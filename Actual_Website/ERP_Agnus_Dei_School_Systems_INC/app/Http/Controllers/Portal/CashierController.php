<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ProjectionFilterRequest;
use App\Mail\PaymentConfirmationMail;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentLedger;
use App\Models\Withdrawal;
use App\Services\CashierProjectionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        // Receivables are a real position to date, so the card never advertises a day
        // that has not happened yet. A school year runs to May, so the default period
        // ends in the future for most of the year while the figure is already "now".
        $receivablesAsOfLabel = $periodTo->copy()->min(Carbon::today())->format('M j, Y');

        return view('portal.cashier.projections', array_merge($series, [
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
            $students = Student::where('status', 'enrolled')
                ->where(function ($q) use ($search) {
                    $q->where('first_name', 'ilike', "%{$search}%")
                        ->orWhere('last_name', 'ilike', "%{$search}%")
                        ->orWhere('student_number', 'ilike', "%{$search}%")
                        ->orWhere('legacy_lrn', 'ilike', "%{$search}%");
                })
            ->with(['user', 'enrollments.section', 'ledger'])
            ->limit(20)
                ->get()
                ->map(function ($student) use ($schoolYear) {
                    $enrollment = $student->enrollments->where('status', 'Active')->sortByDesc('id')->first();
                    $gradeLevel = $enrollment?->section?->grade_level;

                    $totalAssessed = 0;
                    if ($gradeLevel) {
                        $feeSchedules = FeeSchedule::where('grade_level', $gradeLevel)
                            ->where('school_year', $schoolYear)
                            ->get();
                        $totalAssessed = $feeSchedules->sum('tuition_fee') + $feeSchedules->sum('misc_fee');
                    }

                    $totalPaid = $student->ledger?->total_paid ?? 0;
                    $discountApplied = $student->ledger?->discount_applied ?? 0;
                    $student->computed_balance = max(0, $totalAssessed - $totalPaid - $discountApplied);
                    return $student;
                });
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

        $students = Student::where('status', 'enrolled')
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('student_number', 'ilike', "%{$search}%")
                    ->orWhere('legacy_lrn', 'ilike', "%{$search}%");
            })
            ->with(['enrollments.section', 'ledger'])
            ->limit(10)
            ->get()
            ->map(function ($student) use ($schoolYear) {
                $enrollment = $student->enrollments->where('status', 'Active')->sortByDesc('id')->first();
                $gradeLevel = $enrollment?->section?->grade_level;

                $totalAssessed = 0;
                if ($gradeLevel) {
                    $feeSchedules = FeeSchedule::where('grade_level', $gradeLevel)
                        ->where('school_year', $schoolYear)
                        ->get();
                    $totalAssessed = $feeSchedules->sum('tuition_fee') + $feeSchedules->sum('misc_fee');
                }

                $totalPaid = $student->ledger?->total_paid ?? 0;
                $discountApplied = $student->ledger?->discount_applied ?? 0;
                $balance = max(0, $totalAssessed - $totalPaid - $discountApplied);

                $student->computed_balance = $balance;
                return $student;
            });

        return response()->json($students);
    }

    public function showPayment(Student $student)
    {
        $student->load('user', 'enrollments.section', 'ledger', 'admissions');
        $enrollment = $student->enrollments->where('status', 'Active')->sortByDesc('id')->first();
        $feeSchedules = $enrollment ? FeeSchedule::where('grade_level', $enrollment->section->grade_level)
            ->where('school_year', $enrollment->school_year)
            ->orderBy('term')
            ->get() : collect();

        $hasScholarship = $student->scholarship ?? false;
        $isSHS = $enrollment && in_array($enrollment->section->grade_level, ['Grade 11', 'Grade 12']);

        $admission = $student->admissions()->where('school_year', $enrollment?->school_year)->latest()->first();
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

        $nextArNumber = (new Payment())->generateArNumber();

        return view('portal.cashier.payment', compact('student', 'enrollment', 'feeSchedules', 'totalTuition', 'totalMisc', 'totalAssessed', 'discountTypes', 'discountApplied', 'hasScholarship', 'isSHS', 'nextArNumber', 'autoDiscountType', 'autoDiscountAmount', 'admissionType'));
    }

    public function processPayment(Request $request, Student $student)
    {
        $data = $request->validate([
            'payment_plan' => 'required|in:installment,full',
            'amount_paid' => 'required|numeric|min:1',
            'discount_type' => 'nullable|in:honor,sibling,esc,other',
            'discount_amount' => 'nullable|numeric|min:0',
            'ar_number' => 'nullable|string|max:30',
            'receipt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $enrollment = $student->enrollments()->with('section')->where('status', 'Active')->latest()->firstOrFail();

        // Locked school years are frozen — no payments into them.
        if (school_year_locked($enrollment->school_year)) {
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
            $totalAssessed = $feeSchedules->sum('misc_fee');
            $autoDiscountType = 'esc';
            $autoDiscountAmount = $feeSchedules->sum('tuition_fee');
        } else {
            $totalTuition = $feeSchedules->sum('tuition_fee');
            $totalAssessed = $totalTuition + $feeSchedules->sum('misc_fee');
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

        try {
            DB::transaction(function () use ($student, $data, $totalAssessed, $totalTuition, $discountAmount, $receiptFilePath, $hasScholarship, $isSHS, $autoDiscountType) {
                $ledger = $student->ledger;

                if (!$ledger) {
                    $isFirstPayment = true;
                    $paymentPlan = $data['payment_plan'];

                    if ($discountAmount <= 0 && $autoDiscountType) {
                        $discountType = $autoDiscountType;
                    } else {
                        $discountType = $data['discount_type'] ?? null;
                    }

                    $newBalance = max(0, $totalAssessed - $data['amount_paid'] - $discountAmount);
                    $totalPaid = $data['amount_paid'];
                    $ledger = StudentLedger::create([
                        'student_id' => $student->id,
                        'payment_plan' => $paymentPlan,
                        'total_assessed' => $totalAssessed,
                        'discount_type' => $discountType,
                        'discount_applied' => $discountAmount,
                        'total_paid' => $totalPaid,
                        'balance' => $newBalance,
                        'clearance_status' => 'Pending',
                    ]);
                } else {
                    $ledger->total_assessed = $totalAssessed;

                    if ($ledger->discount_applied <= 0 && $discountAmount > 0) {
                        $ledger->discount_applied = $discountAmount;
                        if ($autoDiscountType) {
                            $ledger->discount_type = $autoDiscountType;
                        } elseif (isset($data['discount_type']) && $data['discount_type']) {
                            $ledger->discount_type = $data['discount_type'];
                        }
                    }
                }

                // First payment on a just-created ledger is already recorded by
                // create() above — adding it again would double total_paid.
                if (empty($isFirstPayment)) {
                    $ledger->total_paid += $data['amount_paid'];
                }
                $ledger->balance = max(0, $ledger->total_assessed - $ledger->total_paid - $ledger->discount_applied);

                $ledger->save();
                \App\Services\LedgerService::refreshClearance($ledger);

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

                Payment::create([
                    'ledger_id' => $ledger->id,
                    'cashier_id' => auth()->id(),
                    'amount_paid' => $data['amount_paid'],
                    'receipt_number' => $receiptNumber,
                    'ar_number' => $arNumber,
                    'receipt_file_path' => $receiptFilePath,
                    'payment_date' => now(),
                ]);

                log_activity($student, 'Payment', "Payment of ₱" . number_format($data['amount_paid'], 2) . " processed (Receipt: {$receiptNumber}, AR: {$arNumber})");
            });
        } catch (\Exception $e) {
            Log::error('Payment processing failed: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'amount' => $data['amount_paid'],
            ]);
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
                Mail::to($email)->send(new PaymentConfirmationMail($lastPayment));
            } catch (\Exception $e) {
                Log::warning('Failed to send payment confirmation email: ' . $e->getMessage(), [
                    'student_id' => $student->id,
                    'payment_id' => $lastPayment->id,
                ]);
            }
        }

        return redirect()->route('cashier.payment', $student)
            ->with('payment_success', [
                'amount' => $data['amount_paid'],
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'receipt_number' => $lastPayment?->receipt_number ?? '',
                'payment_id' => $lastPayment?->id ?? '',
            ]);
    }

    public function printReceipt(Payment $payment)
    {
        $payment->load([
            'ledger.student.enrollments.section',
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

    public function studentFinancial(Request $request, Student $student)
    {
        $isAjax = $request->boolean('ajax');
        $request->query->remove('ajax');

        $student->load([
            'user',
            'enrollments' => fn($q) => $q->latest(),
            'enrollments.section',
            'ledger.payments.cashier',
        ]);

        $enrollment = $student->enrollments->where('status', 'Active')->sortByDesc('id')->first();
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

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.cashier.partials.student-financial-results', compact('student', 'enrollment', 'feeSchedules', 'payments', 'paymentYears', 'selectedYear', 'libraryFees', 'libraryTotal', 'gradFeeAssignments'))->render(),
            ]);
        }

        return view('portal.cashier.student-financial', compact('student', 'enrollment', 'feeSchedules', 'payments', 'paymentYears', 'selectedYear', 'libraryFees', 'libraryTotal', 'gradFeeAssignments'));
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
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($q2) use ($search) {
                        $q2->where('email', 'like', "%{$search}%");
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
     * Apply a Directress-approved discount request. Cashier never grants directly —
     * every discount on this page comes from an approved request.
     */
    public function applyDiscount(\App\Models\DiscountRequest $discountRequest)
    {
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
            return back()->with('error', 'Only collected payments (RCP- receipts) can be voided.');
        }

        $alreadyVoided = Payment::where('ledger_id', $payment->ledger_id)
            ->where('receipt_number', 'like', 'VOID-' . $payment->receipt_number . '%')
            ->exists();
        if ($alreadyVoided) {
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
        });

        return back()->with('success', 'Payment ' . $payment->receipt_number . ' voided with an offsetting reversal.');
    }
}
