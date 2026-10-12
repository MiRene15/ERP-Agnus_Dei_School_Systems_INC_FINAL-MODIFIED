<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\BulkForwardAdmissionRequest;
use App\Http\Requests\Registrar\ForwardAdmissionRequest;
use App\Http\Requests\Registrar\ReturnAdmissionRequest;
use App\Models\Admission;
use App\Models\Classes;
use App\Models\Requirement;
use App\Models\Section;
use App\Services\AdmissionReviewService;
use App\Services\FullApprovalService;
use Illuminate\Http\Request;

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
            // "Paid" is not a stored status — it is Pending carrying a
            // downpayment receipt (spec: payment-access.md Option A).
            if (request('status') === 'Paid') {
                $query->where('status', 'Pending')->whereNotNull('downpayment_receipt');
            } else {
                $query->where('status', request('status'));
            }
        }

        if (request('grade_level') && request('grade_level') !== 'All') {
            $query->where('grade_level', request('grade_level'));
        }

        // Counts read the year-only scope — never search/status/grade —
        // so the four cards stay honest for the picked school year.
        $baseCounts = Admission::where('school_year', $schoolYear);
        $pendingCount = (clone $baseCounts)->where('status', 'Pending')->whereNull('downpayment_receipt')->count();
        $approvedCount = (clone $baseCounts)->where('status', 'Approved By Registrar')->count();
        $awaitingDownpaymentCount = (clone $baseCounts)->where('status', 'Awaiting Downpayment')->count();
        $paidCount = (clone $baseCounts)->where('status', 'Pending')->whereNotNull('downpayment_receipt')->count();

        $admissions = $query->orderByRaw("CASE WHEN status = 'Pending' THEN 0 ELSE 1 END")
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $schoolYears = all_school_years();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.admissions-results', compact('admissions'))->render(),
                'counts' => [
                    'pending' => $pendingCount,
                    'awaitingDownpayment' => $awaitingDownpaymentCount,
                    'paid' => $paidCount,
                    'approved' => $approvedCount,
                ],
            ]);
        }

        return view('portal.registrar.admissions-index', compact('admissions', 'pendingCount', 'approvedCount', 'awaitingDownpaymentCount', 'paidCount', 'schoolYears'));
    }

    public function show(Request $request, Admission $admission)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $admission->load('student.user');
        $admission->load(['requirements' => function ($q) {
            $q->select('id', 'document_type', 'original_filename', 'mime_type', 'file_size', 'status', 'admission_id');
        }]);

        // Section + subject picker for Grant Full Approval (paid files):
        // active sections of the applied grade, active classes of the
        // applied grade + year with live remaining seats.
        $sections = Section::where('grade_level', $admission->grade_level)
            ->where('is_active', true)
            ->orderBy('section_name')
            ->get();
        $subjectClasses = Classes::with('subject')
            ->where('grade_level', $admission->grade_level)
            ->where('school_year', $admission->school_year)
            ->where('status', 'active')
            ->withCount(['enrollments as active_enrollments_count' => fn ($q) => $q->where('status', 'Active')])
            ->orderBy('section')
            ->orderBy('id')
            ->get();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.admissions-show-results', compact('admission', 'sections', 'subjectClasses'))->render(),
            ]);
        }

        return view('portal.registrar.admissions-show', compact('admission', 'sections', 'subjectClasses'));
    }

    public function verifyRequirement(Request $request, Requirement $requirement)
    {
        $newStatus = $request->input('verify') ? 'Verified' : 'Under Review';

        if ($requirement->status === $newStatus) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'status' => $requirement->status,
                    'message' => 'Already ' . ($newStatus === 'Verified' ? 'verified' : 'under review') . '.',
                ]);
            }

            return back()->with('success', 'Already ' . ($newStatus === 'Verified' ? 'verified' : 'under review') . '.');
        }

        $requirement->status = $newStatus;
        $requirement->save();

        $admission = $requirement->admission;

        log_activity($requirement, 'Requirement Verified', auth()->user()->name . ' ' . ($request->input('verify') ? 'verified' : 'unverified') . ' requirement: ' . ($requirement->document_type ?? $requirement->requirement_type ?? $requirement->name ?? $requirement->original_filename ?? 'Requirement') . ' for admission #' . $admission->id . '.');

        if ($request->wantsJson() || $request->ajax()) {
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

        if ($updated === 0) {
            if (request()->wantsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'updated' => 0,
                    'message' => 'Already verified.',
                ]);
            }

            return back()->with('success', 'Already verified.');
        }

        log_activity($admission, 'All Requirements Verified', auth()->user()->name . ' verified all requirements for admission #' . $admission->id . '.');

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'updated' => $updated,
                'message' => $updated . ' requirement(s) verified successfully.',
            ]);
        }

        return back()->with('success', $updated . ' requirement(s) verified successfully.');
    }

public function forwardToCashier(ForwardAdmissionRequest $request, Admission $admission)
{
    $result = AdmissionReviewService::forward($admission, auth()->user()->name, $request->input('note'));

    if (! $result['forwarded']) {
        return back()->with('error', $result['error']);
    }

    // The student flag is set atomically inside AdmissionReviewService::forward,
    // so the Cashier payments page and student dashboard see this file.
    return redirect()->route('registrar.admissions.index')
        ->with('success', $result['message']);
}

    public function returnWithNote(ReturnAdmissionRequest $request, Admission $admission)
    {
        $result = AdmissionReviewService::returnWithNote($admission, auth()->user()->name, $request->input('comment'));

        if (! $result['returned']) {
            return back()->with('error', $result['error']);
        }

        return redirect()->route('registrar.admissions.index')
            ->with('success', $result['message']);
    }

    public function bulkForward(BulkForwardAdmissionRequest $request)
    {
        $result = AdmissionReviewService::bulkForward($request->input('admission_ids', []), auth()->user()->name, $request->input('note'));

        if ($result['forwarded_count'] === 0) {
            $firstBlocker = $result['blocked'] !== [] ? ' First blocker: ' . reset($result['blocked']) : '';

            return redirect()->route('registrar.admissions.index')
                ->with('error', 'Nothing was forwarded.' . $firstBlocker);
        }

        $message = $result['forwarded_count'] . ' application(s) forwarded to Cashier.';
        if ($result['blocked_count'] > 0) {
            $message .= ' ' . $result['blocked_count'] . ' stayed behind (incomplete or already processed).';
        }

        return redirect()->route('registrar.admissions.index')->with('success', $message);
    }

    public function approve(Request $request, Admission $admission)
    {
        // Full approval (spec: enrollment-payment-gating-payment-access.md):
        // paid Pending file + section assignment → enrollment + institution
        // address. Domain rules live in FullApprovalService — including the
        // downpayment-receipt gate that closes the approve-without-paying
        // bypass. Same route, same response shapes as before.
        $data = $request->validate([
            'section_id' => 'required|exists:sections,id',
            'subject_ids' => 'required|array',
            'subject_ids.*' => 'exists:classes,id',
        ]);

        $result = FullApprovalService::approve(
            $admission,
            (int) $data['section_id'],
            array_map('intval', $data['subject_ids']),
            auth()->user()->name,
            $request->input('_idempotency_key')
        );

        if (! $result['approved']) {
            return back()->with('error', $result['error']);
        }

        if (! ($result['mailSent'] ?? false)) {
            return redirect()->route('registrar.admissions.index')
                ->with('error', $result['message']);
        }

        return redirect()->route('registrar.admissions.index')
            ->with('success', $result['message']);
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
