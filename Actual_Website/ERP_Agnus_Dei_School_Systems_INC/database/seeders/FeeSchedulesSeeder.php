<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeeSchedulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeded precisely from the "PAYMENT SCHEME" ledger image.
     */
    public function run(): void
    {
        $schoolYear = '2026-2027';

        // 1. Seed Tuition Fees (from the image)
        $paymentScheme = [
            // SET A
            ['grade_level' => 'Kinder', 'tuition_fee' => 21000.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            
            // SET B
            ['grade_level' => 'Grade 1', 'tuition_fee' => 24748.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            
            // SET C
            ['grade_level' => 'Grade 2', 'tuition_fee' => 24748.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            
            // SET D
            ['grade_level' => 'Grade 3', 'tuition_fee' => 25748.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            
            // SET E
            ['grade_level' => 'Grade 4', 'tuition_fee' => 26748.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            ['grade_level' => 'Grade 5', 'tuition_fee' => 26748.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            ['grade_level' => 'Grade 6', 'tuition_fee' => 26748.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            
            // SET F (Standard JHS) -> Adding Grade 7 default as it belongs to JHS
            ['grade_level' => 'Grade 7', 'tuition_fee' => 31700.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            ['grade_level' => 'Grade 8', 'tuition_fee' => 31700.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            ['grade_level' => 'Grade 9', 'tuition_fee' => 31700.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            ['grade_level' => 'Grade 10', 'tuition_fee' => 31700.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],

            // SET G (JHS with ESC applied immediately per image)
            // Storing these slightly differently so the Cashier dropdown can target them
            ['grade_level' => 'Grade 7 - ESC', 'tuition_fee' => 22700.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            ['grade_level' => 'Grade 8 - ESC', 'tuition_fee' => 22700.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            ['grade_level' => 'Grade 9 - ESC', 'tuition_fee' => 22700.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            ['grade_level' => 'Grade 10 - ESC', 'tuition_fee' => 22700.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],

            // SET H (SHS - Handled by Voucher completely)
            ['grade_level' => 'Grade 11', 'tuition_fee' => 0.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
            ['grade_level' => 'Grade 12', 'tuition_fee' => 0.00, 'misc_fee' => 0.00, 'school_year' => $schoolYear],
        ];

        DB::table('fee_schedules')->insert($paymentScheme);

        /**
         * 2. DISCOUNTS IMPLEMENTATION LOGIC (From Master Capstone Document)
         * Because `student_ledgers` manages the discounts directly on instantiation 
         * per student via the Cashier Dashboard, the actual processing script in your
         * CashierController will compute these percentages against the 'tuition_fee'.
         * 
         * 1. PLAN A (Cash/Full Payment):
         *    - 10% discount on total tuition.
         * 
         * 2. PLAN B (Monthly):
         *    - Exactly 1,500 Downpayment upon enrollment. 
         *    - Balance divided into 10 months. (Matches exact computation on picture).
         * 
         * 3. HONORS DISCOUNT (Primary):
         *    - Rank 1: 100% Discount
         *    - Rank 2: 50% Discount
         *    - Rank 3: 25% Discount
         * 
         * 4. HONORS DISCOUNT (JHS):
         *    - Rank 1: 100% Discount
         *    - Rank 2: 75% Discount
         *    - Rank 3: 50% Discount
         * 
         * 5. FAMILY DISCOUNT:
         *    - 2nd Child: 10% Discount
         *    - 3rd Child: 15% Discount
         */
    }
}
