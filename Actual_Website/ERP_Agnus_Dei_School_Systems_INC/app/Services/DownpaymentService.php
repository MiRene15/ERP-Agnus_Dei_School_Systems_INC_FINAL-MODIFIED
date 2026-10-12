<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Admission;
use App\Models\FeeSchedule;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentLedger;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Cashier downpayment for forwarded (not yet enrolled) files
 * (spec: enrollment-payment-gating-payment-access.md).
 *
 * Confirm is receipt-first and idempotent: a file carrying a receipt
 * confirms exactly once — double-taps return the existing receipt
 * without charging again. The money math mirrors the enrolled path
 * (CashierController@processPayment): same floors, same ledger lines,
 * same allocation — only the grade/year source differs (admission
 * instead of enrollment).
 */
class DownpaymentService
{
    /**
     * @param array<string, mixed> $data validated ProcessCashierPaymentRequest data
     * @return array{confirmed: bool, already: bool, admission?: Admission, payment?: Payment, receipt?: string, credited?: float, message?: string, error?: string}
     */
    public static function confirm(Student $student, array $data, int $cashierId, string $cashierName): array
    {
        $admission = $student->admissions()
            ->where('status', AdmissionReviewService::STATUS_AWAITING_DOWNPAYMENT)
            ->latest()
            ->first();

        if ($admission === null) {
            return ['confirmed' => false, 'already' => false, 'error' => 'This account is not awaiting downpayment.'];
        }

        // Idempotency first: a receipt on file means money already changed
        // hands — return it, never charge twice (double-tap safe).
        if (! empty($admission->downpayment_receipt)) {
            $existing = Payment::where('receipt_number', $admission->downpayment_receipt)->latest()->first();

            return [
                'confirmed' => true,
                'already' => true,
                'admission' => $admission,
                'payment' => $existing,
                'receipt' => $admission->downpayment_receipt,
                'message' => 'Downpayment already confirmed (' . $admission->downpayment_receipt . '). No new charge made.',
            ];
        }

        if (school_year_locked($admission->school_year)) {
            return ['confirmed' => false, 'already' => false, 'error' => 'Cannot collect — school year ' . $admission->school_year . ' is locked.'];
        }

        $fees = app(FeeAssessmentService::class);
        $grade = (string) ($admission->grade_level ?? '');
        $year = (string) ($admission->school_year ?? '');
        $schedules = ($grade !== '' && $year !== '')
            ? FeeSchedule::where('grade_level', $grade)->where('school_year', $year)->get()
            : collect();

        if ($schedules->isEmpty()) {
            return ['confirmed' => false, 'already' => false, 'error' => 'No fee schedule for ' . ($grade !== '' ? $grade : 'this grade') . ' (' . ($year !== '' ? $year : 'this year') . '). Ask the Directress or Registrar to set fees first.'];
        }

        $path = $data['payment_plan'];
        $amount = round((float) $data['amount_paid'], 2);
        $isSHS = in_array($grade, ['Grade 11', 'Grade 12'], true);
        $admissionType = $admission->application_type ?? 'New';

        if (($student->scholarship ?? false) && $isSHS) {
            $totalTuition = 0.0;
            $split = $fees->splitSchedules($schedules);
            $totalMisc = $split['misc'];
            $miscItems = $split['items'];
            $totalAssessed = $totalMisc;
            $autoDiscountType = 'esc';
            $autoDiscountAmount = (float) $schedules->sum('tuition_fee');
        } else {
            $split = $fees->splitSchedules($schedules);
            $totalTuition = $split['tuition'];
            $totalMisc = $split['misc'];
            $miscItems = $split['items'];
            $totalAssessed = $totalTuition + $totalMisc;
            $autoDiscountType = $admissionType === 'Honor' ? 'honor' : ($admissionType === 'Sibling' ? 'sibling' : null);
            $autoDiscountAmount = 0.0;
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

        // Has-balance returnees carry the flag into this confirmation; the
        // Registrar sees it at full approval (clearance enforced there).
        $holds = HoldService::forStudent($student);
        $holdsNote = $holds !== [] ? ' Holds on file: ' . HoldService::blockingMessage($holds) : '';

        $payment = null;
        $creditedExcess = 0.0;
        $receiptNumber = '';

        try {
            DB::transaction(function () use ($student, $admission, $data, $path, $amount, $discountAmount, $autoDiscountType, $totalAssessed, $totalTuition, $totalMisc, $miscItems, $year, $cashierId, $cashierName, $fees, $holdsNote, &$payment, &$creditedExcess, &$receiptNumber): void {
                $syStart = (int) explode('-', $year)[0];
                $elapsed = Carbon::create($syStart, 6, 1)->startOfMonth()->diffInMonths(Carbon::now()->startOfMonth(), false);
                $remainingMonths = max(1, FeeAssessmentService::INSTALLMENTS - max(0, (int) $elapsed));
                $juneStart = Carbon::create($syStart, 6, 1)->startOfMonth();
                $assessStart = $juneStart->greaterThan(Carbon::now()->startOfMonth()) ? $juneStart : Carbon::now()->startOfMonth();

                $ledger = StudentLedger::where('student_id', $student->id)
                    ->where('school_year', $year)
                    ->latest()
                    ->first();

                if ($ledger === null) {
                    $ledger = StudentLedger::create([
                        'student_id' => $student->id,
                        'school_year' => $year,
                        'payment_plan' => $path,
                        'assessment_path' => $path,
                        'enrollment_fee' => $path === FeeAssessmentService::PATH_FULL ? 0 : FeeAssessmentService::ENROLLMENT_FEE,
                        'tuition_assessed' => $totalTuition,
                        'installment_count' => $path === FeeAssessmentService::PATH_INSTALLMENT ? $remainingMonths : 0,
                        'total_assessed' => $totalAssessed,
                        'discount_type' => ($discountAmount <= 0 && $autoDiscountType) ? $autoDiscountType : ($data['discount_type'] ?? null),
                        'discount_applied' => $discountAmount,
                        'total_paid' => 0,
                        'balance' => max(0, $totalAssessed - $discountAmount),
                        'clearance_status' => 'Pending',
                    ]);

                    $fees->assessLedger($ledger->refresh(), (float) $totalTuition, $year, $path, $remainingMonths, $assessStart, (float) $totalMisc, $miscItems ?? []);
                    $ledger = $ledger->refresh();
                } elseif ($ledger->ledgerLines()->exists() && $ledger->assessment_path && $ledger->assessment_path !== $path) {
                    $fees->switchPath($ledger, $path);
                    $ledger = $ledger->refresh();
                } elseif (! $ledger->ledgerLines()->exists()) {
                    $historicPaid = round((float) $ledger->total_paid, 2);
                    $ledger->school_year = $ledger->school_year ?? $year;
                    $ledger->save();
                    $fees->assessLedger($ledger->refresh(), (float) $totalTuition, $year, $ledger->assessment_path ?? $path, $remainingMonths, $assessStart, (float) $totalMisc, $miscItems ?? []);
                    $ledger = $ledger->refresh();
                    if ($historicPaid > 0) {
                        $fees->allocatePayment($ledger, $historicPaid);
                        $ledger = $ledger->refresh();
                    }
                }

                if ($ledger->ledgerLines()->exists()) {
                    $floor = $fees->floorFor($ledger);
                    $floorAmount = (float) $floor['floor'];
                    $floorLabel = (string) $floor['label'];
                } else {
                    $isFullPreview = $path === FeeAssessmentService::PATH_FULL;
                    $floorAmount = $isFullPreview
                        ? $fees->fullTotalFor((float) $totalTuition, (float) $totalMisc)
                        : FeeAssessmentService::ENROLLMENT_FEE;
                    $floorLabel = $isFullPreview ? 'Full total' : 'Enrollment Fee';
                }

                if ($floorAmount <= 0) {
                    throw new \RuntimeException('Fully Paid — no further payments required for this account.');
                }

                if ($amount < $floorAmount) {
                    throw new \RuntimeException('Minimum ₱' . number_format($floorAmount, 2) . ' — ' . $floorLabel . ' ₱' . number_format($floorAmount, 2) . '. Below-floor payments are never accepted.');
                }

                $candidate = null;
                for ($attempt = 0; $attempt < 5; $attempt++) {
                    $todayCount = Payment::whereDate('payment_date', today())->count() + 1 + $attempt;
                    $try = 'RCP-' . now()->format('Ymd') . '-' . str_pad((string) $todayCount, 4, '0', STR_PAD_LEFT);
                    if (! Payment::where('receipt_number', $try)->exists()) {
                        $candidate = $try;
                        break;
                    }
                }

                if ($candidate === null) {
                    throw new \RuntimeException('Could not generate unique receipt number after 5 attempts.');
                }
                $receiptNumber = $candidate;

                $arNumber = (! empty($data['ar_number'])) ? (string) $data['ar_number'] : (new Payment())->generateArNumber();

                $payment = Payment::create([
                    'ledger_id' => $ledger->id,
                    'cashier_id' => $cashierId,
                    'amount_paid' => $amount,
                    'tuition_amount' => $amount,
                    'library_amount' => 0,
                    'receipt_number' => $receiptNumber,
                    'ar_number' => $arNumber,
                    'receipt_file_path' => $data['receipt_file_path'] ?? null,
                    'payment_date' => now(),
                ]);

                $allocation = $fees->allocatePayment($ledger->refresh(), $amount);
                $creditedExcess = round((float) $allocation['excess'], 2);
                $ledger = $ledger->refresh();
                $ledger->total_paid = round((float) $ledger->ledgerLines()->sum('amount_paid'), 2);
                $ledger->balance = max(0, $ledger->total_assessed - $ledger->total_paid - $ledger->discount_applied);
                $ledger->save();
                LedgerService::refreshClearance($ledger);

                // Paid file returns to Registrar as Pending carrying the
                // receipt (Option A) — Forward is blocked from here on.
                $admission->status = 'Pending';
                $admission->downpayment_receipt = $receiptNumber;
                $admission->downpayment_paid_at = now();
                $admission->save();

                log_activity($admission, 'Downpayment Confirmed', $cashierName . ' confirmed ₱' . number_format($amount, 2) . ' downpayment (' . $path . ', Receipt: ' . $receiptNumber . ') for admission ' . ($admission->application_number ?? '#' . $admission->id) . '.' . $holdsNote);
            });
        } catch (\Throwable $e) {
            Log::error('Downpayment confirmation failed: ' . $e->getMessage(), [
                'student_id' => $student->id,
                'admission_id' => $admission->id ?? null,
                'grade' => $grade,
                'school_year' => $year,
                'payment_plan' => $path,
                'amount' => $data['amount_paid'] ?? null,
                'cashier_id' => $cashierId,
                'exception' => get_class($e),
                'trace' => $e->getTraceAsString(),
            ]);

            $gates = ['not awaiting downpayment', 'locked', 'No fee schedule', 'Fully Paid', 'Minimum ₱'];
            foreach ($gates as $gate) {
                if (str_contains($e->getMessage(), $gate)) {
                    return ['confirmed' => false, 'already' => false, 'error' => $e->getMessage() . ' No charge made — safe to retry.'];
                }
            }

            $detail = trim($e->getMessage()) !== '' ? $e->getMessage() : get_class($e);

            return ['confirmed' => false, 'already' => false, 'error' => 'Downpayment processing failed (' . $detail . '). No charge made — safe to retry. File stayed as Awaiting Downpayment.'];
        }

        return [
            'confirmed' => true,
            'already' => false,
            'admission' => $admission->refresh(),
            'payment' => $payment,
            'receipt' => $receiptNumber,
            'credited' => $creditedExcess,
            'message' => '₱' . number_format($amount, 2) . ' downpayment received. File returned to Registrar as Pending with receipt ' . $receiptNumber . '.'
                . ($creditedExcess > 0 ? ' ₱' . number_format($creditedExcess, 2) . ' credited to next term.' : ''),
        ];
    }

    /**
     * Refund/void drop-back (spec §6 edge): voiding the downpayment
     * receipt before approval returns the file to Awaiting Downpayment
     * with history kept. Approved files are untouched — the money
     * reversal stands on its own there.
     */
    public static function dropBackOnVoid(Payment $payment, string $actorName): bool
    {
        $admission = Admission::where('downpayment_receipt', $payment->receipt_number)->latest()->first();

        if ($admission === null || $admission->status !== 'Pending') {
            return false;
        }

        $admission->status = AdmissionReviewService::STATUS_AWAITING_DOWNPAYMENT;
        $admission->downpayment_receipt = null;
        $admission->downpayment_paid_at = null;
        $admission->save();

        log_activity($admission, 'Downpayment Reversed', $actorName . ' voided receipt ' . $payment->receipt_number . ' — admission ' . ($admission->application_number ?? '#' . $admission->id) . ' is Awaiting Downpayment again. History kept.');

        return true;
    }
}
