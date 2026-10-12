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
 * Spec: fee-itemized-manager.md (Revision — Bundled Monthly Payments).
 * Amends cashier-fee-rules.md §6 rule 1: the ₱1.5k line is
 * installment-downpayment-only (never on full payment), and the 10%
 * touches tuition lines only (misc/books/uniform/ID at 100%).
 *
 * Single source for the written-down fee rules:
 * - Installment path: ₱1.5k downpayment (first, fixed, never discounted)
 *   + 100% tuition + 100% misc bundled into N equal monthly lines.
 *   Books, Uniform, ID, Miscellaneous and the enrollment fee are never
 *   collected separately — each month already contains its share.
 * - Full path: 90% of tuition lines + 100% of misc lines
 *   (books, uniform, ID, miscellaneous, each named), no ₱1.5k line anywhere.
 * - Mid-year: remaining bundled total (tuition + misc) over remaining months.
 * - Path switch: single re-computation, prior payments credited in order
 *   (enrollment first, then bundled months), history kept (paid lines stay;
 *   only unpaid lines are replaced).
 * - Floor: every term payment meets or exceeds that term's total (bundled month).
 * - Library: Cashier's receipt wins; one due, one receipt, one lift (DB unique backs this).
 *
 *
 * The full-payment −10% lives INSIDE the tuition line (reduced assessment),
 * never in ledger.discount_applied — the existing discount auto-post flow
 * (honor/sibling/ESC) keeps owning discount_applied, so receivables math
 * (total_assessed − discount_applied − paid) is preserved.
 *
 * No migration: misc add-on lines reuse the tuition line kind and are told
 * apart by label. tuition_assessed stores the tuition-only base going
 * forward (old ledgers assessed under the bundle math keep their posted
 * figures; switchPath falls back gracefully when no misc lines exist).
 */
class FeeAssessmentService
{
    public const ENROLLMENT_FEE = 1500.00;

    public const INSTALLMENTS = 10;

    public const FULL_DISCOUNT_RATE = 0.10;

    public const PATH_FULL = 'full';

    public const PATH_INSTALLMENT = 'installment';

    /**
     * Misc add-on labels, canonicalised from FeeSchedule misc_fee_items keys
     * (books, uniform, id, miscellaneous). Labels are matched
     * case-insensitively so posted lines can be told apart from tuition lines.
     */
    public const MISC_LABELS = ['books', 'uniform', 'id', 'miscellaneous'];

    /**
     * Fresh assessment: full path posts discounted tuition + full-price misc
     * (no ₱1.5k line); installment posts the ₱1.5k line + N bundled months
     * each holding its share of tuition + misc (no separate add-on lines).
     * Posted lines are immutable — later fee edits only affect new assessments.
     *
     * @param  array<string, float>  $miscItems  itemised misc (books, uniform, id, miscellaneous)
     */
    public function assessLedger(
        StudentLedger $ledger,
        float $tuitionTotal,
        string $schoolYear,
        string $path,
        int $remainingMonths = self::INSTALLMENTS,
        ?Carbon $startMonth = null,
        float $miscTotal = 0.0,
        array $miscItems = []
    ): void {
        $this->assertPath($path);
        $remainingMonths = max(1, min(self::INSTALLMENTS, $remainingMonths));
        $tuitionTotal = round(max(0, $tuitionTotal), 2);
        $miscTotal = round(max(0, $miscTotal), 2);
        $miscItems = $this->normaliseMiscItems($miscItems, $miscTotal);

        DB::transaction(function () use ($ledger, $tuitionTotal, $miscTotal, $miscItems, $schoolYear, $path, $remainingMonths, $startMonth): void {
            $ledger->school_year = $schoolYear;
            $ledger->assessment_path = $path;
            $ledger->payment_plan = $path;
            $ledger->tuition_assessed = $tuitionTotal;
            $ledger->installment_count = $path === self::PATH_INSTALLMENT ? $remainingMonths : 0;

            if ($path === self::PATH_FULL) {
                $ledger->enrollment_fee = 0.0;
                $discounted = round($tuitionTotal * (1 - self::FULL_DISCOUNT_RATE), 2);
                $ledger->total_assessed = round($discounted + $miscTotal, 2);
                $ledger->save();

                $ledger->ledgerLines()->delete();

                $ledger->ledgerLines()->create([
                    'school_year' => $schoolYear,
                    'kind' => LedgerLine::KIND_TUITION,
                    'label' => 'Full tuition (10% discount)',
                    'amount_assessed' => $discounted,
                ]);
                $this->createMiscLines($ledger, $schoolYear, $miscItems);
            } else {
                $ledger->enrollment_fee = self::ENROLLMENT_FEE;
                $ledger->total_assessed = round(self::ENROLLMENT_FEE + $tuitionTotal + $miscTotal, 2);
                $ledger->save();

                $ledger->ledgerLines()->delete();

                $ledger->ledgerLines()->create([
                    'school_year' => $schoolYear,
                    'kind' => LedgerLine::KIND_ENROLLMENT,
                    'label' => 'Enrollment Fee',
                    'amount_assessed' => self::ENROLLMENT_FEE,
                ]);

                $bundledTotal = round($tuitionTotal + $miscTotal, 2);
                $tuitionSplits = $this->equalSplits($tuitionTotal, $remainingMonths);
                $miscSplitsByItem = [];
                foreach ($miscItems as $key => $amount) {
                    $miscSplitsByItem[(string) $key] = $this->equalSplits(round((float) $amount, 2), $remainingMonths);
                }
                foreach ($this->equalSplits($bundledTotal, $remainingMonths) as $i => $amount) {
                    $month = $i + 1;
                    $breakdown = $this->bundledBreakdown($tuitionSplits[$i] ?? 0.0, $miscSplitsByItem, $i);
                    $ledger->ledgerLines()->create([
                        'school_year' => $schoolYear,
                        'kind' => LedgerLine::KIND_TUITION,
                        'label' => 'Month ' . $month,
                        'term_label' => $breakdown,
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
     * credited in order (enrollment first, then bundled months), history
     * kept (paid lines stay; only unpaid lines are replaced). The 10% is
     * granted or removed accordingly — never both. Tuition/misc bases are
     * recovered from the posted lines (misc told apart by label, bundled
     * months fall back to tuition_assessed); ledgers assessed under the
     * old bundle math keep paid figures standing.
     */
    public function switchPath(StudentLedger $ledger, string $newPath): void
    {
        $this->assertPath($newPath);

        if ($ledger->assessment_path === $newPath) {
            return;
        }

        DB::transaction(function () use ($ledger, $newPath): void {
            $schoolYear = (string) ($ledger->school_year ?? '');
            $monthsLeft = $ledger->installment_count > 0 ? (int) $ledger->installment_count : self::INSTALLMENTS;

            $allLines = $ledger->ledgerLines()->orderBy('id')->get();
            [$tuitionTotal, $miscItems] = $this->splitBases($allLines, (float) $ledger->tuition_assessed);
            $miscTotal = round(array_sum($miscItems), 2);

            $paidLines = $allLines->where('amount_paid', '>', 0)->sortBy('id')->values();
            $credited = round((float) $paidLines->sum('amount_paid'), 2);

            $ledger->ledgerLines()->where('amount_paid', '<=', 0)->delete();

            if ($newPath === self::PATH_FULL) {
                $discounted = round($tuitionTotal * (1 - self::FULL_DISCOUNT_RATE), 2);
                $ledger->assessment_path = self::PATH_FULL;
                $ledger->payment_plan = self::PATH_FULL;
                $ledger->enrollment_fee = 0.0;
                $ledger->tuition_assessed = $tuitionTotal;
                $ledger->installment_count = 0;
                $ledger->total_assessed = round($discounted + $miscTotal, 2);
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
                $ledger->enrollment_fee = self::ENROLLMENT_FEE;
                $ledger->tuition_assessed = $tuitionTotal;
                $ledger->installment_count = $monthsLeft;
                $ledger->total_assessed = round(self::ENROLLMENT_FEE + $tuitionTotal + $miscTotal, 2);
                $ledger->save();

                $paidMisc = round((float) $paidLines->filter(fn (LedgerLine $l): bool => $this->isMiscLine($l))->sum('amount_paid'), 2);
                $paidTuitionish = round(max(0, $credited - $paidMisc), 2);
                // Enrollment credit applies first: anything paid up to ₱1.5k
                // covered the downpayment, the rest covered bundled months.
                $bundledTotal = round($tuitionTotal + $miscTotal, 2);
                $unpaidBundled = round($bundledTotal - max(0, $paidTuitionish - self::ENROLLMENT_FEE), 2);
                $unpaidTuitionShare = $bundledTotal > 0 ? round($tuitionTotal * max(0, $unpaidBundled) / $bundledTotal, 2) : 0.0;
                $unpaidMiscShare = round(max(0, $unpaidBundled) - $unpaidTuitionShare, 2);
                $tuitionSplits = $this->equalSplits(max(0, $unpaidTuitionShare), $monthsLeft);
                $miscSplitsByItem = [];
                if ($miscTotal > 0 && $unpaidMiscShare > 0) {
                    $ratio = $unpaidMiscShare / $miscTotal;
                    foreach ($miscItems as $key => $amount) {
                        $miscSplitsByItem[(string) $key] = $this->equalSplits(round((float) $amount * $ratio, 2), $monthsLeft);
                    }
                }
                foreach ($this->equalSplits(max(0, $unpaidBundled), $monthsLeft) as $i => $amount) {
                    $breakdown = $this->bundledBreakdown($tuitionSplits[$i] ?? 0.0, $miscSplitsByItem, $i);
                    $ledger->ledgerLines()->create([
                        'school_year' => $schoolYear,
                        'kind' => LedgerLine::KIND_TUITION,
                        'label' => 'Month ' . ($i + 1) . ' — re-assessed',
                        'term_label' => $breakdown,
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
    public function floorFor(StudentLedger $ledger, ?string $schoolYear = null): array
    {
        // Spec promoted-re-enrollment-direct-payment §6: floor reads the
        // picked year's earliest unpaid line, never another year's.
        $query = $ledger->ledgerLines()
            ->where('status', '!=', LedgerLine::STATUS_PAID);
        if ($schoolYear !== null && $schoolYear !== '') {
            $query->where('school_year', $schoolYear);
        }
        $line = $query->orderByRaw("CASE WHEN kind = 'enrollment' THEN 0 ELSE 1 END")
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
    public function statusFor(StudentLedger $ledger, ?string $schoolYear = null): array
    {
        $ledger->loadMissing('ledgerLines');
        // Spec promoted-re-enrollment-direct-payment §6: status reads the
        // picked year's lines only, so last year's payments never mark this
        // year Fully Paid (or vice versa).
        $lines = $ledger->ledgerLines;
        if ($schoolYear !== null && $schoolYear !== '') {
            $lines = $lines->where('school_year', $schoolYear)->values();
        }
        $remaining = round((float) $lines->sum(fn (LedgerLine $l): float => $l->remaining()), 2);

        if ($lines->isNotEmpty() && $remaining <= 0 && $lines->every(fn (LedgerLine $l): bool => $l->isPaid())) {
            return ['status' => 'Fully Paid', 'next_due' => '', 'remaining' => 0.0];
        }

        $overdue = $lines
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

        $next = $lines
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
     * Post one full-price line per misc add-on (books, uniform, ID,
     * miscellaneous), each named with its amount. Used by the full path
     * only — the installment path bundles misc into the monthly lines.
     */
    private function createMiscLines(StudentLedger $ledger, string $schoolYear, array $miscItems, int $afterMonth = 0, string $suffix = ''): void
    {
        $position = 1;
        foreach ($miscItems as $key => $amount) {
            $amount = round((float) $amount, 2);
            if ($amount <= 0) {
                continue;
            }
            $ledger->ledgerLines()->create([
                'school_year' => $schoolYear,
                'kind' => LedgerLine::KIND_TUITION,
                'label' => $this->miscLabel((string) $key) . $suffix,
                'due_month' => $afterMonth > 0 ? $afterMonth + $position : null,
                'amount_assessed' => $amount,
            ]);
            $position++;
        }
    }

    /**
     * @param  array<string, mixed>  $miscItems
     * @return array<string, float>
     */
    private function normaliseMiscItems(array $miscItems, float $miscTotal): array
    {
        $normalised = [];
        foreach ($miscItems as $key => $amount) {
            $slug = strtolower(trim((string) $key));
            if ($slug === '') {
                continue;
            }
            $normalised[$slug] = round((float) $amount, 2);
        }
        $sum = round(array_sum($normalised), 2);
        if ($sum <= 0 && $miscTotal > 0) {
            return ['miscellaneous' => $miscTotal];
        }
        if ($sum > 0 && abs($sum - $miscTotal) > 0.01 && $miscTotal > 0) {
            $factor = $miscTotal / $sum;
            foreach ($normalised as $key => $amount) {
                $normalised[$key] = round($amount * $factor, 2);
            }
        }

        return array_filter($normalised, fn (float $amount): bool => $amount > 0);
    }

    private function miscLabel(string $key): string
    {
        return match (strtolower(trim($key))) {
            'books' => 'Books',
            'uniform' => 'Uniform',
            'id' => 'ID',
            default => ucfirst(strtolower(trim($key))),
        };
    }

    /**
     * Human-readable share inside one bundled month for the View breakdown.
     *
     * @param  array<string, array<int, float>>  $miscSplitsByItem
     */
    private function bundledBreakdown(float $tuitionShare, array $miscSplitsByItem, int $index): string
    {
        $parts = ['Tuition ₱' . number_format(round((float) $tuitionShare, 2), 2)];
        foreach ($miscSplitsByItem as $key => $splits) {
            $share = round((float) ($splits[$index] ?? 0.0), 2);
            if ($share <= 0) {
                continue;
            }
            $parts[] = $this->miscLabel((string) $key) . ' ₱' . number_format($share, 2);
        }

        return 'Incl. ' . implode(' + ', $parts);
    }

    private function isMiscLine(LedgerLine $line): bool
    {
        if ($line->kind === LedgerLine::KIND_ENROLLMENT) {
            return false;
        }
        $label = strtolower(trim((string) $line->label));
        if (str_starts_with($label, 'month ') || str_starts_with($label, 'full tuition')) {
            return false;
        }
        foreach (self::MISC_LABELS as $misc) {
            if (str_starts_with($label, $misc)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recover the tuition-only base and the misc breakdown from posted lines.
     * Bundled installment months hold tuition + misc together with no
     * separate misc lines — the stored tuition_assessed stays the tuition
     * base and the remainder is the misc bucket so the 10% never touches
     * add-ons. Old separate-lines ledgers keep their named misc split.
     *
     * @param  Collection<int, LedgerLine>  $lines
     * @return array{0: float, 1: array<string, float>}
     */
    private function splitBases(Collection $lines, float $storedBase): array
    {
        $storedBase = round(max(0, $storedBase), 2);
        if ($lines->isEmpty()) {
            return [$storedBase, []];
        }

        $miscItems = [];
        foreach ($lines as $line) {
            if (! $this->isMiscLine($line)) {
                continue;
            }
            $slug = strtolower(trim(explode(' ', (string) $line->label)[0]));
            $miscItems[$slug] = round(($miscItems[$slug] ?? 0) + (float) $line->amount_assessed, 2);
        }
        $miscTotal = round(array_sum($miscItems), 2);

        $fullLine = $lines->first(fn (LedgerLine $l): bool => str_starts_with(strtolower(trim((string) $l->label)), 'full tuition'));
        if ($fullLine) {
            $tuition = round((float) $fullLine->amount_assessed / (1 - self::FULL_DISCOUNT_RATE), 2);
            return [$tuition, $miscItems];
        }

        $monthSum = round((float) $lines->filter(fn (LedgerLine $l): bool => str_starts_with(strtolower(trim((string) $l->label)), 'month '))->sum('amount_assessed'), 2);
        if ($monthSum > 0) {
            if ($miscTotal > 0) {
                // Old separate-lines math: months are tuition-only, misc sits in named lines.
                return [$monthSum, $miscItems];
            }
            // Bundled math: months already contain tuition + misc.
            if ($storedBase > 0 && $monthSum > $storedBase) {
                return [$storedBase, ['miscellaneous' => round($monthSum - $storedBase, 2)]];
            }
            if ($storedBase <= 0) {
                return [0.0, ['miscellaneous' => $monthSum]];
            }

            return [$monthSum, []];
        }

        if ($miscTotal > 0) {
            return [round(max(0, $storedBase - $miscTotal), 2), $miscItems];
        }

        return [$storedBase, []];
    }

    /**
     * Split fee schedules into the tuition-only base, the misc base, and the
     * itemised misc add-ons (books, uniform, ID, miscellaneous) summed across
     * rows. misc_fee_items may arrive as an array (cast) or a JSON string.
     *
     * @param  Collection<int, \App\Models\FeeSchedule>  $schedules
     * @return array{tuition: float, misc: float, items: array<string, float>}
     */
    public function splitSchedules(Collection $schedules): array
    {
        $tuition = round((float) $schedules->sum('tuition_fee'), 2);
        $misc = round((float) $schedules->sum('misc_fee'), 2);
        $items = [];
        foreach ($schedules as $schedule) {
            $raw = $schedule->misc_fee_items ?? [];
            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $raw = is_array($decoded) ? $decoded : [];
            }
            if (! is_array($raw)) {
                continue;
            }
            foreach ($raw as $key => $amount) {
                $slug = strtolower(trim((string) $key));
                if ($slug === '') {
                    continue;
                }
                $items[$slug] = round(($items[$slug] ?? 0) + (float) $amount, 2);
            }
        }
        $items = array_filter($items, fn (float $amount): bool => $amount > 0);
        if ($items === [] && $misc > 0) {
            $items = ['miscellaneous' => $misc];
        }

        return ['tuition' => $tuition, 'misc' => $misc, 'items' => $items];
    }

    /**
     * Full-path total preview: 90% of tuition + 100% of misc, no ₱1.5k line.
     */
    public function fullTotalFor(float $tuitionTotal, float $miscTotal): float
    {
        return round(round(max(0, $tuitionTotal), 2) * (1 - self::FULL_DISCOUNT_RATE) + round(max(0, $miscTotal), 2), 2);
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
