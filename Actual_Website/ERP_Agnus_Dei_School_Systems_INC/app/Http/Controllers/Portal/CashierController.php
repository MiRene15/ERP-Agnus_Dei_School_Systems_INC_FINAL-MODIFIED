<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\BulkDownpaymentRequest;
use App\Http\Requests\Portal\ProcessCashierPaymentRequest;
use App\Http\Requests\Portal\ProjectionFilterRequest;
use App\Mail\PaymentConfirmationMail;
use App\Models\Admission;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\LibraryTransaction;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentLedger;
use App\Models\Withdrawal;
use App\Services\CashierProjectionService;
use App\Services\DownpaymentService;
use App\Services\FeeAssessmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CashierController extends Controller
{
    public function index(): View
    {
        $todayCollection = Payment::whereDate('payment_date', today())->sum('amount_paid');
        $receiptsToday = Payment::whereDate('payment_date', today())->count();

        // Work queues: approved discounts to apply + refund payouts to release.
        $approvedDiscounts = \App\Models\DiscountRequest::where('status', \App\Models\DiscountRequest::STATUS_APPROVED)->count();
        $pendingRefunds = \App\Models\Withdrawal::where('status', 'Approved')
            ->where('refund_amount', '>', 0)
            ->whereNull('refund_processed_at')
            ->count();

        return view('portal.cashier.dashboard', [
            'todayCollection' => $todayCollection,
            'receiptsToday' => $receiptsToday,
            'approvedDiscounts' => $approvedDiscounts,
            'pendingRefunds' => $pendingRefunds,
        ]);
    }

    public function projections(ProjectionFilterRequest $request, CashierProjectionService $projectionService)
    {
        $schoolYear = $request->input('school_year', active_school_year());

        [$periodFrom, $periodTo] = $this->resolveProjectionPeriod($request, $schoolYear, $projectionService);

        $series = $projectionService->monthlySeriesForRange($periodFrom, $periodTo);

        $schoolYears = all_school_years();
        if ($schoolYears->isEmpty()) {
            $schoolYears = collect([$schoolYear]);
        }

        $summary = $projectionService->summaryForPeriod($periodFrom, $periodTo);
        $stacked = $projectionService->yearlyCollectionsStacked($periodFrom, $periodTo);

        // Receivables are a real position to date, so the card never advertises a day
        // that has not happened yet. A school year runs to May, so the default period
        // ends in the future for most of the year while the figure is already "now".
        $receivablesAsOfLabel = $periodTo->copy()->min(Carbon::today())->format('M j, Y');

        return view('portal.cashier.projections', array_merge($series, $stacked, [
            'schoolYear' => $schoolYear,
            'schoolYears' => $schoolYears,
            'summary' => $summary,
            'dateFrom' => $periodFrom->toDateString(),
            'dateTo' => $periodTo->toDateString(),
            'periodLabel' => $this->periodLabel($periodFrom, $periodTo),
            'receivablesAsOfLabel' => $receivablesAsOfLabel,
            'earliestDate' => $projectionService->earliestSelectableDate(),
            'usingCustomDates' => $request->input('date_from') !== null && $request->input('date_to') !== null,
        ]));
    }

    private function periodLabel(Carbon $from, Carbon $to): string
    {
        $today = Carbon::now();

        if ($from->equalTo($today->copy()->startOfMonth()) && $to->equalTo($today)) {
            return 'Month to Date';
        }

        if ($from->equalTo($today->copy()->startOfMonth()) && $to->equalTo($today->copy()->endOfMonth())) {
            return 'This Month';
        }

        if ($from->year === $to->year) {
            return $from->format('M j') . ' - ' . $to->format('M j, Y');
        }

        return $from->format('M j, Y') . ' - ' . $to->format('M j, Y');
    }

    /**
     * @return array{0: \Carbon\Carbon, 1: \Carbon\Carbon}
     */
    private function resolveProjectionPeriod(
        ProjectionFilterRequest $request,
        string $schoolYear,
        CashierProjectionService $projectionService
    ): array {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($dateFrom !== null && $dateTo !== null) {
            return $projectionService->clampRange(Carbon::parse($dateFrom), Carbon::parse($dateTo));
        }

        return $projectionService->monthRangeForSchoolYear($schoolYear);
    }

    public function payments(Request $request)
    {
        $search = $request->input('search');
        $schoolYear = $request->input('school_year', active_school_year());
        $students = collect();
        $schoolYears = all_school_years();

        if ($search && strlen($search) >= 2) {
$students = Student::whereIn('status', ['enrolled', 'Awaiting Downpayment'])
                ->where(function ($q) use ($schoolYear) {
                    $q->whereHas('enrollments', fn ($qe) => $qe->where('status', 'Active')->where('school_year', $schoolYear))
                        ->orWhereHas('admissions', fn ($qa) => $qa->where('status', 'Awaiting Downpayment')->where('school_year', $schoolYear));
                })
                ->where(function ($q) use ($search) {
                    $q->where('first_name', 'ilike', "%{$search}%")
                        ->orWhere('last_name', 'ilike', "%{$search}%")
                        ->orWhere('student_number', 'ilike', "%{$search}%")
                        ->orWhere('legacy_lrn', 'ilike', "%{$search}%")
                        ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$search}%"]);
                })
            ->with(['user', 'enrollments.section', 'ledger', 'admissions'])
            ->limit(10)
            ->get();

        $gradeLevelByStudent = [];

        foreach ($students as $student) {
            $active = $student->enrollments->where('status', 'Active')->where('school_year', $schoolYear)->sortByDesc('id')->first();
            $pickedAdmission = $student->admissions->where('school_year', $schoolYear)->sortByDesc('id')->first();
            $gradeLevelByStudent[$student->getKey()] = $active?->section?->grade_level;
            // Forwarded files have no enrollment yet: show the grade picked
            // on the admission so the row never reads N/A.
            $student->display_grade = $active?->section?->grade_level
                ?? $pickedAdmission?->grade_level;
        }

        $assessedByGradeLevel = $this->assessedTotalsByGradeLevel(
            array_values(array_unique(array_filter($gradeLevelByStudent))),
            $schoolYear
        );

        $students->loadMissing('ledger.ledgerLines');

        foreach ($students as $student) {
            $totalAssessed = $assessedByGradeLevel[$gradeLevelByStudent[$student->getKey()] ?? ''] ?? 0;

            $totalPaid = $student->ledger?->total_paid ?? 0;
            $discountApplied = $student->ledger?->discount_applied ?? 0;
            $student->computed_balance = max(0, $totalAssessed - $totalPaid - $discountApplied);
            $student->payment_status = $this->paymentStatusFor($student, $totalAssessed);
        }
        }

        return view('portal.cashier.payments', compact('students', 'search', 'schoolYear', 'schoolYears'));
    }

    public function searchStudents(Request $request)
    {
        $search = $request->search;
        $schoolYear = $request->input('school_year', active_school_year());

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        // Forwarded files must be findable here (spec: review-forward §8 check 3 —
        // the file "reaches the Cashier queue" before child 2 ships its own
        // downpayment queue). Match the student flag OR the admission state,
        // so older forwards whose student flag predates the atomic update
        // still show up.
        $students = Student::where(function ($q) {
                $q->whereIn('status', ['enrolled', 'Awaiting Downpayment'])
                    ->orWhereHas('admissions', fn ($qa) => $qa->where('status', 'Awaiting Downpayment'));
            })
            ->where(function ($q) use ($schoolYear) {
                $q->whereHas('enrollments', fn ($qe) => $qe->where('status', 'Active')->where('school_year', $schoolYear))
                    ->orWhereHas('admissions', fn ($qa) => $qa->where('status', 'Awaiting Downpayment')->where('school_year', $schoolYear));
            })
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('student_number', 'ilike', "%{$search}%")
                    ->orWhere('legacy_lrn', 'ilike', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$search}%"]);
            })
            ->with(['enrollments.section', 'ledger', 'admissions'])
            ->limit(10)
            ->get();

        $gradeLevelByStudent = [];

        foreach ($students as $student) {
            $active = $student->enrollments->where('status', 'Active')->where('school_year', $schoolYear)->sortByDesc('id')->first();
            // Same fallback as payments(): forwarded files have no
            // enrollment yet, so read the grade from the picked-year admission
            // and expose it as display_grade for the queue row.
            $grade = $active?->section?->grade_level
                ?? $student->admissions->where('school_year', $schoolYear)->sortByDesc('id')->first()?->grade_level;
            $gradeLevelByStudent[$student->getKey()] = $grade;
            $student->display_grade = $grade;
        }

        $assessedByGradeLevel = $this->assessedTotalsByGradeLevel(
            array_values(array_unique(array_filter($gradeLevelByStudent))),
            $schoolYear
        );

        $students->loadMissing('ledger.ledgerLines');

        foreach ($students as $student) {
            $totalAssessed = $assessedByGradeLevel[$gradeLevelByStudent[$student->getKey()] ?? ''] ?? 0;

            $totalPaid = $student->ledger?->total_paid ?? 0;
            $discountApplied = $student->ledger?->discount_applied ?? 0;

            $student->computed_balance = max(0, $totalAssessed - $totalPaid - $discountApplied);
            $student->payment_status = $this->paymentStatusFor($student, $totalAssessed);
        }

        return response()->json($students);
    }

    /**
     * @param  array<int, string>  $gradeLevels
     * @return array<string, float>
     */
    private function assessedTotalsByGradeLevel(array $gradeLevels, string $schoolYear): array
    {
        if ($gradeLevels === []) {
            return [];
        }

        $totals = [];
        foreach (array_values(array_unique(array_filter($gradeLevels))) as $gradeLevel) {
            $totals[$gradeLevel] = Cache::remember(
                "fee_totals:{$schoolYear}:{$gradeLevel}",
                3600,
                function () use ($schoolYear, $gradeLevel) {
                    return (float) FeeSchedule::where('school_year', $schoolYear)
                        ->where('grade_level', $gradeLevel)
                        ->get(['grade_level', 'tuition_fee', 'misc_fee'])
                        ->sum(fn ($schedule) => (float) $schedule->tuition_fee + (float) $schedule->misc_fee);
                }
            );
        }

        return $totals;
    }

    /**
     * Spec cashier-fee-rules.md: the payments list leads with status
     * (Fully Paid / Has Balance), never a raw balance. Assessed ledgers
     * read their line states; unassessed rows fall back to balance math.
     */
    private function paymentStatusFor(Student $student, float $totalAssessed): string
    {
        $ledger = $student->ledger;

        if ($ledger && $ledger->relationLoaded('ledgerLines') && $ledger->ledgerLines->isNotEmpty()) {
            return app(FeeAssessmentService::class)->statusFor($ledger)['status'];
        }

        // Spec cashier-forwarded-new-account-display.md §4.4: a new
        // account with an Awaiting-Downpayment admission and no receipt
        // reads Awaiting Downpayment — never Has Balance. Assessed
        // ledgers (including has-balance returnees) already returned
        // above, so their clearance path is untouched.
        $admissions = $student->relationLoaded('admissions')
            ? $student->admissions
            : $student->admissions()->latest()->limit(1)->get();
        $awaiting = $admissions->where('status', 'Awaiting Downpayment')->sortByDesc('id')->first();
        if ($awaiting && empty($awaiting->downpayment_receipt)) {
            return 'Awaiting Downpayment';
        }

        $balance = max(0, $totalAssessed - (float) ($ledger?->total_paid ?? 0) - (float) ($ledger?->discount_applied ?? 0));

        return ($totalAssessed > 0 && $balance <= 0) ? 'Fully Paid' : 'Has Balance';
    }

    public function showPayment(Request $request, Student $student, FeeAssessmentService $fees)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $student->load('user', 'enrollments.section', 'ledger.ledgerLines', 'ledger.payments', 'admissions');
        $enrollment = $student->enrollments->where('status', 'Active')->sortByDesc('id')->first();
        // Forwarded files have no enrollment yet (review-forward): read the
        // grade and year from the latest admission so Cashier sees the real
        // fee basis instead of N/A. Section stays N/A until the Registrar
        // assigns it at full approval (child 2).
        $latestAdmission = $student->admissions->sortByDesc('id')->first();
        $displayGrade = $enrollment?->section?->grade_level ?? $latestAdmission?->grade_level;
        $displayYear = $enrollment?->school_year ?? $latestAdmission?->school_year ?? active_school_year();
        $feeSchedules = $displayGrade ? FeeSchedule::where('grade_level', $displayGrade)
            ->where('school_year', $displayYear)
            ->orderBy('term')
            ->get() : collect();

        $hasScholarship = $student->scholarship ?? false;
        $isSHS = in_array((string) $displayGrade, ['Grade 11', 'Grade 12'], true);

        $admission = $student->admissions->where('school_year', $displayYear)->sortByDesc('id')->first() ?? $latestAdmission;
        $admissionType = $admission?->application_type ?? 'New';

        $autoDiscountType = null;
        $autoDiscountAmount = 0;

        if ($hasScholarship && $isSHS) {
            $feeSchedules = $feeSchedules->map(function ($fs) {
                $fs->tuition_fee = 0;
                return $fs;
            });
            $autoDiscountType = 'esc';
            $autoDiscountAmount = $feeSchedules->sum('tuition_fee');
        } elseif ($admissionType === 'Honor') {
            $autoDiscountType = 'honor';
            $autoDiscountAmount = 0;
        } elseif ($admissionType === 'Sibling') {
            $autoDiscountType = 'sibling';
            $autoDiscountAmount = 0;
        }

        $totalTuition = $feeSchedules->sum('tuition_fee');
        $totalMisc = $feeSchedules->sum('misc_fee');
        // Spec cashier-full-payment-label.md: tuition-only 10%, add-ons at
        // 100% — split the bundle once here so previews, floors, and the
        // assessment all read the same bases.
        $split = $fees->splitSchedules($feeSchedules);
        $totalTuition = $split['tuition'];
        $totalMisc = $split['misc'];
        $miscItems = $split['items'];
        $totalAssessed = $totalTuition + $totalMisc;

        if ($autoDiscountType === 'honor' && $totalAssessed > 0) {
            $autoDiscountAmount = round($totalAssessed * 0.10, 2);
        } elseif ($autoDiscountType === 'sibling' && $totalAssessed > 0) {
            $autoDiscountAmount = round($totalAssessed * 0.05, 2);
        }

        $discountTypes = [
            'honor' => 'Honor',
            'sibling' => 'Sibling',
            'esc' => 'ESC Grant',
            'other' => 'Other',
        ];

        $discountApplied = $student->ledger?->discount_applied ?? 0;

        // Pending-approval note (spec: cashier-discount-auto-apply.md): truthful
        // read-only line when a request awaits decision — never a discount.
        $hasPendingDiscount = $student->ledger
            ? \App\Models\DiscountRequest::where('student_ledger_id', $student->ledger->id)
                ->where('status', \App\Models\DiscountRequest::STATUS_PENDING)
                ->exists()
            : false;

        $nextArNumber = (new Payment())->generateArNumber();

        // Spec cashier-fee-rules.md: status-first screen — term total +
        // status first, then lines; library dues as named lines.
        $ledgerLines = $student->ledger?->ledgerLines ?? collect();
        $feeStatus = ($student->ledger && $ledgerLines->isNotEmpty())
            ? $fees->statusFor($student->ledger)
            : null;
        if ($student->ledger && $ledgerLines->isNotEmpty()) {
            $termFloor = $fees->floorFor($student->ledger);
        } else {
            // Spec cashier-full-payment-label.md: pre-assessment hint binds to
            // the picked plan — installment floors at the ₱1.5k downpayment,
            // full floors at 90% tuition + 100% misc with no ₱1.5k line.
            $previewFull = $fees->fullTotalFor((float) $totalTuition, (float) $totalMisc);
            $termFloor = ['floor' => FeeAssessmentService::ENROLLMENT_FEE, 'fullFloor' => $previewFull, 'label' => 'Enrollment Fee', 'line_ids' => []];
        }
        $libraryDues = LibraryTransaction::with('book')
            ->where('student_id', $student->id)
            ->whereNull('paid_payment_id')
            ->where(function ($q) {
                $q->where('fees_assessed', true)->orWhere('total_fees', '>', 0);
            })
            ->orderBy('borrow_date', 'desc')
            ->get();
        $libraryDuesTotal = round((float) $libraryDues->sum('total_fees'), 2);

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.cashier.partials.payment-form', compact('student', 'enrollment', 'feeSchedules', 'totalTuition', 'totalMisc', 'miscItems', 'totalAssessed', 'discountTypes', 'discountApplied', 'hasScholarship', 'isSHS', 'nextArNumber', 'autoDiscountType', 'autoDiscountAmount', 'admission', 'admissionType', 'hasPendingDiscount', 'feeStatus', 'termFloor', 'libraryDues', 'libraryDuesTotal', 'ledgerLines', 'displayGrade', 'displayYear'))->render(),
            ]);
        }

        return view('portal.cashier.payment', compact('student', 'enrollment', 'feeSchedules', 'totalTuition', 'totalMisc', 'miscItems', 'totalAssessed', 'discountTypes', 'discountApplied', 'hasScholarship', 'isSHS', 'nextArNumber', 'autoDiscountType', 'autoDiscountAmount', 'admission', 'admissionType', 'hasPendingDiscount', 'feeStatus', 'termFloor', 'libraryDues', 'libraryDuesTotal', 'ledgerLines', 'displayGrade', 'displayYear'));
    }

    public function processPayment(ProcessCashierPaymentRequest $request, Student $student, FeeAssessmentService $fees)
    {
        $data = $request->validated();

        $enrollment = $student->enrollments()->with('section')->where('status', 'Active')->latest()->firstOrFail();

        // Locked school years are frozen — no payments into them.
        if (school_year_locked($enrollment->school_year)) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Cannot collect — school year ' . $enrollment->school_year . ' is locked.'], 422);
            }

            return back()->with('error', 'Cannot collect — school year ' . $enrollment->school_year . ' is locked.');
        }
        $feeSchedules = FeeSchedule::where('grade_level', $enrollment->section->grade_level)
            ->where('school_year', $enrollment->school_year)
            ->get();

        $hasScholarship = $student->scholarship ?? false;
        $isSHS = in_array($enrollment->section->grade_level, ['Grade 11', 'Grade 12']);

        $admission = $student->admissions()->where('school_year', $enrollment->school_year)->latest()->first();
        $admissionType = $admission?->application_type ?? 'New';

        if ($hasScholarship && $isSHS) {
            $totalTuition = 0;
            $split = $fees->splitSchedules($feeSchedules);
            $totalMisc = $split['misc'];
            $miscItems = $split['items'];
            $totalAssessed = $totalMisc;
            $autoDiscountType = 'esc';
            $autoDiscountAmount = $feeSchedules->sum('tuition_fee');
        } else {
            $split = $fees->splitSchedules($feeSchedules);
            $totalTuition = $split['tuition'];
            $totalMisc = $split['misc'];
            $miscItems = $split['items'];
            $totalAssessed = $totalTuition + $totalMisc;
            $autoDiscountType = $admissionType === 'Honor' ? 'honor' : ($admissionType === 'Sibling' ? 'sibling' : null);
            $autoDiscountAmount = 0;
            if ($autoDiscountType === 'honor') {
                $autoDiscountAmount = round($totalAssessed * 0.10, 2);
            } elseif ($autoDiscountType === 'sibling') {
                $autoDiscountAmount = round($totalAssessed * 0.05, 2);
            }
        }

        $discountAmount = (float) ($data['discount_amount'] ?? 0);
        if ($discountAmount <= 0 && $autoDiscountAmount > 0) {
            $discountAmount = $autoDiscountAmount;
        }
        $discountAmount = min($discountAmount, $totalAssessed);

        $receiptFilePath = null;
        if ($request->hasFile('receipt_file')) {
            $receiptFilePath = $request->file('receipt_file')->store('receipts/' . $student->id, 'public');
        }

        // Spec cashier-full-payment-label.md: per-plan floor guard (never
        // below the total, naming the figure) + shared tuition+library receipt.
        // Full floors at 90% tuition + 100% misc with no ₱1.5k line.
        $libraryDueIds = collect($data['library_transaction_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $libraryDues = $libraryDueIds->isEmpty()
            ? collect()
            : LibraryTransaction::whereIn('id', $libraryDueIds)
                ->where('student_id', $student->id)
                ->whereNull('paid_payment_id')
                ->get();
        $librarySum = round((float) $libraryDues->sum('total_fees'), 2);

        $preLedger = $student->ledger;
        if ($preLedger && $preLedger->ledgerLines()->exists()) {
            $floor = $fees->floorFor($preLedger);
            $floorAmount = (float) $floor['floor'];
            $floorLabel = (string) $floor['label'];
        } else {
            $isFullPreview = $data['payment_plan'] === FeeAssessmentService::PATH_FULL;
            $floorAmount = $isFullPreview
                ? $fees->fullTotalFor((float) $totalTuition, (float) $totalMisc)
                : FeeAssessmentService::ENROLLMENT_FEE;
            $floorLabel = $isFullPreview ? 'Full total' : 'Enrollment Fee';
        }
        $required = round($floorAmount + $librarySum, 2);
        if ($required > 0 && (float) $data['amount_paid'] < $required) {
            $message = 'Minimum ₱' . number_format($required, 2) . ' — ' . $floorLabel . ' ₱' . number_format($floorAmount, 2)
                . ($librarySum > 0 ? ' + library dues ₱' . number_format($librarySum, 2) : '')
                . '. Below-floor payments are never accepted.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('cashier.payment', $student)
                ->with('error', $message)->withInput();
        }

        $creditedExcess = 0.0;

        // Fully Paid ledgers take no further tuition payments. Library dues
        // may still be settled on their own receipt when ticked above.
        $tuitionFullyPaid = $preLedger
            && $preLedger->ledgerLines()->exists()
            && $fees->floorFor($preLedger)['floor'] <= 0;
        if ($tuitionFullyPaid && $librarySum <= 0) {
            $message = 'Fully Paid — no further payments required for this account.';
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $message], 422);
            }

            return redirect()->route('cashier.payment', $student)
                ->with('error', $message)->withInput();
        }

        try {
            DB::transaction(function () use ($student, $data, $totalAssessed, $totalTuition, $totalMisc, $miscItems, $discountAmount, $receiptFilePath, $hasScholarship, $isSHS, $autoDiscountType, $enrollment, $fees, $libraryDues, $librarySum, &$creditedExcess) {
                $ledger = $student->ledger;
                $path = $data['payment_plan'];

                $syStart = (int) explode('-', (string) $enrollment->school_year)[0];
                $elapsed = Carbon::create($syStart, 6, 1)->startOfMonth()->diffInMonths(Carbon::now()->startOfMonth(), false);
                $remainingMonths = max(1, FeeAssessmentService::INSTALLMENTS - max(0, (int) $elapsed));
                // Calendar anchor: Month 1 is due from June of the SY start,
                // or from the current month for mid-year enrollees — so each
                // line names a real month ("pay this month" = earliest unpaid).
                $juneStart = Carbon::create($syStart, 6, 1)->startOfMonth();
                $assessStart = $juneStart->greaterThan(Carbon::now()->startOfMonth()) ? $juneStart : Carbon::now()->startOfMonth();

                if (!$ledger) {
                    if ($discountAmount <= 0 && $autoDiscountType) {
                        $discountType = $autoDiscountType;
                    } else {
                        $discountType = $data['discount_type'] ?? null;
                    }

                    $ledger = StudentLedger::create([
                        'student_id' => $student->id,
                        'school_year' => $enrollment->school_year,
                        'payment_plan' => $path,
                        'assessment_path' => $path,
                        'enrollment_fee' => $path === FeeAssessmentService::PATH_FULL ? 0 : FeeAssessmentService::ENROLLMENT_FEE,
                        'tuition_assessed' => $totalTuition,
                        'installment_count' => $path === FeeAssessmentService::PATH_INSTALLMENT ? $remainingMonths : 0,
                        'total_assessed' => $totalAssessed,
                        'discount_type' => $discountType,
                        'discount_applied' => $discountAmount,
                        'total_paid' => 0,
                        'balance' => max(0, $totalAssessed - $discountAmount),
                        'clearance_status' => 'Pending',
                    ]);

                    $fees->assessLedger($ledger->refresh(), (float) $totalTuition, (string) $enrollment->school_year, $path, $remainingMonths, $assessStart, (float) $totalMisc, $miscItems ?? []);
                    $ledger = $ledger->refresh();
                } else {
                    if ($ledger->ledgerLines()->exists() && $ledger->assessment_path && $ledger->assessment_path !== $path) {
                        $fees->switchPath($ledger, $path);
                        $ledger = $ledger->refresh();
                    } elseif (!$ledger->ledgerLines()->exists()) {
                        $historicPaid = round((float) $ledger->total_paid, 2);
                        $ledger->school_year = $ledger->school_year ?? $enrollment->school_year;
                        $ledger->save();
                        $fees->assessLedger($ledger->refresh(), (float) $totalTuition, (string) $enrollment->school_year, $ledger->assessment_path ?? $path, $remainingMonths, $assessStart, (float) $totalMisc, $miscItems ?? []);
                        $ledger = $ledger->refresh();
                        if ($historicPaid > 0) {
                            $fees->allocatePayment($ledger, $historicPaid);
                            $ledger = $ledger->refresh();
                        }
                    }

                    if ($ledger->discount_applied <= 0 && $discountAmount > 0) {
                        $ledger->discount_applied = $discountAmount;
                        if ($autoDiscountType) {
                            $ledger->discount_type = $autoDiscountType;
                        } elseif (isset($data['discount_type']) && $data['discount_type']) {
                            $ledger->discount_type = $data['discount_type'];
                        }
                        $ledger->save();
                    }
                }

                $receiptNumber = null;
                for ($attempt = 0; $attempt < 5; $attempt++) {
                    $todayCount = Payment::whereDate('payment_date', today())->count() + 1;
                    $receiptNumber = 'RCP-' . now()->format('Ymd') . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);
                    $exists = Payment::where('receipt_number', $receiptNumber)->exists();
                    if (!$exists) break;
                    $receiptNumber = null;
                }

                if (!$receiptNumber) {
                    throw new \Exception('Could not generate unique receipt number after 5 attempts.');
                }

                $arNumber = ($data['ar_number'] ?? null) ?: (new Payment())->generateArNumber();

                $tuitionPortion = round(max(0, (float) $data['amount_paid'] - $librarySum), 2);
                $payment = Payment::create([
                    'ledger_id' => $ledger->id,
                    'cashier_id' => auth()->id(),
                    'amount_paid' => $data['amount_paid'],
                    'tuition_amount' => $tuitionPortion,
                    'library_amount' => $librarySum,
                    'receipt_number' => $receiptNumber,
                    'ar_number' => $arNumber,
                    'receipt_file_path' => $receiptFilePath,
                    'payment_date' => now(),
                ]);

                if ($libraryDues->isNotEmpty()) {
                    $fees->markLibraryDuesPaid($payment, $libraryDues);
                }

                $allocation = $fees->allocatePayment($ledger->refresh(), $tuitionPortion);
                $creditedExcess = round((float) $allocation['excess'], 2);
                $ledger = $ledger->refresh();
                $ledger->total_paid = round((float) $ledger->ledgerLines()->sum('amount_paid'), 2);
                $ledger->balance = max(0, $ledger->total_assessed - $ledger->total_paid - $ledger->discount_applied);
                $ledger->save();
                \App\Services\LedgerService::refreshClearance($ledger);

                log_activity($student, 'Payment', 'Payment of ₱' . number_format($data['amount_paid'], 2) . ' processed (Receipt: ' . $receiptNumber . ', AR: ' . $arNumber
                    . ($librarySum > 0 ? ', library ₱' . number_format($librarySum, 2) : '')
                    . ($creditedExcess > 0 ? ', credited ₱' . number_format($creditedExcess, 2) . ' to next term' : '') . ')');
            });
        } catch (\Exception $e) {
            Log::error('Payment processing failed: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'amount' => $data['amount_paid'],
            ]);
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Payment processing failed. Please try again.'], 422);
            }

            return redirect()->route('cashier.payment', $student)
                ->with('error', 'Payment processing failed. Please try again.');
        }

        $lastPayment = Payment::where('ledger_id', $student->ledger?->id ?? 0)->latest()->first();

        if ($lastPayment) {
            try {
                $student->load('user');
                $email = $student->user->email;
                if ($student->personal_email) {
                    $email = $student->personal_email;
                }
                $confirmationMail = new PaymentConfirmationMail($lastPayment);
                $confirmationMail->idempotencyMarker = $request->input('_idempotency_key');
                Mail::to($email)->send($confirmationMail);
            } catch (\Exception $e) {
                Log::warning('Failed to send payment confirmation email: ' . $e->getMessage(), [
                    'student_id' => $student->id,
                    'payment_id' => $lastPayment->id,
                ]);
            }
        }

        $freshLedger = $student->ledger ? $student->ledger->fresh() : null;
        $totalAssessedAfter = $freshLedger ? round((float) $freshLedger->total_assessed, 2) : 0.0;
        $totalPaidAfter = $freshLedger ? round((float) $freshLedger->total_paid, 2) : round((float) $data['amount_paid'], 2);
        $discountAfter = $freshLedger ? round((float) $freshLedger->discount_applied, 2) : 0.0;
        $balanceAfter = max(0.0, round($totalAssessedAfter - $totalPaidAfter - $discountAfter, 2));
        $percentPaid = $totalAssessedAfter > 0
            ? (int) min(100, round(($totalPaidAfter / $totalAssessedAfter) * 100))
            : 100;

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => '₱' . number_format($data['amount_paid'], 2) . ' received from ' . $student->first_name . ' ' . $student->last_name . '.'
                    . ($creditedExcess > 0 ? ' ₱' . number_format($creditedExcess, 2) . ' credited to next term.' : ''),
                'amount' => $data['amount_paid'],
                'credited' => $creditedExcess,
                'library_amount' => $librarySum,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'receipt_number' => $lastPayment?->receipt_number ?? '',
                'payment_id' => $lastPayment?->id ?? '',
                'print_url' => $lastPayment ? route('cashier.receipt.print', $lastPayment) : null,
                'balance_after' => $balanceAfter,
                'percent_paid' => $percentPaid,
                'total_paid' => $totalPaidAfter,
                'total_assessed' => $totalAssessedAfter,
            ]);
        }

        return redirect()->route('cashier.payment', $student)
            ->with('payment_success', [
                'amount' => $data['amount_paid'],
                'credited' => $creditedExcess,
                'library_amount' => $librarySum,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'receipt_number' => $lastPayment?->receipt_number ?? '',
                'payment_id' => $lastPayment?->id ?? '',
                'balance_after' => $balanceAfter,
                'percent_paid' => $percentPaid,
                'total_paid' => $totalPaidAfter,
                'total_assessed' => $totalAssessedAfter,
            ]);
    }

    /**
     * Confirm downpayment for a forwarded (not yet enrolled) file
     * (spec: enrollment-payment-gating-payment-access.md §4.1).
     * Thin HTTP wrapper — money rules live in DownpaymentService.
     */
    public function confirmDownpayment(ProcessCashierPaymentRequest $request, Student $student)
    {
        $data = $request->validated();

        if ($request->hasFile('receipt_file')) {
            $data['receipt_file_path'] = $request->file('receipt_file')->store('receipts/' . $student->id, 'public');
        }

        $result = DownpaymentService::confirm($student, $data, (int) auth()->id(), (string) auth()->user()->name);

        if (! $result['confirmed']) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => $result['error']], 422);
            }

            return back()->with('error', $result['error'])->withInput();
        }

        // Confirmation email goes after the commit (same guarantee as the
        // enrolled path): a mail failure never un-confirms money.
        $payment = $result['payment'] ?? null;
        if ($payment) {
            try {
                $email = $student->personal_email ?: $student->user?->email;
                if ($email) {
                    $confirmationMail = new PaymentConfirmationMail($payment);
                    $confirmationMail->idempotencyMarker = $request->input('_idempotency_key');
                    Mail::to($email)->send($confirmationMail);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to send downpayment confirmation email: ' . $e->getMessage(), [
                    'student_id' => $student->id,
                    'payment_id' => $payment->id,
                ]);
            }
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'already' => $result['already'],
                'message' => $result['message'],
                'receipt_number' => $result['receipt'] ?? '',
                'payment_id' => $payment?->id ?? '',
                'print_url' => $payment ? route('cashier.receipt.print', $payment) : null,
            ]);
        }

        return redirect()->route('cashier.payment', $student)
            ->with('success', $result['message']);
    }

    /**
     * Bulk confirm downpayments with one plan + amount (spec:
     * payment-access.md §4.1). Each file goes through the single-file
     * gates; incomplete files stay behind and are reported per file.
     * No confirmation emails in bulk — receipts print from the payment
     * screen, and the approval notice (the required email) follows at
     * full approval.
     */
    public function bulkConfirmDownpayments(BulkDownpaymentRequest $request)
    {
        $ids = array_filter($request->input('admission_ids', []));
        $confirmed = 0;
        $already = 0;
        $blocked = [];

        $admissions = Admission::whereIn('id', $ids)->with('student')->get();

        foreach ($admissions as $admission) {
            $student = $admission->student;
            if ($student === null) {
                $blocked[$admission->id] = 'No student linked to this application.';
                continue;
            }

            $result = DownpaymentService::confirm($student, [
                'payment_plan' => $request->input('payment_plan'),
                'amount_paid' => $request->input('amount_each'),
                'discount_type' => null,
                'discount_amount' => 0,
                'ar_number' => null,
            ], (int) auth()->id(), (string) auth()->user()->name);

            if ($result['confirmed']) {
                if ($result['already']) {
                    $already++;
                } else {
                    $confirmed++;
                }
            } else {
                $blocked[$admission->id] = $result['error'];
            }
        }

        if ($confirmed === 0 && $already === 0) {
            $firstBlocker = $blocked !== [] ? ' First blocker: ' . reset($blocked) : '';

            return redirect()->route('cashier.payments')
                ->with('error', 'Nothing was confirmed.' . $firstBlocker);
        }

        $message = $confirmed . ' downpayment(s) confirmed.';
        if ($already > 0) {
            $message .= ' ' . $already . ' already had receipts (no new charge).';
        }
        if ($blocked !== []) {
            $message .= ' ' . count($blocked) . ' stayed behind. First blocker: ' . reset($blocked);
        }

        return redirect()->route('cashier.payments')->with('success', $message);
    }

    public function printReceipt(Payment $payment)
    {
        $payment->load([
            'ledger.student.enrollments.section',
            'ledger.ledgerLines',
            'libraryTransactions.book',
            'cashier',
        ]);

        $student = $payment->ledger->student;
        $enrollment = $student->enrollments->where('status', 'Active')->sortByDesc('id')->first();

        $previousPayments = $payment->ledger->payments()
            ->where('id', '<', $payment->id)
            ->sum('amount_paid');

        $balanceAfter = max(0, $payment->ledger->total_assessed - $previousPayments - $payment->ledger->discount_applied - $payment->amount_paid);

        return view('portal.cashier.partials.receipt-print', compact('payment', 'student', 'enrollment', 'previousPayments', 'balanceAfter'));
    }

    public function studentFinancial(Request $request, Student $student, FeeAssessmentService $fees)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $student->load([
            'user',
            'enrollments' => fn($q) => $q->latest(),
            'enrollments.section',
            'ledger.payments.cashier',
            'ledger.ledgerLines',
            'admissions',
        ]);

        $enrollment = $student->enrollments->where('status', 'Active')->sortByDesc('id')->first();
        // Same admission fallback as showPayment: forwarded files have no
        // enrollment yet, so the financial view shows the application
        // grade/year instead of N/A (spec: forwarded-new-account-display).
        $latestAdmission = $student->admissions->sortByDesc('id')->first();
        $displayGrade = $enrollment?->section?->grade_level ?? $latestAdmission?->grade_level;
        $displayYear = $enrollment?->school_year ?? $latestAdmission?->school_year ?? active_school_year();
        $feeSchedules = $enrollment ? FeeSchedule::where('grade_level', $enrollment->section->grade_level)
            ->where('school_year', $enrollment->school_year)
            ->orderBy('term')
            ->get() : collect();

        $libraryFees = \App\Models\LibraryTransaction::with('book')->where('student_id', $student->id)->where(function($q){$q->where('fees_assessed', true)->orWhere('total_fees','>',0);})->orderBy('borrow_date','desc')->get();
        $libraryTotal = $libraryFees->sum('total_fees');

        $allPayments = $student->ledger?->payments()->latest('payment_date')->get() ?? collect();

        $selectedYear = $request->get('payment_year', 'all');
        if ($selectedYear !== 'all') {
            $payments = $allPayments->filter(fn($p) => \Carbon\Carbon::parse($p->payment_date)->format('Y') === $selectedYear);
        } else {
            $payments = $allPayments;
        }

        $paymentYears = $allPayments->map(fn($p) => \Carbon\Carbon::parse($p->payment_date)->format('Y'))->unique()->sortDesc()->values()->all();

        // Graduation-fee assignments for this student — Cashier marks paid here.
        $gradFeeAssignments = \App\Models\StudentGraduationFee::with('graduationFee')
            ->where('student_id', $student->id)
            ->orderByDesc('id')
            ->get();

        // Spec cashier-fee-rules.md: status-first financial view.
        $ledgerLines = $student->ledger?->ledgerLines ?? collect();
        $feeStatus = ($student->ledger && $ledgerLines->isNotEmpty())
            ? $fees->statusFor($student->ledger)
            : null;

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.cashier.partials.student-financial-results', compact('student', 'enrollment', 'feeSchedules', 'payments', 'paymentYears', 'selectedYear', 'libraryFees', 'libraryTotal', 'gradFeeAssignments', 'ledgerLines', 'feeStatus', 'displayGrade', 'displayYear'))->render(),
            ]);
        }

        return view('portal.cashier.student-financial', compact('student', 'enrollment', 'feeSchedules', 'payments', 'paymentYears', 'selectedYear', 'libraryFees', 'libraryTotal', 'gradFeeAssignments', 'ledgerLines', 'feeStatus', 'displayGrade', 'displayYear'));
    }

    public function collectionsReport(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        $payments = Payment::with('ledger.student', 'cashier')
            ->whereBetween('payment_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->orderBy('payment_date')
            ->get();

        $totalCollected = $payments->sum('amount_paid');
        $receiptCount = $payments->count();
        $byPlan = $payments->groupBy(fn($p) => $p->ledger->payment_plan ?? 'Unknown')
            ->map(fn($group) => ['count' => $group->count(), 'total' => $group->sum('amount_paid')]);

        $dailyBreakdown = $payments->groupBy(fn($p) => \Carbon\Carbon::parse($p->payment_date)->format('Y-m-d'))
            ->map(fn($group) => [
                'date' => $group->first()->payment_date,
                'count' => $group->count(),
                'total' => $group->sum('amount_paid'),
            ])
            ->sortBy('date')
            ->values()
            ->all();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.cashier.partials.collections-report-results', compact('payments', 'dailyBreakdown', 'totalCollected', 'receiptCount', 'byPlan', 'dateFrom', 'dateTo'))->render(),
            ]);
        }

        return view('portal.cashier.collections-report', compact('payments', 'totalCollected', 'receiptCount', 'byPlan', 'dateFrom', 'dateTo', 'dailyBreakdown'));
    }

    public function receivablesReport(Request $request) {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $receivables = \App\Models\StudentLedger::with(['student', 'payments' => fn($q) => $q->orderByDesc('payment_date')->orderByDesc('id')])->where('balance','>',0)->orderByDesc('balance')->get();
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        if ($dateFrom || $dateTo) {
            $receivables = $receivables->filter(fn($l) => $l->payments->isEmpty() || (($d = $l->payments->first()?->payment_date?->format('Y-m-d')) && (!$dateFrom || $d >= $dateFrom) && (!$dateTo || $d <= $dateTo)))->values();
        }
        $dailyBreakdown = $receivables->filter(fn($l) => $l->payments->isNotEmpty())->groupBy(fn($l) => $l->payments->first()->payment_date->format('Y-m-d'))->map(fn($g, $date) => ['date' => $date, 'count' => $g->count(), 'total' => $g->sum('balance')])->sortKeys()->values();
        $unpaidDues = $receivables->filter(fn($l) => $l->payments->isEmpty());
        $unpaid = ['count' => $unpaidDues->count(), 'total' => $unpaidDues->sum('balance')];
        $byPlan = $receivables->groupBy(fn($l) => $l->payment_plan ?? 'N/A')->map(fn($g) => ['count' => $g->count(), 'total' => $g->sum('balance')]);
        $totalReceivable = $receivables->sum('balance');
        $countReceivable = $receivables->count();
        if ($isAjax) return response()->json(['html'=>view('portal.cashier.partials.receivables-results', compact('receivables','byPlan','dailyBreakdown','unpaid','totalReceivable','countReceivable'))->render()]);
        return view('portal.cashier.reports', compact('receivables','byPlan','dailyBreakdown','unpaid','totalReceivable','countReceivable'));
    }
    public function reports(Request $request) {
        // just show the tabbed wrapper, data loaded via AJAX for each tab
        return view('portal.cashier.reports');
    }

    public function collectionsReportExport(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        $payments = Payment::with('ledger.student', 'cashier')
            ->whereBetween('payment_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->orderBy('payment_date')
            ->get();

        $filename = "collections-{$dateFrom}-to-{$dateTo}.csv";

        log_activity(Payment::class, 'Exported', auth()->user()->name . ' exported the collections report CSV (' . $payments->count() . ' payments, ' . $dateFrom . ' to ' . $dateTo . ').');

        return response()->stream(function () use ($payments, $filename) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Date', 'Student', 'Number', 'LRN', 'AR No.', 'Cashier', 'Amount']);
            foreach ($payments as $p) {
                fputcsv($fh, [
                    $p->payment_date->format('Y-m-d'),
                    ($p->ledger?->student?->first_name ?? '') . ' ' . ($p->ledger?->student?->last_name ?? ''),
                    $p->ledger?->student?->student_number ?? '',
                    $p->ledger?->student?->legacy_lrn ?? '',
                    $p->ar_number ?? '',
                    $p->cashier?->name ?? '',
                    $p->amount_paid,
                ]);
            }
            fclose($fh);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function receivablesReportExport(Request $request)
    {
        $ledgers = \App\Models\StudentLedger::with(['student', 'payments' => fn($q) => $q->orderByDesc('payment_date')->orderByDesc('id')])
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->get();
        $dateFrom = $request->date_from;
        $dateTo = $request->date_to;
        if ($dateFrom || $dateTo) {
            $ledgers = $ledgers->filter(fn($l) => $l->payments->isEmpty() || (($d = $l->payments->first()?->payment_date?->format('Y-m-d')) && (!$dateFrom || $d >= $dateFrom) && (!$dateTo || $d <= $dateTo)))->values();
        }

        $total = $ledgers->sum('balance');
        $filename = 'receivables-' . now()->format('Y-m-d') . '.csv';

        log_activity(\App\Models\StudentLedger::class, 'Exported', auth()->user()->name . ' exported the receivables report CSV (' . $ledgers->count() . ' student(s), total ₱' . number_format($total, 2) . ').');

        return response()->stream(function () use ($ledgers, $total) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Date', 'Student', 'Number', 'LRN', 'Balance']);
            foreach ($ledgers as $ledger) {
                $student = $ledger->student;
                $pay = $ledger->payments->first();
                fputcsv($fh, [
                    $pay?->payment_date?->format('Y-m-d') ?? '',
                    trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? '')),
                    $student?->student_number ?? '',
                    $student?->legacy_lrn ?? '',
                    $ledger->balance,
                ]);
            }
            fputcsv($fh, ['', 'TOTAL', '', '', $total]);
            fclose($fh);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function discounts(Request $request)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');
        $query = StudentLedger::with('student.user', 'student.enrollments.section')
            ->whereHas('student.enrollments', function ($q) {
                $q->where('status', 'Active')->where('school_year', active_school_year());
            });

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) ILIKE ?", ["%{$search}%"])
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('email', 'ilike', "%{$search}%");
                    });
            });
        }

        $ledgers = $query->orderBy('id')->paginate(20)->withQueryString();

        $discountTypes = [
            'honor' => 'Honor',
            'sibling' => 'Sibling',
            'esc' => 'ESC Grant',
            'other' => 'Other',
            '' => 'None',
        ];

        // Directress-approved requests awaiting Cashier application (two-step discounts).
        $approvedRequests = \App\Models\DiscountRequest::with('ledger.student.user', 'ledger.student.enrollments.section', 'requester', 'reviewer')
            ->where('status', \App\Models\DiscountRequest::STATUS_APPROVED)
            ->latest()
            ->get();

        // Ledgers that already have an open (pending/approved) request, to shape row actions.
        $openRequestLedgerIds = \App\Models\DiscountRequest::whereIn('status', [\App\Models\DiscountRequest::STATUS_PENDING, \App\Models\DiscountRequest::STATUS_APPROVED])
            ->pluck('student_ledger_id')
            ->flip();

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.cashier.partials.discounts-results', compact('ledgers', 'discountTypes', 'approvedRequests', 'openRequestLedgerIds'))->render(),
            ]);
        }

        return view('portal.cashier.discounts', compact('ledgers', 'discountTypes', 'approvedRequests', 'openRequestLedgerIds'));
    }

    /**
     * Requests hub — Discounts / Discount Requests / Refunds on one page
     * (spec: cashier-requests-hub.md). Composition only: the same queries as
     * the three standalone pages, so lists and badge counts can never drift
     * apart. Standalone routes, registrar path, and throttle coverage stay
     * valid by being untouched. Tab markup below copies the standalone views
     * verbatim (same variable names), so future diffs stay trivial.
     */
    public function requests()
    {
        // Discounts tab — apply queue, same data as discounts().
        $approvedRequests = \App\Models\DiscountRequest::with('ledger.student.user', 'ledger.student.enrollments.section', 'requester', 'reviewer')
            ->where('status', \App\Models\DiscountRequest::STATUS_APPROVED)
            ->latest()
            ->get();

        // Requests tab — same data as DiscountRequestController@index.
        $requests = \App\Models\DiscountRequest::with(['ledger.student.user', 'ledger.student.enrollments.section', 'requester', 'reviewer'])
            ->latest()
            ->paginate(20)
            ->withQueryString();
        $ledgers = StudentLedger::with('student.user')
            ->whereHas('student.enrollments', function ($q) {
                $q->where('status', 'Active')->where('school_year', active_school_year());
            })
            ->orderBy('id')
            ->get();
        $discountTypes = \App\Models\DiscountRequest::TYPES;

        // Refunds tab — same data as refunds().
        $pending = Withdrawal::with('student.user', 'enrollment.section', 'processor')
            ->where('status', 'Approved')
            ->where('refund_amount', '>', 0)
            ->whereNull('refund_processed_at')
            ->latest()
            ->get();
        $released = Withdrawal::with('student.user')
            ->where('status', 'Approved')
            ->whereNotNull('refund_processed_at')
            ->latest('refund_processed_at')
            ->take(50)
            ->get();

        // Badges — counted from the same collections above, so they always match.
        $badgeCounts = [
            'discounts' => $approvedRequests->count(),
            'requests' => \App\Models\DiscountRequest::where('status', \App\Models\DiscountRequest::STATUS_PENDING)->count(),
            'refunds' => $pending->count(),
        ];

        return view('portal.cashier.requests', compact('approvedRequests', 'requests', 'ledgers', 'discountTypes', 'pending', 'released', 'badgeCounts'));
    }

    /**
     * Apply a Directress-approved discount request. Cashier never grants directly —
     * every discount on this page comes from an approved request.
     */
    public function applyDiscount(\App\Models\DiscountRequest $discountRequest)
    {
        // Backlog fallback (spec: cashier-discount-auto-apply.md): approvals now
        // auto-apply, so this survives only for pre-change approved-but-unapplied
        // requests. Already-applied reports info — never a second posting.
        if ($discountRequest->status === \App\Models\DiscountRequest::STATUS_APPLIED) {
            return back()->with('info', 'Discount already applied.');
        }

        if ($discountRequest->status !== \App\Models\DiscountRequest::STATUS_APPROVED) {
            return back()->with('error', 'Only Directress-approved requests can be applied.');
        }

        $ledger = $discountRequest->ledger;
        $discountAmount = min((float) $discountRequest->discount_amount, (float) $ledger->total_assessed);

        $ledger->update([
            'discount_type' => $discountRequest->discount_type,
            'discount_applied' => $discountAmount,
            'balance' => max(0, $ledger->total_assessed - $ledger->total_paid - $discountAmount),
        ]);
        \App\Services\LedgerService::refreshClearance($ledger->fresh());

        $discountRequest->update([
            'status' => \App\Models\DiscountRequest::STATUS_APPLIED,
            'applied_at' => now(),
        ]);

        $student = $ledger->student;
        $studentName = $student ? $student->first_name . ' ' . $student->last_name : 'Student #' . $ledger->student_id;
        $reviewer = $discountRequest->reviewer?->name ?? 'Directress';
        log_activity($student ?? $ledger, 'Discount Applied', auth()->user()->name . " (Cashier) applied the approved {$discountRequest->discount_type} discount (₱" . number_format($discountAmount, 2) . ") for {$studentName}. Approved by {$reviewer}.");

        return back()->with('success', 'Discount applied for ' . $studentName . '.');
    }

    /**
     * Graduation-fee paid marking — Cashier only (moved from Directress:
     * setting the price and marking paid must not be the same person).
     */
    public function graduationFeesTogglePaid(\App\Models\StudentGraduationFee $assignment)
    {
        $assignment->paid = !$assignment->paid;
        $assignment->save();

        log_activity($assignment, 'Graduation Fee Payment Toggled', auth()->user()->name . ' (Cashier) toggled paid status for student #' . $assignment->student_id . ' on "' . $assignment->graduationFee->name . '".');

        if (request()->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Payment status updated.']);
        }

        return back()->with('success', 'Payment status updated.');
    }

    /**
     * Refund payouts awaiting release (role reform Phase 4d).
     * Registrar approves the withdrawal; only the Cashier moves money.
     */
    public function refunds()
    {
        $pending = Withdrawal::with('student.user', 'enrollment.section', 'processor')
            ->where('status', 'Approved')
            ->where('refund_amount', '>', 0)
            ->whereNull('refund_processed_at')
            ->latest()
            ->get();

        $released = Withdrawal::with('student.user')
            ->where('status', 'Approved')
            ->whereNotNull('refund_processed_at')
            ->latest('refund_processed_at')
            ->take(50)
            ->get();

        return view('portal.cashier.refunds.index', compact('pending', 'released'));
    }

    public function releasePayout(Withdrawal $withdrawal)
    {
        if ($withdrawal->status !== 'Approved' || $withdrawal->refund_processed_at || $withdrawal->refund_amount <= 0) {
            return back()->with('error', 'This refund cannot be released (already released or nothing due).');
        }

        $student = $withdrawal->student;
        $ledger = $student->ledger;
        $refundAmount = (float) $withdrawal->refund_amount;

        DB::transaction(function () use ($withdrawal, $student, $ledger, $refundAmount) {
            if ($ledger) {
                $ledger->total_paid = max(0, $ledger->total_paid - $refundAmount);
                $ledger->balance = max(0, $ledger->total_assessed - $ledger->total_paid - $ledger->discount_applied);
                $ledger->save();
                \App\Services\LedgerService::refreshClearance($ledger);

                $receiptNumber = 'REF-' . now()->format('Ymd') . '-' . str_pad($student->id, 5, '0', STR_PAD_LEFT);

                $ledger->payments()->create([
                    'cashier_id' => auth()->id(),
                    'amount_paid' => -$refundAmount,
                    'receipt_number' => $receiptNumber,
                    'payment_date' => now(),
                ]);
            }

            $withdrawal->refund_processed_at = now();
            $withdrawal->refund_released_by = auth()->id();
            $withdrawal->save();

            log_activity($student, 'Refund Released', auth()->user()->name . " (Cashier) released refund payout ₱" . number_format($refundAmount, 2) . " for {$student->first_name} {$student->last_name}.");
        });

        return back()->with('success', 'Refund payout of ₱' . number_format($refundAmount, 2) . ' released for ' . $student->first_name . ' ' . $student->last_name . '.');
    }

    /**
     * Void an erroneous collection: posts an offsetting reversal (VOID- receipt)
     * and recomputes the ledger. The original row is kept for the audit trail.
     */
    public function voidPayment(Request $request, Payment $payment)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:500']);

        if ($payment->amount_paid <= 0 || !str_starts_with((string) $payment->receipt_number, 'RCP-')) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Only collected payments (RCP- receipts) can be voided.'], 422);
            }

            return back()->with('error', 'Only collected payments (RCP- receipts) can be voided.');
        }

        $alreadyVoided = Payment::where('ledger_id', $payment->ledger_id)
            ->where('receipt_number', 'like', 'VOID-' . $payment->receipt_number . '%')
            ->exists();
        if ($alreadyVoided) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'This payment has already been voided.'], 422);
            }

            return back()->with('error', 'This payment has already been voided.');
        }

        $ledger = $payment->ledger;

        DB::transaction(function () use ($payment, $ledger, $data) {
            $reversal = $ledger->payments()->create([
                'cashier_id' => auth()->id(),
                'amount_paid' => -$payment->amount_paid,
                'receipt_number' => 'VOID-' . $payment->receipt_number,
                'ar_number' => $payment->ar_number,
                'payment_date' => now(),
            ]);

            $ledger->total_paid = max(0, $ledger->total_paid - $payment->amount_paid);
            $ledger->balance = max(0, $ledger->total_assessed - $ledger->total_paid - $ledger->discount_applied);
            $ledger->save();
            \App\Services\LedgerService::refreshClearance($ledger);

            $student = $ledger->student;
            $name = $student ? $student->first_name . ' ' . $student->last_name : 'Student #' . $ledger->student_id;
            log_activity($student ?? $ledger, 'Payment Voided', auth()->user()->name . " (Cashier) voided payment {$payment->receipt_number} (₱" . number_format($payment->amount_paid, 2) . ") for {$name}. Reason: {$data['reason']}");

            // Voiding the downpayment receipt before approval returns the
            // file to Awaiting Downpayment (spec: payment-access.md §6 edge).
            DownpaymentService::dropBackOnVoid($payment, auth()->user()->name . ' (Cashier)');
        });

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Payment ' . $payment->receipt_number . ' voided with an offsetting reversal.']);
        }

        return back()->with('success', 'Payment ' . $payment->receipt_number . ' voided with an offsetting reversal.');
    }
}
