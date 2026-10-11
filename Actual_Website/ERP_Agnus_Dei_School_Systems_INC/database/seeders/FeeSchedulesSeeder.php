<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeSchedule;

class FeeSchedulesSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = active_school_year();

        // Spec seed-data-foundations.md §4: history first, then current,
        // then ready-next. Dedupe guards a future active year equalling
        // one of the fixed entries so re-runs stay idempotent.
        $schoolYears = array_values(array_unique([
            '2024-2025',
            '2025-2026',
            $activeYear,
            '2027-2028',
        ]));

        $feeStructure = [
            'Kinder'   => ['tuition' => 15000, 'misc' => 5000],
            'Grade 1'  => ['tuition' => 16000, 'misc' => 5200],
            'Grade 2'  => ['tuition' => 16000, 'misc' => 5200],
            'Grade 3'  => ['tuition' => 17000, 'misc' => 5400],
            'Grade 4'  => ['tuition' => 17000, 'misc' => 5400],
            'Grade 5'  => ['tuition' => 18000, 'misc' => 5600],
            'Grade 6'  => ['tuition' => 18000, 'misc' => 5600],
            'Grade 7'  => ['tuition' => 20000, 'misc' => 6000],
            'Grade 8'  => ['tuition' => 20000, 'misc' => 6000],
            'Grade 9'  => ['tuition' => 21000, 'misc' => 6200],
            'Grade 10' => ['tuition' => 21000, 'misc' => 6200],
            'Grade 11' => ['tuition' => 25000, 'misc' => 7000],
            'Grade 12' => ['tuition' => 25000, 'misc' => 7000],
        ];

        $terms = ['1st Term', '2nd Term', '3rd Term'];

        foreach ($schoolYears as $schoolYear) {
            foreach ($feeStructure as $gradeLevel => $fees) {
                foreach ($terms as $term) {
                    FeeSchedule::updateOrCreate(
                        [
                            'grade_level' => $gradeLevel,
                            'term' => $term,
                            'school_year' => $schoolYear,
                        ],
                        [
                            'tuition_fee' => $fees['tuition'] / 3,
                            'misc_fee' => $fees['misc'] / 3,
                            'misc_fee_items' => json_encode([
                                'books' => round($fees['misc'] * 0.4 / 3, 2),
                                'uniform' => round($fees['misc'] * 0.2 / 3, 2),
                                'id' => round($fees['misc'] * 0.1 / 3, 2),
                                'miscellaneous' => round($fees['misc'] * 0.3 / 3, 2),
                            ]),
                        ]
                    );
                }
            }
        }
    }
}
