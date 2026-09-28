<?php

namespace App\Console\Commands;

use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\Schedule;
use App\Models\Section;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairSchedules extends Command
{
    protected $signature = 'schedules:repair';
    protected $description = 'Report and repair scheduling data: rebuild classes/schedules section-aware, backfill enrollment class links and missing-only grades';

    public function handle(): int
    {
        $this->report('BEFORE');

        // 1. Fixed seeder: SHS plans from the DB sections table, one room per section,
        //    section+room-aware slot resolution, orphan-class deactivation, conflict-free
        //    schedule fallback for classes that lost theirs
        $this->call('db:seed', [
            '--class' => \Database\Seeders\TeachersClassesSchedulesSeeder::class,
            '--force' => true,
        ]);

        // 2. Active enrollments → class links (creates links for sections that previously
        //    had no classes, removes links pointing at deactivated classes)
        $linked = $this->backfillEnrollmentLinks();

        // 3. Assessments/grades ONLY for pairs that have none (existing grades untouched)
        [$assessments, $grades] = $this->backfillMissingGrades();

        $this->report('AFTER');
        $this->info("Enrollment class links ensured: {$linked} row(s); missing grades created: {$assessments} assessments, {$grades} grades.");

        return self::SUCCESS;
    }

    private function report(string $label): void
    {
        $year = active_school_year();

        $schedules = Schedule::whereHas('schoolClass', function ($q) use ($year) {
            $q->where('school_year', $year)->where('status', 'active');
        })->with('schoolClass')->get();

        $sectionConflicts = 0;
        $teacherConflicts = 0;
        $roomConflicts = 0;
        foreach ($schedules->groupBy('day_of_week') as $daySchedules) {
            $rows = $daySchedules->values();
            for ($i = 0; $i < $rows->count(); $i++) {
                for ($j = $i + 1; $j < $rows->count(); $j++) {
                    $a = $rows[$i];
                    $b = $rows[$j];
                    $overlap = $a->start_time < $b->end_time && $b->start_time < $a->end_time;
                    if (!$overlap) continue;

                    $ca = $a->schoolClass;
                    $cb = $b->schoolClass;
                    if ($ca && $cb && $ca->grade_level === $cb->grade_level && $ca->section === $cb->section) {
                        $sectionConflicts++;
                    }
                    if ($ca && $cb && $ca->teacher_id && $ca->teacher_id === $cb->teacher_id) {
                        $teacherConflicts++;
                    }
                    if ($a->room && $a->room === $b->room) {
                        $roomConflicts++;
                    }
                }
            }
        }

        $classesWithoutSchedule = Classes::where('school_year', $year)->where('status', 'active')
            ->whereDoesntHave('schedules')->count();

        $activeSections = Section::where('is_active', true)->get();
        $sectionsWithoutClasses = 0;
        foreach ($activeSections as $section) {
            $hasClass = Classes::where('school_year', $year)->where('status', 'active')
                ->where('grade_level', $section->grade_level)
                ->where('section', $section->section_name)
                ->exists();
            if (!$hasClass) $sectionsWithoutClasses++;
        }

        $enrollmentsWithoutLinks = Enrollment::where('school_year', $year)->where('status', 'Active')
            ->whereDoesntHave('subjects')->count();

        $this->info("[{$label}] section overlaps: {$sectionConflicts} | teacher overlaps: {$teacherConflicts} | room overlaps: {$roomConflicts} | active classes w/o schedule: {$classesWithoutSchedule} | active sections w/o classes: {$sectionsWithoutClasses} | active enrollments w/o class links: {$enrollmentsWithoutLinks}");
    }

    private function backfillEnrollmentLinks(): int
    {
        $year = active_school_year();

        $activeClassIds = Classes::where('school_year', $year)->where('status', 'active')->pluck('id');
        $enrollmentIds = Enrollment::where('school_year', $year)->where('status', 'Active')->pluck('id');

        // Drop links that point at inactive/deactivated classes (rename-drift orphans)
        if ($enrollmentIds->isNotEmpty() && $activeClassIds->isNotEmpty()) {
            DB::table('enrollment_subject')
                ->whereIn('enrollment_id', $enrollmentIds)
                ->whereNotIn('class_id', $activeClassIds)
                ->delete();
        }

        $classesByPair = Classes::where('school_year', $year)->where('status', 'active')->get()
            ->groupBy(fn($c) => $c->grade_level . '|' . $c->section);

        $enrollments = Enrollment::where('school_year', $year)->where('status', 'Active')
            ->with('section')->get();

        $count = 0;
        foreach ($enrollments as $enrollment) {
            if (!$enrollment->section) continue;
            $pair = $enrollment->section->grade_level . '|' . $enrollment->section->section_name;
            $classes = $classesByPair->get($pair);
            if (!$classes || $classes->isEmpty()) continue;

            // Same term preference as StudentsAndFeesSeeder (term-less classes first, else all)
            $termless = $classes->filter(fn($c) => $c->term === null);
            $use = $termless->isNotEmpty() ? $termless : $classes;

            $rows = $use->map(fn($c) => [
                'enrollment_id' => $enrollment->id,
                'class_id' => $c->id,
            ])->toArray();
            if (empty($rows)) continue;

            $before = DB::table('enrollment_subject')->whereIn('enrollment_id', [$enrollment->id])->count();
            DB::table('enrollment_subject')->insertOrIgnore($rows);
            $after = DB::table('enrollment_subject')->whereIn('enrollment_id', [$enrollment->id])->count();
            $count += max(0, $after - $before);
        }

        return $count;
    }

    private function backfillMissingGrades(): array
    {
        $year = active_school_year();
        $now = now()->toDateTimeString();

        // Replicate GradesAssessmentsSeeder's demo rows so behavior stays consistent
        $activeEnrollments = DB::table('enrollments')
            ->where('status', 'Active')
            ->where('school_year', $year)
            ->get();
        $demoFailIds = $activeEnrollments->take(2)->pluck('id')->toArray();
        $demoNoGradesId = $activeEnrollments->skip(2)->first()->id ?? null;

        $gradingPeriods = ['1st Term', '2nd Term', '3rd Term'];
        $weights = ['Written Work' => 0.20, 'Quiz' => 0.20, 'Seatwork' => 0.20, 'Exam' => 0.40];
        $titles = [
            'Written Work' => ['1st Term' => 'Written Work 1', '2nd Term' => 'Written Work 2', '3rd Term' => 'Written Work 3'],
            'Quiz'         => ['1st Term' => 'Quiz 1',         '2nd Term' => 'Quiz 2',         '3rd Term' => 'Quiz 3'],
            'Seatwork'     => ['1st Term' => 'Seatwork 1',     '2nd Term' => 'Seatwork 2',     '3rd Term' => 'Seatwork 3'],
            'Exam'         => ['1st Term' => 'Prelim Exam',    '2nd Term' => 'Midterm Exam',   '3rd Term' => 'Final Exam'],
        ];
        $assessmentTypes = [
            ['type' => 'Written Work', 'max_score' => 50],
            ['type' => 'Quiz',         'max_score' => 30],
            ['type' => 'Seatwork',     'max_score' => 50],
            ['type' => 'Exam',         'max_score' => 100],
        ];

        // Pairs (enrollment × class) that have no grades at all yet
        $pairs = DB::table('enrollment_subject as es')
            ->join('enrollments as e', 'e.id', '=', 'es.enrollment_id')
            ->join('classes as c', 'c.id', '=', 'es.class_id')
            ->where('e.school_year', $year)->where('e.status', 'Active')
            ->where('c.school_year', $year)->where('c.status', 'active')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw('1'))
                    ->from('grades as g')
                    ->whereColumn('g.enrollment_id', 'es.enrollment_id')
                    ->whereColumn('g.class_id', 'es.class_id');
            })
            ->get(['es.enrollment_id', 'es.class_id']);

        $assessmentRows = [];
        $gradeRows = [];
        foreach ($pairs as $pair) {
            if ($demoNoGradesId && $pair->enrollment_id == $demoNoGradesId) continue;

            $isFailDemo = in_array($pair->enrollment_id, $demoFailIds);
            foreach ($gradingPeriods as $period) {
                $baseScore = $isFailDemo ? mt_rand(4500, 6500) / 100 : mt_rand(6000, 9800) / 100;
                $weightedTotal = 0;

                foreach ($assessmentTypes as $at) {
                    $rawScore = round(min($at['max_score'], mt_rand((int) ($baseScore * 50), (int) ($baseScore * 100)) / 100), 2);
                    $percentage = ($rawScore / $at['max_score']) * 100;
                    $weightedTotal += $percentage * $weights[$at['type']];

                    $assessmentRows[] = [
                        'enrollment_id' => $pair->enrollment_id,
                        'class_id' => $pair->class_id,
                        'type' => $at['type'],
                        'grading_period' => $period,
                        'title' => $titles[$at['type']][$period],
                        'raw_score' => $rawScore,
                        'max_score' => $at['max_score'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }

                $gradeRows[] = [
                    'enrollment_id' => $pair->enrollment_id,
                    'class_id' => $pair->class_id,
                    'grading_period' => $period,
                    'final_grade' => max(40, min(100, round($weightedTotal, 2))),
                    'status' => 'Submitted',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        foreach (array_chunk($assessmentRows, 2000) as $chunk) {
            DB::table('assessments')->insertOrIgnore($chunk);
        }
        foreach (array_chunk($gradeRows, 2000) as $chunk) {
            DB::table('grades')->insertOrIgnore($chunk);
        }

        return [count($assessmentRows), count($gradeRows)];
    }
}
