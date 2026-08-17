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
use App\Mail\AdmissionCredentialsMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class RegistrarAdmissionController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = Admission::with('student.user')
            ->where('school_year', active_school_year());

        if (request('search')) {
            $search = request('search');
            $query->where(function ($q) use ($search) {
                $q->whereHas('student.user', function ($sq) use ($search) {
                    $sq->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })->orWhere('application_number', 'like', "%{$search}%");
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
            ->paginate(20)
            ->withQueryString();

        $pendingCount = $admissions->where('status', 'Pending')->count();
        $approvedCount = $admissions->where('status', 'Approved By Registrar')->count();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.admissions-results', compact('admissions'))->render(),
            ]);
        }

        return view('portal.registrar.admissions-index', compact('admissions', 'pendingCount', 'approvedCount'));
    }

    public function show(Admission $admission)
    {
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

        return view('portal.registrar.admissions-show', compact('admission', 'sections', 'classes'));
    }

    public function verifyRequirement(Request $request, Requirement $requirement)
    {
        $requirement->status = $request->input('verify') ? 'Verified' : 'Under Review';
        $requirement->save();

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

        $unverifiedCount = $admission->requirements()->where('status', '!=', 'Verified')->count();
        if ($unverifiedCount > 0) {
            return back()->with('error', 'All requirements must be verified before approving. ' . $unverifiedCount . ' requirement(s) still pending.');
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

            if ($student->user?->email) {
                Mail::to($student->user->email)->send(new AdmissionCredentialsMail($student));
            }

            return redirect()->route('registrar.admissions.index')
                ->with('success', 'Admission approved for ' . $student->first_name . ' ' . $student->last_name . '. Student has been enrolled with ' . count($data['subject_ids']) . ' subject(s).');
        } catch (\Exception $e) {
            Log::error('Admission approval failed: ' . $e->getMessage(), [
                'admission_id' => $admission->id,
                'student_id' => $student->id,
            ]);
            return back()->with('error', 'Failed to approve admission. Please try again.');
        }
    }

    public function reject(Admission $admission)
    {
        if ($admission->status !== 'Pending') {
            return back()->with('error', 'This admission has already been processed.');
        }

        $admission->status = 'Rejected';
        $admission->save();

        return redirect()->route('registrar.admissions.index')
            ->with('success', 'Admission application has been rejected.');
    }
}
