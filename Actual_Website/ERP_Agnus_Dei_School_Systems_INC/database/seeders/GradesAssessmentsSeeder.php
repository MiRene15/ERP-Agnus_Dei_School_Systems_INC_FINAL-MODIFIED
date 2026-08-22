<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradesAssessmentsSeeder extends Seeder
{
    public function run(): void
    {
        $activeEnrollments = DB::table('enrollments')
            ->where('status', 'Active')
            ->where('school_year', active_school_year())
            ->get();

        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];

        $weights = [
            'Written Work' => 0.20,
            'Quiz' => 0.20,
            'Seatwork' => 0.20,
            'Exam' => 0.40,
        ];

        $titles = [
            'Written Work' => ['1st Term' => 'Written Work 1', '2nd Term' => 'Written Work 2', '3rd Term' => 'Written Work 3'],
            'Quiz'         => ['1st Term' => 'Quiz 1',         '2nd Term' => 'Quiz 2',         '3rd Term' => 'Quiz 3'],
            'Seatwork'     => ['1st Term' => 'Seatwork 1',     '2nd Term' => 'Seatwork 2',     '3rd Term' => 'Seatwork 3'],
            'Exam'         => ['1st Term' => 'Prelim Exam',    '2nd Term' => 'Midterm Exam',    '3rd Term' => 'Final Exam'],
        ];

        $assessmentTypes = [
            ['type' => 'Written Work', 'max_score' => 50],
            ['type' => 'Quiz',         'max_score' => 30],
            ['type' => 'Seatwork',     'max_score' => 50],
            ['type' => 'Exam',         'max_score' => 100],
        ];

        $assessmentRows = [];
        $gradeRows = [];
        $now = now()->toDateTimeString();

        $demoFailIds = $activeEnrollments->take(2)->pluck('id')->toArray();
        $demoNoGradesId = $activeEnrollments->skip(2)->first()->id ?? null;

        foreach ($activeEnrollments as $enrollment) {
            $isNoGrades = $demoNoGradesId && $enrollment->id === $demoNoGradesId;
            if ($isNoGrades) continue; // leave this enrollment with no grades for demo (No grades badge)

            $enrolledClasses = DB::table('enrollment_subject')
                ->where('enrollment_id', $enrollment->id)
                ->pluck('class_id');

            foreach ($enrolledClasses as $classId) {
                foreach ($gradingPeriods as $period) {
                    $isFailDemo = in_array($enrollment->id, $demoFailIds);
                    $baseScore = $isFailDemo ? mt_rand(4500, 6500) / 100 : mt_rand(6000, 9800) / 100;
                    $weightedTotal = 0;

                    foreach ($assessmentTypes as $at) {
                        $rawScore = round(min($at['max_score'], mt_rand((int)($baseScore * 50), (int)($baseScore * 100)) / 100), 2);
                        $percentage = ($rawScore / $at['max_score']) * 100;
                        $weightedTotal += $percentage * ($weights[$at['type']]);

                        $assessmentRows[] = [
                            'enrollment_id' => $enrollment->id,
                            'class_id' => $classId,
                            'type' => $at['type'],
                            'grading_period' => $period,
                            'title' => $titles[$at['type']][$period],
                            'raw_score' => $rawScore,
                            'max_score' => $at['max_score'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    $finalGrade = max(40, min(100, round($weightedTotal, 2)));

                    $gradeRows[] = [
                        'enrollment_id' => $enrollment->id,
                        'class_id' => $classId,
                        'grading_period' => $period,
                        'final_grade' => $finalGrade,
                        'status' => 'Submitted',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        if (!empty($assessmentRows)) {
            foreach (array_chunk($assessmentRows, 2000) as $chunk) {
                DB::table('assessments')->upsert(
                    $chunk,
                    ['enrollment_id', 'class_id', 'type', 'grading_period'],
                    ['title', 'raw_score', 'max_score', 'updated_at']
                );
            }
        }

        if (!empty($gradeRows)) {
            foreach (array_chunk($gradeRows, 2000) as $chunk) {
                DB::table('grades')->upsert(
                    $chunk,
                    ['enrollment_id', 'class_id', 'grading_period'],
                    ['final_grade', 'status', 'updated_at']
                );
            }
        }

        $this->command->info('Grades and Assessments seeded: ' . count($assessmentRows) . ' assessments, ' . count($gradeRows) . ' grades.');
    }
}
