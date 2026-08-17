<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentLedger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CashierController extends Controller
{
    public function index()
    {
        $todayCollection = Payment::whereDate('payment_date', today())->sum('amount_paid');
        $receiptsToday = Payment::whereDate('payment_date', today())->count();

        return view('portal.cashier.dashboard', compact('todayCollection', 'receiptsToday'));
    }

    public function payments(Request $request)
    {
        $search = $request->input('search');
        $students = collect();

        if ($search && strlen($search) >= 2) {
            $students = Student::where('status', 'enrolled')
                ->whereHas('enrollments', function ($q) {
                    $q->where('status', 'Active')->where('school_year', active_school_year());
                })
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('student_number', 'ilike', "%{$search}%")
                    ->orWhere('legacy_lrn', 'ilike', "%{$search}%");
            })
            ->with(['user', 'enrollments.section', 'ledger'])
            ->limit(20)
                ->get()
                ->map(function ($student) {
                    $enrollment = $student->enrollments->where('status', 'Active')->first();
                    $gradeLevel = $enrollment?->section?->grade_level;
                    $schoolYear = $enrollment?->school_year;

                    $totalAssessed = 0;
                    if ($gradeLevel && $schoolYear) {
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

        return view('portal.cashier.payments', compact('students', 'search'));
    }

    public function searchStudents(Request $request)
    {
        $search = $request->search;

        if (strlen($search) < 2) {
            return response()->json([]);
        }

        $students = Student::where('status', 'enrolled')
            ->whereHas('enrollments', function ($q) {
                $q->where('status', 'Active')->where('school_year', active_school_year());
            })
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'ilike', "%{$search}%")
                    ->orWhere('last_name', 'ilike', "%{$search}%")
                    ->orWhere('student_number', 'ilike', "%{$search}%")
                    ->orWhere('legacy_lrn', 'ilike', "%{$search}%");
            })
            ->with(['enrollments.section', 'ledger'])
            ->limit(10)
            ->get()
            ->map(function ($student) {
                $enrollment = $student->enrollments->where('status', 'Active')->first();
                $gradeLevel = $enrollment?->section?->grade_level;
                $schoolYear = $enrollment?->school_year;

                $totalAssessed = 0;
                if ($gradeLevel && $schoolYear) {
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
        $enrollment = $student->enrollments()->where('status', 'Active')->latest()->first();
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

                    $ledger->total_paid += $data['amount_paid'];
                    $ledger->balance = max(0, $ledger->total_assessed - $ledger->total_paid - $ledger->discount_applied);

                    $ledger->save();
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

                $arNumber = $data['ar_number'] ?: (new Payment())->generateArNumber();

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
        $enrollment = $student->enrollments()->where('status', 'Active')->latest()->first();

        $previousPayments = $payment->ledger->payments()
            ->where('id', '<', $payment->id)
            ->sum('amount_paid');

        $balanceAfter = max(0, $payment->ledger->total_assessed - $previousPayments - $payment->ledger->discount_applied - $payment->amount_paid);

        return view('portal.cashier.partials.receipt-print', compact('payment', 'student', 'enrollment', 'previousPayments', 'balanceAfter'));
    }

    public function studentFinancial(Student $student)
    {
        $student->load([
            'user',
            'enrollments' => fn($q) => $q->where('status', 'Active')->latest(),
            'enrollments.section',
            'ledger.payments.cashier',
        ]);

        $enrollment = $student->enrollments()->where('status', 'Active')->latest()->first();
        $feeSchedules = $enrollment ? FeeSchedule::where('grade_level', $enrollment->section->grade_level)
            ->where('school_year', $enrollment->school_year)
            ->orderBy('term')
            ->get() : collect();

        $payments = $student->ledger?->payments()->latest('payment_date')->get() ?? collect();

        return view('portal.cashier.student-financial', compact('student', 'enrollment', 'feeSchedules', 'payments'));
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

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.cashier.partials.collections-report-results', compact('payments'))->render(),
            ]);
        }

        return view('portal.cashier.collections-report', compact('payments', 'totalCollected', 'receiptCount', 'byPlan', 'dateFrom', 'dateTo'));
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

        return response()->stream(function () use ($payments, $filename) {
            $fh = fopen('php://output', 'w');
            fputcsv($fh, ['Date', 'Student', 'Amount', 'Receipt No.', 'AR No.', 'Plan', 'Cashier']);
            foreach ($payments as $p) {
                fputcsv($fh, [
                    $p->payment_date->format('Y-m-d'),
                    ($p->ledger?->student?->first_name ?? '') . ' ' . ($p->ledger?->student?->last_name ?? ''),
                    $p->amount_paid,
                    $p->receipt_number,
                    $p->ar_number ?? '',
                    $p->ledger?->payment_plan ?? '',
                    $p->cashier?->name ?? '',
                ]);
            }
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

        if ($isAjax) {
            return response()->json([
                'html' => view('portal.cashier.partials.discounts-results', compact('ledgers', 'discountTypes'))->render(),
            ]);
        }

        return view('portal.cashier.discounts', compact('ledgers', 'discountTypes'));
    }

    public function updateDiscount(Request $request, StudentLedger $ledger)
    {
        $data = $request->validate([
            'discount_type' => 'required|in:honor,sibling,esc,other',
            'discount_amount' => 'required|numeric|min:0',
        ]);

        $discountAmount = min((float) $data['discount_amount'], $ledger->total_assessed);

        $ledger->update([
            'discount_type' => $data['discount_type'],
            'discount_applied' => $discountAmount,
            'balance' => max(0, $ledger->total_assessed - $ledger->total_paid - $discountAmount),
        ]);

        log_activity($ledger->student, 'Discount Updated', "Discount updated: {$data['discount_type']} — ₱" . number_format($discountAmount, 2));

        return back()->with('success', 'Discount updated for ' . $ledger->student->first_name . ' ' . $ledger->student->last_name . '.');
    }
}
