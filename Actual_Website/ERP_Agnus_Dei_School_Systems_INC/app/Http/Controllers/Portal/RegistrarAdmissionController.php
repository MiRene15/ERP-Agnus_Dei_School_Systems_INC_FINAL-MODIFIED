<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Requirement;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegistrarAdmissionController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $schoolYear = $request->input('school_year', active_school_year());
        $query = Admission::with('student.user')
            ->where('school_year', $schoolYear);

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', function ($sq) use ($search) {
                    $sq->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                })
                ->orWhereHas('student', function ($sq) use ($search) {
                    $sq->where('first_name', 'ilike', "%{$search}%")
                        ->orWhere('last_name', 'ilike', "%{$search}%");
                })
                ->orWhere('application_number', 'ilike', "%{$search}%");
            });
        }

        if (request('status') && request('status') !== 'All') {
            $query->where('status', request('status'));
        }

        if (request('grade_level') && request('grade_level') !== 'All') {
            $query->where('grade_level', request('grade_level'));
        }

        $admissions = $query->orderByRaw("CASE WHEN status = 'Pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $pendingCount = $admissions->where('status', 'Pending')->count();
        $approvedCount = $admissions->where('status', 'Approved By Registrar')->count();

        $schoolYears = all_school_years();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.admissions-results', compact('admissions'))->render(),
            ]);
        }

        return view('portal.registrar.admissions-index', compact('admissions', 'pendingCount', 'approvedCount', 'schoolYears'));
    }

    public function show(Request $request, Admission $admission)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $admission->load('student.user');
        $admission->load(['requirements' => function ($q) {
            $q->select('id', 'document_type', 'original_filename', 'mime_type', 'file_size', 'status', 'admission_id');
        }]);
        $sections = Section::where('is_active', true)
            ->where('grade_level', $admission->grade_level)
            ->get();

        $classes = Classes::with('subject', 'teacher')
            ->where('grade_level', $admission->grade_level)
            ->where('status', 'active')
            ->get();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.admissions-show-results', compact('admission', 'sections', 'classes'))->render(),
            ]);
        }

        return view('portal.registrar.admissions-show', compact('admission', 'sections', 'classes'));
    }

    public function verifyRequirement(Request $request, Requirement $requirement)
    {
        $requirement->status = $request->input('verify') ? 'Verified' : 'Under Review';
        $requirement->save();

        $admission = $requirement->admission;

        log_activity($requirement, 'Requirement Verified', auth()->user()->name . ' ' . ($request->input('verify') ? 'verified' : 'unverified') . ' requirement: ' . ($requirement->document_type ?? $requirement->requirement_type ?? $requirement->name ?? $requirement->original_filename ?? 'Requirement') . ' for admission #' . $admission->id . '.');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $requirement->status,
                'message' => 'Requirement ' . ($requirement->status === 'Verified' ? 'verified' : 'unverified') . '.',
            ]);
        }

        return back()->with('success', 'Requirement ' . ($requirement->status === 'Verified' ? 'verified' : 'unverified') . '.');
    }

    public function verifyAll(Admission $admission)
    {
        $updated = $admission->requirements()
            ->where('status', 'Under Review')
            ->update(['status' => 'Verified']);

        log_activity($admission, 'All Requirements Verified', auth()->user()->name . ' verified all requirements for admission #' . $admission->id . '.');

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'updated' => $updated,
                'message' => $updated . ' requirement(s) verified successfully.',
            ]);
        }

        return back()->with('success', $updated . ' requirement(s) verified successfully.');
    }

    public function approve(Request $request, Admission $admission)
    {
        if ($admission->status !== 'Pending') {
            return back()->with('error', 'This admission has already been processed.');
        }

        // Gender gate (spec: admission-gender-required.md): approval is
        // blocked while gender is empty — registrar corrects it first.
        if (empty($admission->student?->gender)) {
            return back()->with('error', 'Gender is still missing for this applicant. Please set it above before approving.');
        }

        $unverifiedCount = $admission->requirements()->where('status', '!=', 'Verified')->count();
        if ($unverifiedCount > 0) {
            return back()->with('error', 'All requirements must be verified before approving. ' . $unverifiedCount . ' requirement(s) still pending.');
        }

        // Holds block (re-)enrollment until cleared (library / clinic / finance).
        $studentForHolds = $admission->student;
        if ($studentForHolds) {
            $holds = \App\Services\HoldService::forStudent($studentForHolds);
            if (!empty($holds)) {
                return back()->with('error', 'Cannot enroll — holds must be cleared first. ' . \App\Services\HoldService::blockingMessage($holds));
            }
        }

        // Locked school years are frozen — no new enrollments into them.
        if (school_year_locked($admission->school_year)) {
            return back()->with('error', 'Cannot enroll — school year ' . $admission->school_year . ' is locked.');
        }

        $data = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:classes,id',
        ]);

        foreach ($data['subject_ids'] as $classId) {
            $class = Classes::find($classId);
            if ($class && $class->capacity) {
                $enrolledCount = $class->enrollments()
                    ->where('status', 'Active')
                    ->count();
                if ($enrolledCount >= $class->capacity) {
                    return back()->with('error', 'Subject "' . ($class->subject->name ?? 'N/A') . '" has reached its capacity of ' . $class->capacity . ' students.');
                }
            }
        }

        $student = $admission->student;

        try {
            DB::transaction(function () use ($admission, $student, $data) {
                if ($admission->application_type !== 'Old') {
                    $student->student_number = Student::generateStudentNumber();
                }

                $student->status = 'enrolled';
                $student->save();

                $enrollment = Enrollment::create([
                    'student_id' => $student->id,
                    'section_id' => $data['section_id'],
                    'school_year' => $admission->school_year,
                    'strand' => $admission->strand,
                    'status' => 'Active',
                ]);

                $enrollment->subjects()->attach($data['subject_ids']);

                $admission->status = 'Approved By Registrar';
                $admission->save();
            });

            log_activity($admission, 'Approved', 'Approved admission for ' . $student->first_name . ' ' . $student->last_name);

            // Approval email goes in its own guarded step AFTER the commit
            // (spec: applicant-email-reliability.md): a mail-provider outage
            // must never report an enrolled student as "failed to approve".
            $mailSent = app(\App\Services\FamilyEmailService::class)
                ->sendAdmissionApproval($student, $request->input('_idempotency_key'));

            $enrolledMsg = 'Admission approved for ' . $student->first_name . ' ' . $student->last_name . '. Student has been enrolled with ' . count($data['subject_ids']) . ' subject(s).';

            if ($mailSent) {
                return redirect()->route('registrar.admissions.index')
                    ->with('success', $enrolledMsg . ' Confirmation email sent.');
            }

            return redirect()->route('registrar.admissions.index')
                ->with('error', $enrolledMsg . ' BUT the confirmation email could not be sent. Open the admission record and use Resend.');
        } catch (\Exception $e) {
            Log::error('Admission approval failed: ' . $e->getMessage(), [
                'admission_id' => $admission->id,
                'student_id' => $student->id,
            ]);
            return back()->with('error', 'Failed to approve admission. Please try again.');
        }
    }

    public function updateGender(Request $request, Admission $admission)
    {
        $data = $request->validate([
            'gender' => 'required|in:Male,Female,Non-binary,Prefer not to say',
            'gender_detail' => 'nullable|string|max:100',
        ]);

        $detail = in_array($data['gender'], ['Non-binary', 'Prefer not to say'], true)
            ? ($data['gender_detail'] ?? null)
            : null;

        $admission->student()->update([
            'gender' => $data['gender'],
            'gender_detail' => $detail,
        ]);

        log_activity($admission, 'Gender Corrected', auth()->user()->name . ' set gender to ' . $data['gender'] . ' for admission #' . $admission->id . '.');

        return back()->with('success', 'Gender saved.');
    }

    public function reject(Admission $admission)
    {
        if ($admission->status !== 'Pending') {
            return back()->with('error', 'This admission has already been processed.');
        }

        $admission->status = 'Rejected';
        $admission->save();

        log_activity($admission, 'Admission Rejected', auth()->user()->name . ' rejected admission for student #' . $admission->student_id . '.');

        return redirect()->route('registrar.admissions.index')
            ->with('success', 'Admission application has been rejected.');
    }

    public function resendApprovalEmail(\App\Http\Requests\Registrar\ResendAdmissionEmailRequest $request, Admission $admission)
    {
        if ($admission->status !== 'Approved By Registrar') {
            return back()->with('error', 'Only approved admissions can receive a confirmation email.');
        }

        $student = $admission->student;

        $mailSent = app(\App\Services\FamilyEmailService::class)
            ->sendAdmissionApproval($student, $request->input('_idempotency_key'));

        if ($mailSent) {
            log_activity($admission, 'Approval Email Resent', auth()->user()->name . ' resent the approval email for ' . $student->first_name . ' ' . $student->last_name . '.');

            return back()->with('success', 'Confirmation email sent.');
        }

        return back()->with('error', 'The confirmation email could not be sent. Check mail settings and try again.');
    }
}
