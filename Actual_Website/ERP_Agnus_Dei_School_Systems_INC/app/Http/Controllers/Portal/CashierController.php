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

class CashierController extends Controller
{
    public function index()
    {
        $pendingPayments = Student::where('status', 'enrolled')
            ->whereDoesntHave('ledger', function ($q) {
                $q->where('clearance_status', 'Cleared');
            })
            ->whereHas('enrollments', function ($q) {
                $q->where('status', 'Active')->where('school_year', active_school_year());
            })
            ->with(['user', 'enrollments' => function ($q) {
                $q->where('status', 'Active')->where('school_year', active_school_year())->latest();
            }, 'ledger'])
            ->get();

        $todayCollection = Payment::whereDate('payment_date', today())->sum('amount_paid');
        $receiptsToday = Payment::whereDate('payment_date', today())->count();

        return view('portal.cashier.dashboard', compact('pendingPayments', 'todayCollection', 'receiptsToday'));
    }

    public function showPayment(Student $student)
    {
        $student->load('user', 'enrollments.section', 'ledger');
        $enrollment = $student->enrollments()->where('status', 'Active')->latest()->first();
        $feeSchedules = $enrollment ? FeeSchedule::where('grade_level', $enrollment->section->grade_level)
            ->where('school_year', $enrollment->school_year)
            ->orderBy('term')
            ->get() : collect();

        $hasScholarship = $student->scholarship ?? false;
        $isSHS = $enrollment && in_array($enrollment->section->grade_level, ['Grade 11', 'Grade 12']);

        if ($hasScholarship && $isSHS) {
            $feeSchedules = $feeSchedules->map(function ($fs) {
                $fs->tuition_fee = 0;
                return $fs;
            });
        }

        $totalTuition = $feeSchedules->sum('tuition_fee');
        $totalMisc = $feeSchedules->sum('misc_fee');
        $totalAssessed = $totalTuition + $totalMisc;

        $discountTypes = [
            'honor' => 'Honor',
            'sibling' => 'Sibling',
            'esc' => 'ESC Grant',
            'other' => 'Other',
        ];

        $discountApplied = $student->ledger?->discount_applied ?? 0;

        return view('portal.cashier.payment', compact('student', 'enrollment', 'feeSchedules', 'totalTuition', 'totalMisc', 'totalAssessed', 'discountTypes', 'discountApplied', 'hasScholarship', 'isSHS'));
    }

    public function processPayment(Request $request, Student $student)
    {
        $data = $request->validate([
            'payment_plan' => 'required|in:installment,full',
            'amount_paid' => 'required|numeric|min:1',
            'discount_type' => 'nullable|in:honor,sibling,esc,other',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        $enrollment = $student->enrollments()->with('section')->where('status', 'Active')->latest()->firstOrFail();
        $feeSchedules = FeeSchedule::where('grade_level', $enrollment->section->grade_level)
            ->where('school_year', $enrollment->school_year)
            ->get();

        $hasScholarship = $student->scholarship ?? false;
        $isSHS = in_array($enrollment->section->grade_level, ['Grade 11', 'Grade 12']);

        if ($hasScholarship && $isSHS) {
            $totalAssessed = $feeSchedules->sum('misc_fee');
        } else {
            $totalAssessed = $feeSchedules->sum('tuition_fee') + $feeSchedules->sum('misc_fee');
        }
        $discountAmount = $data['discount_amount'] ?? 0;

        try {
            DB::transaction(function () use ($student, $data, $totalAssessed, $discountAmount) {
                $ledger = $student->ledger;

                if (!$ledger) {
                    $ledger = StudentLedger::create([
                        'student_id' => $student->id,
                        'payment_plan' => $data['payment_plan'],
                        'total_assessed' => $totalAssessed,
                        'discount_applied' => $discountAmount,
                        'total_paid' => $data['amount_paid'],
                        'balance' => max(0, $totalAssessed - $data['amount_paid'] - $discountAmount),
                        'clearance_status' => ($data['payment_plan'] === 'full' && $data['amount_paid'] >= $totalAssessed - $discountAmount) ? 'Cleared' : 'Pending',
                    ]);
                } else {
                    $ledger->payment_plan = $data['payment_plan'];
                    $ledger->total_assessed = $totalAssessed;
                    $ledger->discount_applied = $discountAmount;
                    $ledger->total_paid += $data['amount_paid'];
                    $ledger->balance = max(0, $ledger->total_assessed - $ledger->total_paid - $ledger->discount_applied);
                    if ($data['payment_plan'] === 'full' && $ledger->balance == 0) {
                        $ledger->clearance_status = 'Cleared';
                    }
                    $ledger->save();
                }

                $receiptNumber = 'RCP-' . now()->format('Ymd') . '-' . str_pad(Payment::whereDate('payment_date', today())->count() + 1, 4, '0', STR_PAD_LEFT);

                Payment::create([
                    'ledger_id' => $ledger->id,
                    'cashier_id' => auth()->id(),
                    'amount_paid' => $data['amount_paid'],
                    'receipt_number' => $receiptNumber,
                    'payment_date' => now(),
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Payment processing failed: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'amount' => $data['amount_paid'],
            ]);
            return redirect()->route('cashier.payment', $student)
                ->with('error', 'Payment processing failed. Please try again.');
        }

        return redirect()->route('cashier.dashboard')
            ->with('success', 'Payment of ₱' . number_format($data['amount_paid'], 2) . ' processed for ' . $student->first_name . ' ' . $student->last_name . '.');
    }
}
