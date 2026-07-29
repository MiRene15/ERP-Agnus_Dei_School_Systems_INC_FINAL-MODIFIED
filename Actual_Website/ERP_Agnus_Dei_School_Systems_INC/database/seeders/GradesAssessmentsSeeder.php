<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Grade;
use App\Models\Assessment;

class GradesAssessmentsSeeder extends Seeder
{
    private $faker;

    public function run()
    {
        $this->faker = \Faker\Factory::create();

        $activeEnrollments = DB::table('enrollments')
            ->where('status', 'Active')
            ->where('school_year', active_school_year())
            ->get();

        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];

        foreach ($activeEnrollments as $enrollment) {
            $enrolledClasses = DB::table('enrollment_subject')
                ->where('enrollment_id', $enrollment->id)
                ->join('classes', 'enrollment_subject.class_id', '=', 'classes.id')
                ->select('classes.id as class_id', 'classes.subject_id', 'classes.teacher_id')
                ->get();

            foreach ($enrolledClasses as $class) {
                foreach ($gradingPeriods as $period) {
                    $this->createGradesAndAssessments($enrollment->id, $class->class_id, $period);
                }
            }
        }

        $this->command->info('Grades and Assessments seeded successfully.');
    }

    private function createGradesAndAssessments(int $enrollmentId, int $classId, string $period): void
    {
        $baseScore = $this->faker->randomFloat(2, 60, 98);

        $assessments = [
            [
                'type' => 'Written Work',
                'title' => $this->generateTitle('Written Work', $period),
                'max_score' => 50,
            ],
            [
                'type' => 'Performance Task',
                'title' => $this->generateTitle('Performance Task', $period),
                'max_score' => 50,
            ],
            [
                'type' => 'Semestral Assessment',
                'title' => $this->generateTitle('Semestral Assessment', $period),
                'max_score' => 100,
            ],
        ];

        $weightedTotal = 0;

        foreach ($assessments as $assess) {
            $rawScore = round($this->faker->randomFloat(2, $baseScore * 0.5, $baseScore), 2);
            $rawScore = min($rawScore, $assess['max_score']);

            Assessment::updateOrCreate(
                [
                    'enrollment_id' => $enrollmentId,
                    'class_id' => $classId,
                    'type' => $assess['type'],
                    'grading_period' => $period,
                ],
                [
                    'title' => $assess['title'],
                    'raw_score' => $rawScore,
                    'max_score' => $assess['max_score'],
                ]
            );

            $percentage = ($rawScore / $assess['max_score']) * 100;

            if ($assess['type'] === 'Written Work') {
                $weightedTotal += $percentage * 0.30;
            } elseif ($assess['type'] === 'Performance Task') {
                $weightedTotal += $percentage * 0.40;
            } else {
                $weightedTotal += $percentage * 0.30;
            }
        }

        $finalGrade = round($weightedTotal, 2);
        $finalGrade = max(40, min(100, $finalGrade));

        Grade::updateOrCreate(
            [
                'enrollment_id' => $enrollmentId,
                'class_id' => $classId,
                'grading_period' => $period,
            ],
            [
                'final_grade' => $finalGrade,
                'status' => 'Submitted',
            ]
        );
    }

    private function generateTitle(string $type, string $period): string
    {
        $titles = [
            'Written Work' => [
                '1st Term' => 'Quarter 1 Quiz & Long Quiz',
                '2nd Term' => 'Quarter 3 Quiz & Long Quiz',
                '3rd Term' => 'Final Drill & Quiz',
            ],
            'Performance Task' => [
                '1st Term' => 'Group Project & Report',
                '2nd Term' => 'Research & Presentation',
                '3rd Term' => 'Final Performance Task',
            ],
            'Semestral Assessment' => [
                '1st Term' => 'Midterm Examination',
                '2nd Term' => 'Final Examination',
                '3rd Term' => 'Comprehensive Exam',
            ],
        ];

        return $titles[$type][$period] ?? "{$type} - {$period}";
    }
}
