<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FeeSchedule;
use App\Models\LedgerLine;
use App\Models\LibraryTransaction;
use App\Models\Payment;
use App\Models\StudentLedger;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Spec: cashier-fee-rules.md — Slice 2 (domain rules engine).
 *
 * Single source for the written-down fee rules:
 * - ₱1.5k enrollment line on every assessment: fixed, never discounted, never waived (v1).
 * - Full path: 1.5k + 90% of tuition lines, computed once at assessment.
 * - Installment path: 1.5k due at enrollment + remaining tuition ÷ N equal monthly lines at full price.
 * - Mid-year: remaining full tuition over remaining months; 1.5k still posts in full.
 * - Path switch: single re-computation, prior payments credited in order, history kept.
 * - Floor: every term payment meets or exceeds that term's total.
 * - Library: Cashier's receipt wins; one due, one receipt, one lift (DB unique backs this).
 *
 * The full-payment −10% lives INSIDE the tuition line (reduced assessment),
 * never in ledger.discount_applied — the existing discount auto-post flow
 * (honor/sibling/ESC) keeps owning discount_applied, so receivables math
 * (total_assessed − discount_applied − paid) is preserved.
 */
class FeeAssessmentService
{
    public const ENROLLMENT_FEE = 1500.00;

    public const INSTALLMENTS = 10;

    public const FULL_DISCOUNT_RATE = 0.10;

    public const PATH_FULL = 'full';

    public const PATH_INSTALLMENT = 'installment';

    /**
     * Fresh assessment: posts the 1.5k line + tuition lines.
     * Posted lines are immutable — later fee edits only affect new assessments.
     */
    public function assessLedger(
        StudentLedger $ledger,
        float $tuitionTotal,
        string $schoolYear,
        string $path,
        int $remainingMonths = self::INSTALLMENTS,
        ?Carbon $startMonth = null
    ): void {
        $this->assertPath($path);
        $remainingMonths = max(1, min(self::INSTALLMENTS, $remainingMonths));
        $tuitionTotal = round(max(0, $tuitionTotal), 2);

        DB::transaction(function () use ($ledger, $tuitionTotal, $schoolYear, $path, $remainingMonths, $startMonth): void {
            $ledger->school_year = $schoolYear;
            $ledger->assessment_path = $path;
            $ledger->payment_plan = $path;
            $ledger->enrollment_fee = self::ENROLLMENT_FEE;
            $ledger->tuition_assessed = $tuitionTotal;
            $ledger->installment_count = $path === self::PATH_INSTALLMENT ? $remainingMonths : 0;

            if ($path === self::PATH_FULL) {
                $discounted = round($tuitionTotal * (1 - self::FULL_DISCOUNT_RATE), 2);
                $ledger->total_assessed = round(self::ENROLLMENT_FEE + $discounted, 2);

                $ledger->save();

                $ledger->ledgerLines()->delete();

                $ledger->ledgerLines()->create([
                    'school_year' => $schoolYear,
                    'kind' => LedgerLine::KIND_ENROLLMENT,
                    'label' => 'Enrollment Fee',
                    'amount_assessed' => self::ENROLLMENT_FEE,
                ]);
                $ledger->ledgerLines()->create([
                    'school_year' => $schoolYear,
                    'kind' => LedgerLine::KIND_TUITION,
                    'label' => 'Full tuition (10% discount)',
                    'amount_assessed' => $discounted,
                ]);
            } else {
                $ledger->total_assessed = round(self::ENROLLMENT_FEE + $tuitionTotal, 2);
                $ledger->save();

                $ledger->ledgerLines()->delete();

                $ledger->ledgerLines()->create([
                    'school_year' => $schoolYear,
                    'kind' => LedgerLine::KIND_ENROLLMENT,
                    'label' => 'Enrollment Fee',
                    'amount_assessed' => self::ENROLLMENT_FEE,
                ]);

                foreach ($this->equalSplits($tuitionTotal, $remainingMonths) as $i => $amount) {
                    $month = $i + 1;
                    $ledger->ledgerLines()->create([
                        'school_year' => $schoolYear,
                        'kind' => LedgerLine::KIND_TUITION,
                        'label' => 'Month ' . $month,
                        'due_month' => $month,
                        'due_date' => $startMonth ? $startMonth->copy()->addMonths($month - 1)->startOfMonth() : null,
                        'amount_assessed' => $amount,
                    ]);
                }
            }

            $this->refreshTotals($ledger);
        });
    }

    /**
     * Path switch after paying: single re-computation, prior payments
     * credited in order (enrollment first, then months), history kept
     * (paid lines stay; only unpaid lines are replaced).
     */
    public function switchPath(StudentLedger $ledger, string $newPath): void
    {
        $this->assertPath($newPath);

        if ($ledger->assessment_path === $newPath) {
            return;
        }

        DB::transaction(function () use ($ledger, $newPath): void {
            $tuitionTotal = round((float) $ledger->tuition_assessed, 2);
            $schoolYear = (string) ($ledger->school_year ?? '');
            $monthsLeft = $ledger->installment_count > 0 ? (int) $ledger->installment_count : self::INSTALLMENTS;

            $paidLines = $ledger->ledgerLines()->where('amount_paid', '>', 0)->orderBy('id')->get();
            $credited = round((float) $paidLines->sum('amount_paid'), 2);

            $ledger->ledgerLines()->where('amount_paid', '<=', 0)->delete();

            if ($newPath === self::PATH_FULL) {
                $discounted = round($tuitionTotal * (1 - self::FULL_DISCOUNT_RATE), 2);
                $ledger->assessment_path = self::PATH_FULL;
                $ledger->payment_plan = self::PATH_FULL;
                $ledger->installment_count = 0;
                $ledger->total_assessed = round(self::ENROLLMENT_FEE + $discounted, 2);
                $ledger->save();

                $remaining = round($ledger->total_assessed - $credited - (float) $ledger->discount_applied, 2);
                $ledger->ledgerLines()->create([
                    'school_year' => $schoolYear,
                    'kind' => LedgerLine::KIND_TUITION,
                    'label' => 'Full tuition (10% discount) — re-assessed',
                    'amount_assessed' => max(0, $remaining > 0 ? $remaining : 0),
                    'amount_paid' => 0,
                    'status' => LedgerLine::STATUS_UNPAID,
                ]);
            } else {
                $ledger->assessment_path = self::PATH_INSTALLMENT;
                $ledger->payment_plan = self::PATH_INSTALLMENT;
                $ledger->installment_count = $monthsLeft;
                $ledger->total_assessed = round(self::ENROLLMENT_FEE + $tuitionTotal, 2);
                $ledger->save();

                $unpaidTuition = round($tuitionTotal - max(0, $credited - self::ENROLLMENT_FEE), 2);
                foreach ($this->equalSplits(max(0, $unpaidTuition), $monthsLeft) as $i => $amount) {
                    $ledger->ledgerLines()->create([
                        'school_year' => $schoolYear,
                        'kind' => LedgerLine::KIND_TUITION,
                        'label' => 'Month ' . ($i + 1) . ' — re-assessed',
                        'due_month' => $i + 1,
                        'amount_assessed' => $amount,
                    ]);
                }
            }

            $this->refreshTotals($ledger);
        });
    }

    /**
     * Term floor: the earliest unpaid line's remaining (never below it).
     *
     * @return array{floor: float, label: string, line_ids: array<int>}
     */
    public function floorFor(StudentLedger $ledger): array
    {
        $line = $ledger->ledgerLines()
            ->where('status', '!=', LedgerLine::STATUS_PAID)
            ->orderByRaw("CASE WHEN kind = 'enrollment' THEN 0 ELSE 1 END")
            ->orderBy('due_month')
            ->orderBy('id')
            ->first();

        if (! $line) {
            return ['floor' => 0.0, 'label' => 'Fully Paid', 'line_ids' => []];
        }

        $floor = round((float) $line->amount_assessed - (float) $line->amount_paid, 2);

        return [
            'floor' => max(0, $floor),
            'label' => $line->label,
            'line_ids' => [$line->id],
        ];
    }

    /**
     * Allocate a cash receipt across lines in order; overpayment past the
     * last line returns as excess (credited to the next term, shown, never silent).
     *
     * @return array{applied: float, excess: float, lines: array<int, float>}
     */
    public function allocatePayment(StudentLedger $ledger, float $amount): array
    {
        $left = round($amount, 2);
        $applied = 0.0;
        $perLine = [];

        $lines = $ledger->ledgerLines()
            ->where('status', '!=', LedgerLine::STATUS_PAID)
            ->orderByRaw("CASE WHEN kind = 'enrollment' THEN 0 ELSE 1 END")
            ->orderBy('due_month')
            ->orderBy('id')
            ->get();

        foreach ($lines as $line) {
            if ($left <= 0) {
                break;
            }
            $due = round((float) $line->amount_assessed - (float) $line->amount_paid, 2);
            if ($due <= 0) {
                continue;
            }
            $take = min($due, $left);
            $line->amount_paid = round((float) $line->amount_paid + $take, 2);
            $line->status = $line->remaining() <= 0 ? LedgerLine::STATUS_PAID
                : ((float) $line->amount_paid > 0 ? LedgerLine::STATUS_PARTIAL : LedgerLine::STATUS_UNPAID);
            $line->save();

            $perLine[$line->id] = $take;
            $applied = round($applied + $take, 2);
            $left = round($left - $take, 2);
        }

        return ['applied' => $applied, 'excess' => max(0, $left), 'lines' => $perLine];
    }

    /**
     * Status-first language for Cashier + family views (never raw ledger primary).
     *
     * @return array{status: string, next_due: string, remaining: float}
     */
    public function statusFor(StudentLedger $ledger): array
    {
        $ledger->loadMissing('ledgerLines');
        $remaining = round((float) $ledger->balance, 2);

        if ($remaining <= 0 && $ledger->ledgerLines->every(fn (LedgerLine $l): bool => $l->isPaid())) {
            return ['status' => 'Fully Paid', 'next_due' => '', 'remaining' => 0.0];
        }

        $overdue = $ledger->ledgerLines
            ->where('status', '!=', LedgerLine::STATUS_PAID)
            ->whereNotNull('due_date')
            ->filter(fn (LedgerLine $l): bool => $l->due_date && $l->due_date->isPast())
            ->sortBy('due_month')
            ->first();

        if ($overdue) {
            return [
                'status' => 'Overdue Installment',
                'next_due' => $overdue->label . ' — ₱' . number_format($overdue->remaining(), 2),
                'remaining' => $remaining,
            ];
        }

        $next = $ledger->ledgerLines
            ->where('status', '!=', LedgerLine::STATUS_PAID)
            ->sortBy(fn (LedgerLine $l): array => [$l->kind === LedgerLine::KIND_ENROLLMENT ? 0 : 1, $l->due_month ?? 99, $l->id])
            ->first();

        return [
            'status' => 'Has Balance',
            'next_due' => $next ? $next->label . ' — ₱' . number_format($next->remaining(), 2) : '',
            'remaining' => $remaining,
        ];
    }

    /**
     * Shared receipt: Cashier's receipt wins. Attaches dues to the payment
     * and stamps paid; the pivot unique + isPaid guard block double-posts.
     *
     * @param  Collection<int, LibraryTransaction>  $dues
     */
    public function markLibraryDuesPaid(Payment $payment, Collection $dues): void
    {
        DB::transaction(function () use ($payment, $dues): void {
            $total = 0.0;
            foreach ($dues as $due) {
                $fresh = LibraryTransaction::lockForUpdate()->find($due->id);
                if (! $fresh || $fresh->isPaid()) {
                    throw new InvalidArgumentException('Library due already receipted (one due, one receipt).');
                }
                $amount = round((float) $fresh->total_fees, 2);
                $payment->libraryTransactions()->attach($fresh->id, ['amount' => $amount]);
                $fresh->paid_payment_id = $payment->id;
                $fresh->paid_at = now();
                $fresh->save();
                $total = round($total + $amount, 2);
            }
            $payment->library_amount = $total;
            $payment->tuition_amount = round((float) $payment->amount_paid - $total, 2);
            $payment->save();
        });
    }

    /**
     * Explicit Roll Over: copies a year's fee rows forward for review.
     * New years start empty until this runs — never silent.
     */
    public function rollOverFees(string $fromYear, string $toYear): int
    {
        if ($fromYear === $toYear) {
            throw new InvalidArgumentException('Roll over needs two different school years.');
        }
        if (school_year_locked($toYear)) {
            throw new InvalidArgumentException('Cannot roll over — school year ' . $toYear . ' is locked.');
        }

        return DB::transaction(function () use ($fromYear, $toYear): int {
            $rows = FeeSchedule::forSchoolYear($fromYear)->get();
            $copied = 0;
            foreach ($rows as $row) {
                $exists = FeeSchedule::where('grade_level', $row->grade_level)
                    ->where('term', $row->term)
                    ->where('school_year', $toYear)
                    ->exists();
                if ($exists) {
                    continue;
                }
                FeeSchedule::create([
                    'grade_level' => $row->grade_level,
                    'term' => $row->term,
                    'school_year' => $toYear,
                    'tuition_fee' => $row->tuition_fee,
                    'misc_fee' => $row->misc_fee,
                    'misc_fee_items' => is_array($row->misc_fee_items) ? $row->misc_fee_items : $row->getRawOriginal('misc_fee_items'),
                ]);
                $copied++;
            }

            return $copied;
        });
    }

    /**
     * @return array<int, float>
     */
    private function equalSplits(float $total, int $months): array
    {
        $months = max(1, $months);
        $base = floor($total / $months * 100) / 100;
        $splits = array_fill(0, $months, round($base, 2));
        $splits[$months - 1] = round($total - $base * ($months - 1), 2);

        return $splits;
    }

    private function assertPath(string $path): void
    {
        if (! in_array($path, [self::PATH_FULL, self::PATH_INSTALLMENT], true)) {
            throw new InvalidArgumentException('Payment path must be full or installment.');
        }
    }

    private function refreshTotals(StudentLedger $ledger): void
    {
        $ledger->total_paid = round((float) $ledger->ledgerLines()->sum('amount_paid'), 2);
        $assessed = round((float) $ledger->ledgerLines()->sum('amount_assessed'), 2);
        if ($assessed > 0) {
            $ledger->total_assessed = $assessed;
        }
        $ledger->balance = round(max(0, (float) $ledger->total_assessed - (float) $ledger->total_paid - (float) $ledger->discount_applied), 2);
        $ledger->save();
        LedgerService::refreshClearance($ledger->refresh());
    }
}
