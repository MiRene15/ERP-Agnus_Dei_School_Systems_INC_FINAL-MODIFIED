<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GradesAssessmentsSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = active_school_year();
        $years = array_values(array_unique(['2024-2025', '2025-2026', $activeYear]));

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

        // Current Term 3 is partial: only these types are built (rest pending by absence).
        $currentTerm3Types = ['Written Work', 'Quiz'];

        // Exactly 2 showcase fails school-wide, history only (2024-2025, first class, 1st Term).
        // Deterministic: two smallest student_ids with active 2024-2025 enrollments.
        $showcaseStudentIds = DB::table('enrollments')
            ->where('status', 'Active')
            ->where('school_year', '2024-2025')
            ->orderBy('student_id')
            ->limit(2)
            ->pluck('student_id')
            ->all();

        $totalAssessments = 0;
        $totalGrades = 0;

        foreach ($years as $year) {
            $isCurrent = $year === $activeYear;

            DB::transaction(function () use (
                $year, $isCurrent, $gradingPeriods, $weights, $titles,
                $assessmentTypes, $currentTerm3Types, $showcaseStudentIds,
                &$totalAssessments, &$totalGrades
            ): void {
                $enrollments = DB::table('enrollments')
                    ->where('status', 'Active')
                    ->where('school_year', $year)
                    ->get();

                if ($enrollments->isEmpty()) {
                    return;
                }

                $this->ensureClassLinks($year, $enrollments);

                $enrollmentIds = $enrollments->pluck('id')->all();

                // Existing grade combos for this year: never re-randomize finished rows.
                $existingGrades = DB::table('grades')
                    ->whereIn('enrollment_id', $enrollmentIds)
                    ->get(['enrollment_id', 'class_id', 'grading_period'])
                    ->map(fn (object $row): string => $row->enrollment_id . '|' . $row->class_id . '|' . $row->grading_period)
                    ->flip()
                    ->all();

                $weekdays = $this->weekdaysInWindow($year, $isCurrent);
                if (empty($weekdays)) {
                    return;
                }
                $dayCount = count($weekdays);

                $assessmentRows = [];
                $gradeRows = [];
                // Track current Term-3 pending per class so no class ends up fully blank.
                $term3Totals = [];
                $term3Pending = [];

                foreach ($enrollments as $enrollment) {
                    $enrollmentId = (int) $enrollment->id;
                    $studentId = (int) $enrollment->student_id;

                    $enrolledClasses = DB::table('enrollment_subject')
                        ->where('enrollment_id', $enrollmentId)
                        ->pluck('class_id')
                        ->all();

                    if (empty($enrolledClasses)) {
                        continue;
                    }

                    $isShowcaseStudent = in_array($studentId, $showcaseStudentIds, true);
                    // Fails live in 2024-2025 only: one mark each, everything else passes.
                    $isFailYear = $year === '2024-2025';

                    foreach ($enrolledClasses as $classId) {
                        $classId = (int) $classId;

                        foreach ($gradingPeriods as $periodIndex => $period) {
                            $comboKey = $enrollmentId . '|' . $classId . '|' . $period;
                            if (isset($existingGrades[$comboKey])) {
                                continue;
                            }

                            $isTerm3 = $period === '3rd Term';
                            $typesForPeriod = ($isCurrent && $isTerm3)
                                ? array_values(array_filter(
                                    $assessmentTypes,
                                    fn (array $at): bool => in_array($at['type'], $currentTerm3Types, true)
                                ))
                                : $assessmentTypes;

                            if (empty($typesForPeriod)) {
                                continue;
                            }

                            // Current Term 3: ~30% of built grades stay pending (omitted row).
                            $isPending = false;
                            if ($isCurrent && $isTerm3) {
                                $isPending = (($enrollmentId + $classId) % 10) < 3;
                                $term3Totals[$classId] = ($term3Totals[$classId] ?? 0) + 1;
                                if ($isPending) {
                                    $term3Pending[$classId] = ($term3Pending[$classId] ?? 0) + 1;
                                }
                            }

                            $isFailMark = $isShowcaseStudent && $isFailYear && $period === '1st Term'
                                && $classId === (int) min($enrolledClasses);
                            $baseLow = $isFailMark ? 4500 : 7500;
                            $baseHigh = $isFailMark ? 6500 : 9800;
                            $baseScore = mt_rand($baseLow, $baseHigh) / 100;
                            $weightedTotal = 0.0;

                            foreach ($typesForPeriod as $typeIndex => $at) {
                                $day = $weekdays[($enrollmentId * 13 + $classId * 7 + $periodIndex * 29 + $typeIndex * 11) % $dayCount];
                                $stamped = $day . ' ' . str_pad((string) (8 + (($enrollmentId + $classId + $typeIndex) % 9)), 2, '0', STR_PAD_LEFT) . ':00:00';
                                $rawScore = round(min((float) $at['max_score'], mt_rand((int) ($baseScore * 50), (int) ($baseScore * 100)) / 100), 2);
                                $percentage = ($rawScore / (float) $at['max_score']) * 100;
                                $weightedTotal += $percentage * ((float) $weights[$at['type']]);

                                $assessmentRows[] = [
                                    'enrollment_id' => $enrollmentId,
                                    'class_id' => $classId,
                                    'type' => $at['type'],
                                    'grading_period' => $period,
                                    'title' => $titles[$at['type']][$period],
                                    'raw_score' => $rawScore,
                                    'max_score' => $at['max_score'],
                                    'assessment_date' => $day,
                                    'created_at' => $stamped,
                                    'updated_at' => $stamped,
                                ];
                            }

                            if ($isPending) {
                                continue;
                            }

                            $finalGrade = max(40.0, min(100.0, round($weightedTotal, 2)));
                            if ($isFailMark) {
                                // Force below passing so the showcase reads as a fail.
                                $finalGrade = round(mt_rand(6800, 7400) / 100, 2);
                            }

                            $gradeDay = $weekdays[($enrollmentId * 17 + $classId * 5 + $periodIndex * 31) % $dayCount];
                            $gradeStamped = $gradeDay . ' 15:00:00';

                            $gradeRows[] = [
                                'enrollment_id' => $enrollmentId,
                                'class_id' => $classId,
                                'grading_period' => $period,
                                'final_grade' => $finalGrade,
                                'status' => 'Submitted',
                                'created_at' => $gradeStamped,
                                'updated_at' => $gradeStamped,
                            ];
                        }
                    }
                }

                // Never leave a whole class blank in current Term 3: un-pend one row per fully-pending class.
                if ($isCurrent && !empty($term3Totals)) {
                    foreach ($term3Totals as $classId => $total) {
                        if (($term3Pending[$classId] ?? 0) >= $total && $total > 0) {
                            // Find the first omitted combo for this class and grade it normally.
                            foreach ($enrollments as $enrollment) {
                                $enrollmentId = (int) $enrollment->id;
                                if ((($enrollmentId + (int) $classId) % 10) < 3) {
                                    $gradeDay = $weekdays[($enrollmentId * 17 + (int) $classId * 5 + 2 * 31) % $dayCount];
                                    $gradeRows[] = [
                                        'enrollment_id' => $enrollmentId,
                                        'class_id' => (int) $classId,
                                        'grading_period' => '3rd Term',
                                        'final_grade' => round(mt_rand(7500, 9500) / 100, 2),
                                        'status' => 'Submitted',
                                        'created_at' => $gradeDay . ' 15:00:00',
                                        'updated_at' => $gradeDay . ' 15:00:00',
                                    ];
                                    break;
                                }
                            }
                        }
                    }
                }

                if (!empty($assessmentRows)) {
                    foreach (array_chunk($assessmentRows, 2000) as $chunk) {
                        DB::table('assessments')->upsert(
                            $chunk,
                            ['enrollment_id', 'class_id', 'type', 'grading_period'],
                            ['title', 'raw_score', 'max_score', 'assessment_date', 'updated_at']
                        );
                    }
                    $totalAssessments += count($assessmentRows);
                }

                if (!empty($gradeRows)) {
                    foreach (array_chunk($gradeRows, 2000) as $chunk) {
                        DB::table('grades')->upsert(
                            $chunk,
                            ['enrollment_id', 'class_id', 'grading_period'],
                            ['final_grade', 'status', 'updated_at']
                        );
                    }
                    $totalGrades += count($gradeRows);
                }
            });
        }

        $this->command->info('Grades and Assessments seeded: ' . $totalAssessments . ' assessments, ' . $totalGrades . ' grades.');
    }

    /**
     * Link orphan enrollments to same-section twin classes first so every
     * enrollment has classes in its own school year before grading.
     *
     * @param object $enrollments
     */
    private function ensureClassLinks(string $year, object $enrollments): void
    {
        foreach ($enrollments as $enrollment) {
            $enrollmentId = (int) $enrollment->id;

            $hasLinks = DB::table('enrollment_subject')
                ->where('enrollment_id', $enrollmentId)
                ->exists();

            if ($hasLinks) {
                continue;
            }

            $section = DB::table('sections')->where('id', $enrollment->section_id)->first();

            $candidates = collect();
            if ($section !== null) {
                $candidates = DB::table('classes')
                    ->where('school_year', $year)
                    ->where('grade_level', $section->grade_level)
                    ->where('section', $section->section_name)
                    ->pluck('id');
            }

            if ($candidates->isEmpty() && $section !== null) {
                $candidates = DB::table('classes')
                    ->where('school_year', $year)
                    ->where('grade_level', $section->grade_level)
                    ->pluck('id');
            }

            if ($candidates->isEmpty()) {
                $candidates = DB::table('classes')
                    ->where('school_year', $year)
                    ->limit(8)
                    ->pluck('id');
            }

            $now = Carbon::now()->toDateTimeString();
            foreach ($candidates as $classId) {
                DB::table('enrollment_subject')->insertOrIgnore([
                    'enrollment_id' => $enrollmentId,
                    'class_id' => (int) $classId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * @return list<string> Weekday dates (Y-m-d) inside the Jun–Mar window.
     */
    private function weekdaysInWindow(string $year, bool $isCurrent): array
    {
        $startYear = (int) substr($year, 0, 4);
        $start = Carbon::create($startYear, 6, 1)->startOfDay();
        $end = Carbon::create($startYear + 1, 3, 31)->startOfDay();

        if ($start === null || $end === null) {
            return [];
        }

        if ($isCurrent && $end->isFuture()) {
            $end = Carbon::today();
        }

        if ($end->lt($start)) {
            return [];
        }

        $days = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->isWeekday()) {
                $days[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        return $days;
    }
}
