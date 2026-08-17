<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FeeSchedule;

class FeeSchedulesSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = '2026-2027';

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
