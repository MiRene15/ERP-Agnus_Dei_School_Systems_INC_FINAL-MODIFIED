<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Admission;
use App\Models\Enrollment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegistrarController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $pendingCount = Admission::where('status', 'Pending')->count();
        $enrolledCount = Enrollment::where('status', 'Active')->count();
        $recentAdmissions = Admission::with('student.user')
            ->where('status', 'Pending')
            ->latest()
            ->take(5)
            ->get();

        // Work queues from the newer workflows, so nothing waits unnoticed.
        $pendingWithdrawals = \App\Models\Withdrawal::where('status', 'Pending')->count();
        $pendingUnlocks = \App\Models\GradeUnlockRequest::where('status', \App\Models\GradeUnlockRequest::STATUS_PENDING)->count();
        $missingLedgers = Enrollment::where('status', 'Active')
            ->where('school_year', active_school_year())
            ->whereDoesntHave('student.ledger')
            ->count();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.registrar.partials.dashboard-results', compact('pendingCount', 'enrolledCount', 'recentAdmissions', 'pendingWithdrawals', 'pendingUnlocks', 'missingLedgers'))->render(),
            ]);
        }

        return view('portal.registrar.dashboard', compact('pendingCount', 'enrolledCount', 'recentAdmissions', 'pendingWithdrawals', 'pendingUnlocks', 'missingLedgers'));
    }

    /**
     * Requests hub — Discount Requests / Withdrawals / End-of-Year Promotion
     * on one page (spec: registrar-menu-restructure.md). Composition only: the
     * same queries as the three standalone pages, so lists and badge counts
     * can never drift apart. Standalone routes and throttle coverage stay valid
     * by being untouched — tab searches keep posting to the throttled
     * standalone routes, so first paint here is the unfiltered list and this
     * method takes no filter input. Tab markup copies the standalone views
     * verbatim (same variable names), so future diffs stay trivial.
     */
    public function requests(): View
    {
        // Discount Requests tab — same data as DiscountRequestController@index.
        $requests = \App\Models\DiscountRequest::with(['ledger.student.user', 'ledger.student.enrollments.section', 'requester', 'reviewer'])
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $ledgers = \App\Models\StudentLedger::with('student.user')
            ->whereHas('student.enrollments', function ($q) {
                $q->where('status', 'Active')->where('school_year', active_school_year());
            })
            ->orderBy('id')
            ->get();
        $discountTypes = \App\Models\DiscountRequest::TYPES;

        // Withdrawals tab — same data as WithdrawalController@index (unfiltered).
        $withdrawals = \App\Models\Withdrawal::with('student.user', 'enrollment.section', 'processor')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        // Promotion tab — same data as PromotionWorkflowController@registrarIndex.
        $enrollments = \App\Models\Enrollment::with(['student.ledger', 'student', 'section', 'grades.schoolClass.subject'])
            ->where('status', 'Active')
            ->where('school_year', active_school_year())
            ->orderBy('school_year', 'desc')
            ->orderBy(\App\Models\Student::selectRaw("CONCAT(first_name, ' ', last_name)")
                ->whereColumn('students.id', 'enrollments.student_id')
            )
            ->get()
            ->groupBy(fn ($e) => $e->section?->grade_level ?? 'Unknown')
            ->sortBy(fn ($_, $k) => \App\Services\PromotionService::gradeRank()[$k] ?? 99);
        $actions = \App\Models\PromotionProposal::ACTIONS;
        $schoolYears = \App\Models\Enrollment::distinct()->orderBy('school_year', 'desc')->pluck('school_year');
        $passingGrade = (int) \App\Models\Setting::getValue('passing_grade', '75');
        $openProposals = \App\Models\PromotionProposal::whereIn('enrollment_id', $enrollments->flatten()->pluck('id'))
            ->whereNotIn('status', [\App\Models\PromotionProposal::STATUS_REJECTED, \App\Models\PromotionProposal::STATUS_EXECUTED])
            ->get()
            ->keyBy('enrollment_id');
        $holdMap = \App\Services\HoldService::forStudentIds($enrollments->flatten()->pluck('student_id'));

        // Badges — counted from the same collections above, so they always match.
        $badgeCounts = [
            'discount-requests' => \App\Models\DiscountRequest::where('status', \App\Models\DiscountRequest::STATUS_PENDING)->count(),
            'withdrawals' => \App\Models\Withdrawal::where('status', 'Pending')->count(),
            'promotion' => $openProposals->count(),
        ];

        return view('portal.registrar.requests', compact('requests', 'ledgers', 'discountTypes', 'withdrawals', 'enrollments', 'actions', 'schoolYears', 'passingGrade', 'openProposals', 'holdMap', 'badgeCounts'));
    }

    /**
     * Sections & Subjects hub (spec: registrar-menu-restructure.md).
     * Same composition-only rule as requests(): unfiltered first paint, tab
     * searches post to the throttled standalone routes. Registrar mode only
     * ($readOnly = false); the principal's shared subjects path is untouched.
     */
    public function sectionsSubjects(): View
    {
        // Sections tab — same data as Admin\SectionController@index.
        $sections = \App\Models\Section::with('adviser')
            ->orderBy('grade_level')->orderBy('section_name')->get()->groupBy('grade_level');
        $gradeLevels = ['All', 'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'];

        // Subjects tab — same data as Admin\SubjectController@index (registrar mode).
        $subjects = \App\Models\Subject::query()
            ->orderBy('grade_level')->orderBy('name')->get()->groupBy('grade_level');
        // Subjects carries SHS where sections stops at Grade 12, so the tab
        // gets its own list — the only one-line adaptation from verbatim copy.
        $subjectGradeLevels = ['All', 'Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12', 'SHS'];
        $readOnly = false;
        $pendingRequests = \App\Models\SubjectChangeRequest::with('subject', 'requester')
            ->where('status', \App\Models\SubjectChangeRequest::STATUS_PENDING)
            ->latest()
            ->get();

        return view('portal.registrar.sections-subjects', compact('sections', 'gradeLevels', 'subjects', 'subjectGradeLevels', 'readOnly', 'pendingRequests'));
    }

    /**
     * Grade & Fee hub — Grade Edit / Fee Assignment on one page
     * (spec: registrar-menu-restructure.md). Composition only, same rule.
     */
    public function gradeFee(): View
    {
        // Grade Edit tab — same data as GradeUnlockController@reviewIndex.
        $pending = \App\Models\GradeUnlockRequest::with(['schoolClass.subject', 'schoolClass.teacher', 'requester'])
            ->where('status', \App\Models\GradeUnlockRequest::STATUS_PENDING)
            ->latest()
            ->get();
        $history = \App\Models\GradeUnlockRequest::with(['schoolClass.subject', 'requester', 'reviewer'])
            ->whereIn('status', [\App\Models\GradeUnlockRequest::STATUS_APPROVED, \App\Models\GradeUnlockRequest::STATUS_REJECTED])
            ->latest()
            ->take(50)
            ->get();

        // Fee Assignment tab — same data as FeeAssignmentController@index.
        $schoolYear = active_school_year();
        $missingLedgers = Enrollment::with('student', 'section')
            ->where('status', 'Active')
            ->where('school_year', $schoolYear)
            ->whereDoesntHave('student.ledger')
            ->orderBy('id')
            ->get();
        $gradFees = \App\Models\GraduationFee::orderByDesc('school_year')->orderBy('grade_level')->get()->map(function ($fee) {
            $eligible = Enrollment::where('status', 'Active')
                ->whereHas('section', fn ($q) => $fee->grade_level ? $q->where('grade_level', $fee->grade_level) : $q)
                ->when($fee->school_year, fn ($q) => $q->where('school_year', $fee->school_year));
            $assignedIds = \App\Models\StudentGraduationFee::where('graduation_fee_id', $fee->id)->pluck('student_id')->all();
            $fee->eligible_count = (clone $eligible)->count();
            $fee->missing_count = (clone $eligible)->whereNotIn('student_id', $assignedIds)->count();
            return $fee;
        });

        $badgeCounts = ['grade-edit' => $pending->count()];

        return view('portal.registrar.grade-fee', compact('pending', 'history', 'missingLedgers', 'gradFees', 'schoolYear', 'badgeCounts'));
    }
}
