<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\StudentLedger;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Spec: seed-data-payments-fulfilled.md (Child 3 of seed-data-connected-history.md).
 *
 * Fills believable paid-up history:
 *  - 2024-2025 / 2025-2026: 100% paid minus 2-3 demo debtors per year,
 *    2-4 scattered weekday installments (Jun YYYY -> Feb YYYY+1), unique
 *    OR + year-scoped AR-YYYY-NNNN + cashier, ledgers zeroed, then locked.
 *  - Current year: ~99% paid (same shape, scattered Jun -> today), demo
 *    debtors keep live receivables, stays unlocked.
 *  - 2027-2028: zero payments (guard only).
 *
 * Idempotent: ledgers already carrying Child-3 receipts
 * (RCP-<student_number>-<YYYY>-*) are skipped untouched; legacy Child-2
 * current-year receipts (RCP-*-001) do NOT block the first top-up.
 * Discounts are never recomputed (existing discount_applied kept).
 * Locked-year writes here are console backfill; the HTTP runtime lock
 * (school_year_locked + controllers) is untouched and still refuses edits.
 */
class PaymentsHistorySeeder extends Seeder
{
    protected const DEBTORS_PER_YEAR = 3;

    protected function windowFor(string $schoolYear, string $activeYear): array
    {
        $startYear = (int) explode('-', $schoolYear)[0];
        if ($schoolYear === $activeYear) {
            return [Carbon::create($startYear, 6, 1, 8), now()];
        }
        return [Carbon::create($startYear, 6, 1, 8), Carbon::create($startYear + 1, 2, 28, 15)];
    }

    protected function randomWeekday(Carbon $start, Carbon $end): Carbon
    {
        if ($end->lt($start)) $end = $start->copy();
        $span = (int) $start->diffInDays($end);
        $tries = 0;
        do {
            $date = $start->copy()->addDays(mt_rand(0, max(0, $span)));
            $tries++;
        } while ($date->isWeekend() && $tries < 12);
        if ($date->isWeekend()) {
            $date = $start->copy();
            while ($date->isWeekend() && $date->lte($end)) $date->addDay();
            if ($date->gt($end)) $date = $end->copy();
        }
        return $date->setTime(mt_rand(8, 15), mt_rand(0, 59), 0);
    }

    protected function nextAr(string $year): string
    {
        static $nextByYear = [];
        if (!isset($nextByYear[$year])) {
            $lastAr = DB::table('payments')
                ->where('ar_number', 'like', "AR-{$year}-%")
                ->orderByDesc('ar_number')
                ->value('ar_number');
            $nextByYear[$year] = $lastAr !== null ? ((int) substr((string) $lastAr, -4)) + 1 : 500;
        }
        do {
            $candidate = 'AR-' . $year . '-' . str_pad((string) $nextByYear[$year], 4, '0', STR_PAD_LEFT);
            $nextByYear[$year]++;
        } while (DB::table('payments')->where('ar_number', $candidate)->exists());
        return $candidate;
    }

    /** Split cents into k installments (last takes the remainder). */
    protected function splitCents(int $totalCents, int $k): array
    {
        $base = intdiv($totalCents, $k);
        $parts = array_fill(0, $k, $base);
        $parts[$k - 1] += $totalCents - ($base * $k);
        return $parts;
    }

    public function run(): void
    {
        $activeYear = active_school_year();
        $years = array_values(array_unique(['2024-2025', '2025-2026', $activeYear]));
        $cashierIds = User::where('role_id', 3)->pluck('id')->toArray();

        $prevBalances = [];

        foreach ($years as $schoolYear) {
            $isCurrent = $schoolYear === $activeYear;
            $year = explode('-', $schoolYear)[0];
            [$windowStart, $windowEnd] = $this->windowFor($schoolYear, $activeYear);

            $ledgers = StudentLedger::where('school_year', $schoolYear)->orderBy('id')->get();
            if ($ledgers->isEmpty()) {
                $this->command->info("Year {$schoolYear}: no ledgers, skipped.");
                continue;
            }

            // 1) Carry-forward patch: debtors promote carrying their balance.
            // Paid kids keep carried 0. Only ledgers with no Child-3 receipts
            // and carried_over_balance == 0 are patched (never double-carry).
            $carried = 0;
            foreach ($ledgers as $ledger) {
                $prev = $prevBalances[$ledger->student_id] ?? 0;
                if ($prev <= 0) continue;
                if ((float) ($ledger->carried_over_balance ?? 0) > 0) continue;
                $hasChild3 = Payment::where('ledger_id', $ledger->id)
                    ->where('receipt_number', 'like', "%-{$year}-%")
                    ->exists();
                if ($hasChild3) continue;
                DB::transaction(function () use ($ledger, $prev) {
                    $ledger->carried_over_balance = $prev;
                    $ledger->total_assessed = (float) $ledger->total_assessed + $prev;
                    $ledger->balance = (float) $ledger->balance + $prev;
                    $ledger->clearance_status = 'Uncleared';
                    $ledger->save();
                });
                $carried++;
            }

            // 2) Debtor selection (deterministic): carried debtors first,
            // then lowest ids to reach 3. Same set on re-runs.
            $carriedDebtorIds = [];
            foreach ($ledgers as $ledger) {
                $prev = $prevBalances[$ledger->student_id] ?? 0;
                if ($prev > 0) $carriedDebtorIds[] = $ledger->id;
            }
            $debtorIds = array_flip(array_slice($carriedDebtorIds, 0, self::DEBTORS_PER_YEAR));
            if (count($debtorIds) < self::DEBTORS_PER_YEAR) {
                foreach ($ledgers as $ledger) {
                    if (count($debtorIds) >= self::DEBTORS_PER_YEAR) break;
                    if (isset($debtorIds[$ledger->id])) continue;
                    // Full-scholarship (nothing owed) can never be a debtor.
                    $effective = (float) $ledger->total_assessed - (float) ($ledger->discount_applied ?? 0);
                    if ($effective <= 0) continue;
                    $debtorIds[$ledger->id] = true;
                }
            }

            // 3) Installments per ledger.
            $paid = 0;
            $debtors = 0;
            $skipped = 0;
            foreach ($ledgers as $ledger) {
                $hasChild3 = Payment::where('ledger_id', $ledger->id)
                    ->where('receipt_number', 'like', "%-{$year}-%")
                    ->exists();
                if ($hasChild3) {
                    $skipped++;
                    continue;
                }

                // Every payment needs an enrollment of the same year.
                $hasEnrollment = Enrollment::where('student_id', $ledger->student_id)
                    ->where('school_year', $schoolYear)
                    ->exists();
                if (!$hasEnrollment) {
                    $skipped++;
                    continue;
                }

                $effective = round((float) $ledger->total_assessed - (float) ($ledger->discount_applied ?? 0), 2);
                if ($effective <= 0) {
                    $ledger->total_paid = 0;
                    $ledger->balance = 0;
                    $ledger->clearance_status = 'Cleared';
                    $ledger->save();
                    continue;
                }

                $isDebtor = isset($debtorIds[$ledger->id]);
                $alreadyPaid = (float) ($ledger->total_paid ?? 0);

                if (!$isCurrent) {
                    // History: ledger shells carry zero paid; full amount due.
                    $due = $effective;
                } else {
                    // Current: legacy Child-2 partials exist; top up remainder.
                    $due = round($effective - $alreadyPaid, 2);
                    if ($due <= 0) {
                        $ledger->clearance_status = 'Cleared';
                        $ledger->balance = 0;
                        $ledger->save();
                        $paid++;
                        continue;
                    }
                }

                if ($isDebtor) {
                    // Demo debtor: pay ~40-60% in 1-2 installments, keep balance.
                    $cents = (int) round($due * 100);
                    $payCents = (int) round($cents * (40 + ($ledger->id % 21)) / 100);
                    $payCents = max(100, min($cents - 100, $payCents));
                    if ($cents <= 200 || $payCents <= 0) {
                        $debtors++;
                        continue;
                    }
                    $k = ($ledger->id % 2) + 1;
                    $parts = $this->splitCents($payCents, $k);
                    $this->writeInstallments($ledger, $parts, $year, $windowStart, $windowEnd, $cashierIds, false);
                    $debtors++;
                } else {
                    $cents = (int) round($due * 100);
                    if ($cents <= 0) {
                        $paid++;
                        continue;
                    }
                    $k = 2 + ($ledger->id % 3); // 2-4 installments
                    if ($cents < $k * 100) $k = 1;
                    $parts = $this->splitCents($cents, $k);
                    $this->writeInstallments($ledger, $parts, $year, $windowStart, $windowEnd, $cashierIds, true);
                    $paid++;
                }
            }

            // Track closing balances for next year's carry.
            foreach (StudentLedger::where('school_year', $schoolYear)->get() as $ledger) {
                $prevBalances[$ledger->student_id] = round((float) $ledger->balance, 2);
            }

            $this->command->info(
                "Year {$schoolYear}: {$paid} fulfilled, {$debtors} debtors, {$skipped} kept, {$carried} carried-in."
            );
        }

        // 2027-2028 guard: fees ready, zero payments.
        $futurePayments = Payment::whereHas('ledger', fn($q) => $q->where('school_year', '2027-2028'))->count();
        $this->command->info("Year 2027-2028: {$futurePayments} payments (must be 0).");
    }

    /**
     * @param int[] $partsCents
     */
    protected function writeInstallments(
        StudentLedger $ledger,
        array $partsCents,
        string $year,
        Carbon $windowStart,
        Carbon $windowEnd,
        array $cashierIds,
        bool $clearInFull,
    ): void {
        DB::transaction(function () use ($ledger, $partsCents, $year, $windowStart, $windowEnd, $cashierIds, $clearInFull) {
            $studentNumber = $ledger->student->student_number ?? (string) $ledger->student_id;
            $dates = [];
            for ($i = 0; $i < count($partsCents); $i++) {
                $dates[] = $this->randomWeekday($windowStart, $windowEnd);
            }
            sort($dates);

            $seq = 1;
            while (Payment::where('receipt_number', 'RCP-' . $studentNumber . '-' . $year . '-' . sprintf('%02d', $seq))->exists()) {
                $seq++;
            }

            $written = 0.0;
            foreach ($partsCents as $i => $cents) {
                $amount = round($cents / 100, 2);
                if ($amount <= 0) continue;
                $receipt = 'RCP-' . $studentNumber . '-' . $year . '-' . sprintf('%02d', $seq++);
                if (Payment::where('receipt_number', $receipt)->exists()) continue;
                $date = $dates[$i] ?? $dates[0];
                Payment::create([
                    'ledger_id' => $ledger->id,
                    'cashier_id' => !empty($cashierIds) ? $cashierIds[($ledger->id + $i) % count($cashierIds)] : null,
                    'amount_paid' => $amount,
                    'receipt_number' => $receipt,
                    'ar_number' => $this->nextAr($year),
                    'payment_date' => $date->toDateString(),
                    'created_at' => $date->toDateTimeString(),
                    'updated_at' => $date->toDateTimeString(),
                ]);
                $written = round($written + $amount, 2);
            }

            $fresh = StudentLedger::find($ledger->id);
            if ($fresh === null) return;
            $newPaid = round((float) ($fresh->total_paid ?? 0) + $written, 2);
            $effective = round((float) $fresh->total_assessed - (float) ($fresh->discount_applied ?? 0), 2);
            $fresh->total_paid = $clearInFull ? $effective : $newPaid;
            $fresh->balance = max(0, round($effective - $fresh->total_paid, 2));
            $fresh->clearance_status = $fresh->balance <= 0 ? 'Cleared' : 'Uncleared';
            $fresh->save();
        });
    }
}
