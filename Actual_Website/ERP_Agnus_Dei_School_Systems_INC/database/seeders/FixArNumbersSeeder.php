<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixArNumbersSeeder extends Seeder
{
    public function run(): void
    {
        // Spec seed-data-payments-fulfilled.md §6: backfill assigns the next
        // AR-YYYY-NNNN in THAT payment's year (ledger school_year start year,
        // fallback payment_date year), never today's year. Existing ARs kept.
        $payments = DB::table('payments as p')
            ->leftJoin('student_ledgers as l', 'l.id', '=', 'p.ledger_id')
            ->whereNull('p.ar_number')
            ->orderBy('p.id')
            ->select('p.id', 'p.receipt_number', 'p.payment_date', 'l.school_year')
            ->get();

        if ($payments->isEmpty()) {
            $this->command->info('Done. Remaining without AR: 0');
            return;
        }

        $nextByYear = [];
        foreach ($payments as $p) {
            $year = $this->yearFor($p->school_year ?? null, $p->payment_date ?? null);

            if (!array_key_exists($year, $nextByYear)) {
                $lastAr = DB::table('payments')
                    ->where('ar_number', 'like', "AR-{$year}-%")
                    ->orderByDesc('ar_number')
                    ->value('ar_number');
                $nextByYear[$year] = $lastAr !== null
                    ? ((int) substr((string) $lastAr, -4)) + 1
                    : 500;
            }

            do {
                $arNumber = 'AR-' . $year . '-' . str_pad((string) $nextByYear[$year], 4, '0', STR_PAD_LEFT);
                $nextByYear[$year]++;
            } while (DB::table('payments')->where('ar_number', $arNumber)->exists());

            DB::table('payments')->where('id', $p->id)->update(['ar_number' => $arNumber]);
            $this->command->info("{$p->receipt_number} -> {$arNumber}");
        }

        $remaining = DB::table('payments')->whereNull('ar_number')->count();
        $this->command->info("Done. Remaining without AR: {$remaining}");
    }

    protected function yearFor(?string $schoolYear, mixed $paymentDate): string
    {
        if ($schoolYear !== null && preg_match('/^(\d{4})-\d{4}$/', trim($schoolYear), $m) === 1) {
            return $m[1];
        }
        if ($paymentDate !== null) {
            try {
                return (new \DateTimeImmutable((string) $paymentDate))->format('Y');
            } catch (\Throwable) {
                // fall through to current year
            }
        }
        return date('Y');
    }
}
