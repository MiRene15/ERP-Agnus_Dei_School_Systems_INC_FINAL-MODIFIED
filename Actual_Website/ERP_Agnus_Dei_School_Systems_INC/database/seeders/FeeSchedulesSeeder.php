<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeeSchedulesSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = active_school_year();

        $terms = ['1st Term', '2nd Term', '3rd Term'];

        $feesPerGrade = [
            'Kinder'   => ['tuition' => 21000.00, 'misc' => 4500.00],
            'Grade 1'  => ['tuition' => 24748.00, 'misc' => 5200.00],
            'Grade 2'  => ['tuition' => 24748.00, 'misc' => 5200.00],
            'Grade 3'  => ['tuition' => 25748.00, 'misc' => 5500.00],
            'Grade 4'  => ['tuition' => 26748.00, 'misc' => 5800.00],
            'Grade 5'  => ['tuition' => 26748.00, 'misc' => 5800.00],
            'Grade 6'  => ['tuition' => 26748.00, 'misc' => 5800.00],
            'Grade 7'  => ['tuition' => 31700.00, 'misc' => 7500.00],
            'Grade 8'  => ['tuition' => 31700.00, 'misc' => 7500.00],
            'Grade 9'  => ['tuition' => 31700.00, 'misc' => 8200.00],
            'Grade 10' => ['tuition' => 31700.00, 'misc' => 8200.00],
            'Grade 11' => ['tuition' => 15000.00, 'misc' => 9500.00],
            'Grade 12' => ['tuition' => 15000.00, 'misc' => 10200.00],
        ];

        $rows = [];

        foreach ($feesPerGrade as $grade => $fees) {
            foreach ($terms as $term) {
                $rows[] = [
                    'grade_level' => $grade,
                    'term'    => $term,
                    'tuition_fee' => $fees['tuition'],
                    'misc_fee'    => $fees['misc'],
                    'school_year' => $schoolYear,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ];
            }
        }

        DB::table('fee_schedules')->where('school_year', $schoolYear)->delete();
        DB::table('fee_schedules')->insert($rows);
    }
}
