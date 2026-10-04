<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Payment;
use App\Models\StudentLedger;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class CashierProjectionService
{
    public const MAX_RANGE_YEARS = 10;

    /**
     * @return array{
     *     receivables: float,
     *     collections: float,
     *     periodFrom: string,
     *     periodTo: string
     * }
     */
    public function summaryForPeriod(Carbon $from, Carbon $to): array
    {
        [$from, $to] = $this->clampRange($from, $to);

        return [
            'receivables' => $this->receivablesAsOf($to),
            'collections' => (float) Payment::whereBetween('payment_date', [
                $from->copy()->startOfDay(),
                $to->copy()->endOfDay(),
            ])->sum('amount_paid'),
            'periodFrom' => $from->toDateString(),
            'periodTo' => $to->toDateString(),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function defaultDashboardRange(): array
    {
        $today = Carbon::now();

        return [$today->copy()->startOfMonth(), $today->copy()];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function clampRange(Carbon $from, Carbon $to): array
    {
        if ($to->lessThan($from)) {
            [$from, $to] = [$to, $from];
        }

        $earliest = $to->copy()->subYears(self::MAX_RANGE_YEARS);

        if ($from->lessThan($earliest)) {
            $from = $earliest->copy();
        }

        return [$from->copy()->startOfDay(), $to->copy()->endOfDay()];
    }

    public function earliestSelectableDate(): string
    {
        return Carbon::now()->subYears(self::MAX_RANGE_YEARS)->toDateString();
    }

    /**
     * Receivables reconstructed as at a date from assessed fees, discounts and
     * the receipts actually booked on or before that date. Ledgers created after
     * the date are excluded so a later enrolment cannot appear in an earlier period.
     */
    public function receivablesAsOf(Carbon $asOf): float
    {
        $asOf = $asOf->copy()->endOfDay();

        $paidByLedger = Payment::where('payment_date', '<=', $asOf)
            ->select('ledger_id', DB::raw('SUM(amount_paid) AS total'))
            ->groupBy('ledger_id');

        $row = StudentLedger::query()
            ->leftJoinSub($paidByLedger, 'p', 'p.ledger_id', '=', 'student_ledgers.id')
            ->where(static function ($query) use ($asOf): void {
                $query->where('student_ledgers.created_at', '<=', $asOf)
                    ->orWhereNull('student_ledgers.created_at');
            })
            ->selectRaw(
                'SUM(CASE WHEN (student_ledgers.total_assessed - student_ledgers.discount_applied'
                . ' - COALESCE(p.total, 0)) > 0'
                . ' THEN (student_ledgers.total_assessed - student_ledgers.discount_applied'
                . ' - COALESCE(p.total, 0))'
                . ' ELSE 0 END) AS receivable_total'
            )
            ->get()
            ->first();

        return (float) ($row->receivable_total ?? 0);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function monthRangeForSchoolYear(string $schoolYear): array
    {
        [$startYear, $endYear] = array_map('intval', explode('-', $schoolYear));

        return [
            Carbon::create($startYear, 6, 1)->startOfMonth(),
            Carbon::create($endYear, 5, 1)->endOfMonth(),
        ];
    }

    public function monthlySeriesForRange(Carbon $from, Carbon $to): array
    {
        [$from, $to] = $this->clampRange($from, $to);

        $startMonth = $from->copy()->startOfMonth();
        $endMonth = $to->copy()->endOfMonth();

        $collectionsByMonth = $this->collectionsGroupedByMonth($startMonth, $endMonth);

        $assessedNetOfDiscounts = (float) StudentLedger::query()
            ->where(static function ($query) use ($endMonth): void {
                $query->where('created_at', '<=', $endMonth)
                    ->orWhereNull('created_at');
            })
            ->selectRaw('COALESCE(SUM(total_assessed - discount_applied), 0) AS net')
            ->get()
            ->first()
            ->net;

        $labels = [];
        $collected = [];
        $outstanding = [];

        $cumulativeCollected = 0.0;

        $period = CarbonPeriod::create($startMonth, '1 month', $endMonth);

        foreach ($period as $month) {
            $labels[] = $month->format('M');

            $monthCollected = $collectionsByMonth[$month->format('Y-m')] ?? 0.0;

            $cumulativeCollected += $monthCollected;

            $collected[] = round($monthCollected, 2);
            $outstanding[] = round(max(0.0, $assessedNetOfDiscounts - $cumulativeCollected), 2);
        }

        return [
            'labels' => $labels,
            'collected' => $collected,
            'outstanding' => $outstanding,
        ];
    }

    /**
     * Buckets receipts per month in PHP rather than in SQL.
     *
     * Month bucketing is deliberately not pushed into the database: the school runs
     * PostgreSQL while other environments declare MySQL, and DATE_FORMAT / TO_CHAR
     * are mutually incompatible. One query, one code path, any driver.
     *
     * @return array<string, float>
     */
    private function collectionsGroupedByMonth(Carbon $from, Carbon $to): array
    {
        $payments = Payment::whereBetween('payment_date', [$from, $to])
            ->get(['payment_date', 'amount_paid']);

        $byMonth = [];

        foreach ($payments as $payment) {
            $date = $payment->payment_date;

            if (! $date instanceof Carbon) {
                continue;
            }

            $key = $date->format('Y-m');
            $byMonth[$key] = ($byMonth[$key] ?? 0.0) + (float) $payment->amount_paid;
        }

        return $byMonth;
    }

    public function monthlySeries(string $schoolYear): array
    {
        [$from, $to] = $this->monthRangeForSchoolYear($schoolYear);

        return $this->monthlySeriesForRange($from, $to);
    }
}