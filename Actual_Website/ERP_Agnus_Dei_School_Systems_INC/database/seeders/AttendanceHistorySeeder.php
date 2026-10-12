<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AttendanceHistorySeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = active_school_year();
        $years = array_values(array_unique(['2024-2025', '2025-2026', $activeYear]));

        // Sample school days per class per term: bounded so 3 years stay fast.
        // History + current Terms 1-2 get full samples; current Term 3 gets ~70%.
        $daysPerTerm = 8;
        $currentTerm3Days = 6;

        $total = 0;

        foreach ($years as $year) {
            $isCurrent = $year === $activeYear;

            DB::transaction(function () use ($year, $isCurrent, $daysPerTerm, $currentTerm3Days, &$total): void {
                $classes = DB::table('classes')->where('school_year', $year)->get(['id', 'teacher_id']);

                if ($classes->isEmpty()) {
                    return;
                }

                foreach ($classes as $class) {
                    $classId = (int) $class->id;

                    // Same-year enrollments only: never mark cross-year rows.
                    $enrollmentIds = DB::table('enrollment_subject')
                        ->join('enrollments', 'enrollments.id', '=', 'enrollment_subject.enrollment_id')
                        ->where('enrollment_subject.class_id', $classId)
                        ->where('enrollments.status', 'Active')
                        ->where('enrollments.school_year', $year)
                        ->pluck('enrollments.id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->all();

                    if (empty($enrollmentIds)) {
                        continue;
                    }

                    $terms = [
                        '1st Term' => $isCurrent ? $daysPerTerm : $daysPerTerm,
                        '2nd Term' => $isCurrent ? $daysPerTerm : $daysPerTerm,
                        '3rd Term' => $isCurrent ? $currentTerm3Days : $daysPerTerm,
                    ];

                    foreach ($terms as $term => $dayCount) {
                        $days = $this->sampleWeekdays($year, $term, $dayCount, $isCurrent, $classId);

                        foreach ($days as $dayIndex => $day) {
                            $rows = [];
                            foreach ($enrollmentIds as $enrollmentId) {
                                // Deterministic ~95% present / 5% absent-late-excused.
                                $roll = ($enrollmentId * 31 + $classId * 17 + $dayIndex * 13) % 100;
                                $status = 'present';
                                if ($roll >= 99) {
                                    $status = 'absent';
                                } elseif ($roll >= 97) {
                                    $status = 'late';
                                } elseif ($roll >= 95) {
                                    $status = 'excused';
                                }

                                $rows[] = [
                                    'class_id' => $classId,
                                    'enrollment_id' => $enrollmentId,
                                    'marked_on' => $day,
                                    'status' => $status,
                                    'marked_by' => (int) $class->teacher_id,
                                    'created_at' => $day . ' 07:30:00',
                                    'updated_at' => $day . ' 07:30:00',
                                ];
                            }

                            // insertOrIgnore on the (class, enrollment, marked_on) unique key:
                            // re-runs add only missing days, never re-randomize kept marks.
                            foreach (array_chunk($rows, 1000) as $chunk) {
                                $inserted = DB::table('attendances')->insertOrIgnore($chunk);
                                $total += $inserted;
                            }
                        }
                    }
                }
            });
        }

        $this->command->info('Attendance history seeded: ' . $total . ' new marks (existing kept).');
    }

    /**
     * Deterministic spread of sample weekdays inside a term window.
     *
     * @return list<string>
     */
    private function sampleWeekdays(string $year, string $term, int $dayCount, bool $isCurrent, int $classId): array
    {
        $startYear = (int) substr($year, 0, 4);

        [$monthFrom, $dayFrom, $monthTo, $dayTo, $endYearOffset] = match ($term) {
            '1st Term' => [6, 1, 8, 31, 0],
            '2nd Term' => [9, 1, 11, 30, 0],
            default => [12, 1, 3, 31, 1],
        };

        $start = Carbon::create($startYear + ($term === '3rd Term' ? 0 : 0), $monthFrom, $dayFrom)?.startOfDay();
        $end = Carbon::create($startYear + $endYearOffset, $monthTo, $dayTo)?.startOfDay();

        if ($start === null || $end === null) {
            return [];
        }

        if ($isCurrent && $term === '3rd Term' && $end->isFuture()) {
            $end = Carbon::today();
        }

        if ($end->lt($start)) {
            return [];
        }

        $weekdays = [];
        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->isWeekday()) {
                $weekdays[] = $cursor->toDateString();
            }
            $cursor->addDay();
        }

        if (empty($weekdays)) {
            return [];
        }

        // Even spread with a per-class offset so classes don't share identical dates.
        $count = count($weekdays);
        $offset = $classId % $count;
        $step = max(1, (int) floor($count / $dayCount));
        $picked = [];
        for ($i = 0; $i < $dayCount; $i++) {
            $picked[] = $weekdays[($offset + $i * $step) % $count];
        }

        sort($picked);

        return array_values(array_unique($picked));
    }
}
