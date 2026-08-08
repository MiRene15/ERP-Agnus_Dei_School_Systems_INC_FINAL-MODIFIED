<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FixArNumbersSeeder extends Seeder
{
    public function run(): void
    {
        $payments = DB::table('payments')->whereNull('ar_number')->orderBy('id')->get();
        $year = date('Y');
        $next = 500;

        foreach ($payments as $p) {
            $arNumber = 'AR-' . $year . '-' . str_pad($next++, 4, '0', STR_PAD_LEFT);
            DB::table('payments')->where('id', $p->id)->update(['ar_number' => $arNumber]);
            $this->command->info("{$p->receipt_number} -> {$arNumber}");
        }

        $remaining = DB::table('payments')->whereNull('ar_number')->count();
        $this->command->info("Done. Remaining without AR: {$remaining}");
    }
}
