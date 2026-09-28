<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeachersClassesSchedulesSeeder extends Seeder
{
    private array $teacherAvailability = [];
    private array $sectionAvailability = [];
    private array $roomAvailability = [];

    private array $timeSlots = [
        ['07:00:00', '08:00:00'],
        ['08:00:00', '09:00:00'],
        ['09:00:00', '10:00:00'],
        ['10:00:00', '11:00:00'],
        ['11:00:00', '12:00:00'],
        ['13:00:00', '14:00:00'],
        ['14:00:00', '15:00:00'],
        ['15:00:00', '16:00:00'],
    ];

    private array $dayPatterns = [
        ['Monday', 'Wednesday'],
        ['Tuesday', 'Thursday'],
        ['Monday', 'Thursday'],
        ['Tuesday', 'Friday'],
        ['Wednesday', 'Friday'],
    ];

    public function run(): void
    {
        $schoolYear = active_school_year();

        $teacherEmailsByDept = [
            'Elementary' => [
                'maria.santos@agnusdei.local', 'jose.reyes@agnusdei.local', 'ana.cruz@agnusdei.local',
                'paolo.garcia@agnusdei.local', 'rosa.villanueva@agnusdei.local', 'daniel.mercado@agnusdei.local',
                'teresa.natividad@agnusdei.local', 'nico.salazar@agnusdei.local',
            ],
            'Junior High School' => [
                'liza.mendoza@agnusdei.local', 'mark.torres@agnusdei.local', 'rina.flores@agnusdei.local',
                'dennis.aquino@agnusdei.local', 'grace.domingo@agnusdei.local', 'carlo.bautista@agnusdei.local',
            ],
            'Senior High School' => [
                'carla.navarro@agnusdei.local', 'vincent.luna@agnusdei.local', 'sheila.ramos@agnusdei.local',
                'adrian.castro@agnusdei.local', 'elaine.sy@agnusdei.local', 'patrick.lopez@agnusdei.local',
            ],
        ];

        $teacherIdsByDept = [];
        foreach ($teacherEmailsByDept as $dept => $emails) {
            $teacherIdsByDept[$dept] = User::whereIn('email', $emails)->pluck('id')->toArray();
        }

        $subjectMap = Subject::pluck('id', 'subject_code')->toArray();
        $sectionsByGrade = Section::where('is_active', true)
            ->orderBy('grade_level')->orderBy('section_name')
            ->get()->groupBy('grade_level');

        $gradeSubjectCodes = [
            'Kinder' => ['K-ENG', 'K-MAT', 'K-SCI', 'K-READ', 'K-MAPEH', 'K-ESP'],
            'Grade 1' => ['G1-ENG', 'G1-FIL', 'G1-MAT', 'G1-ESP', 'G1-MAPEH', 'G1-AP'],
            'Grade 2' => ['G2-ENG', 'G2-FIL', 'G2-MAT', 'G2-ESP', 'G2-MAPEH', 'G2-AP'],
            'Grade 3' => ['G3-ENG', 'G3-FIL', 'G3-MAT', 'G3-SCI', 'G3-ESP', 'G3-MAPEH', 'G3-AP'],
            'Grade 4' => ['G4-ENG', 'G4-FIL', 'G4-MAT', 'G4-SCI', 'G4-AP', 'G4-ESP', 'G4-MAPEH', 'G4-EPP'],
            'Grade 5' => ['G5-ENG', 'G5-FIL', 'G5-MAT', 'G5-SCI', 'G5-AP', 'G5-ESP', 'G5-MAPEH', 'G5-EPP'],
            'Grade 6' => ['G6-ENG', 'G6-FIL', 'G6-MAT', 'G6-SCI', 'G6-AP', 'G6-ESP', 'G6-MAPEH', 'G6-EPP'],
            'Grade 7' => ['G7-ENG', 'G7-FIL', 'G7-MAT', 'G7-SCI', 'G7-AP', 'G7-ESP', 'G7-MAPEH', 'G7-TLE'],
            'Grade 8' => ['G8-ENG', 'G8-FIL', 'G8-MAT', 'G8-SCI', 'G8-AP', 'G8-ESP', 'G8-MAPEH', 'G8-TLE'],
            'Grade 9' => ['G9-ENG', 'G9-FIL', 'G9-MAT', 'G9-SCI', 'G9-AP', 'G9-ESP', 'G9-MAPEH', 'G9-TLE'],
            'Grade 10' => ['G10-ENG', 'G10-FIL', 'G10-MAT', 'G10-SCI', 'G10-AP', 'G10-ESP', 'G10-MAPEH', 'G10-TLE'],
        ];

        // Map legacy generic section names to new saint/strand names for existing DB migration.
        // Only rename when the sections table actually holds the NEW names — on DBs that still
        // use generic names (A/B/STEM-A), renaming classes would orphan them from their sections.
        $legacySectionMap = [
            'Kinder' => ['A' => 'St. Agnes', 'B' => 'St. Clare'],
            'Grade 1' => ['A' => 'St. Francis', 'B' => 'St. Dominic'],
            'Grade 2' => ['A' => 'St. Catherine', 'B' => 'St. Therese'],
            'Grade 3' => ['A' => 'St. Augustine', 'B' => 'St. Benedict'],
            'Grade 4' => ['A' => 'St. Joseph', 'B' => 'St. Michael'],
            'Grade 5' => ['A' => 'St. John', 'B' => 'St. Paul'],
            'Grade 6' => ['A' => 'St. Peter', 'B' => 'St. Andrew'],
            'Grade 7' => ['A' => 'Charity', 'B' => 'Hope'],
            'Grade 8' => ['A' => 'Faith', 'B' => 'Love'],
            'Grade 9' => ['A' => 'Wisdom', 'B' => 'Courage'],
            'Grade 10' => ['A' => 'Justice', 'B' => 'Temperance'],
            'Grade 11' => ['STEM-A' => 'STEM - St. Thomas Aquinas', 'ABM-A' => 'ABM - St. Matthew', 'HUMSS-A' => 'HUMSS - St. Augustine', 'GAS-A' => 'GAS - St. Scholastica'],
            'Grade 12' => ['STEM-A' => 'STEM - St. Albert', 'ABM-A' => 'ABM - St. Luke', 'HUMSS-A' => 'HUMSS - St. Jerome', 'GAS-A' => 'GAS - St. Benedict'],
        ];
        foreach ($legacySectionMap as $gl => $map) {
            $sectionNames = ($sectionsByGrade[$gl] ?? collect())->pluck('section_name');
            if ($sectionNames->isEmpty()) continue;
            foreach ($map as $old => $new) {
                if ($sectionNames->contains($new) && !$sectionNames->contains($old)) {
                    Classes::where('grade_level', $gl)->where('section', $old)->update(['section' => $new]);
                }
            }
        }

        // Normalize blank terms ('' vs NULL would break updateOrCreate keys and duplicate classes)
        Classes::where('school_year', $schoolYear)->where('term', '')->update(['term' => null]);

        // Remove current-year classes whose (grade, section) no longer exists among active
        // sections (rename-drift orphans). DELETE (not deactivate) so the FK cascade clears
        // their grades, assessments, schedules and enrollment links — otherwise students would
        // see duplicate subject grades from both the dead and the replacement class.
        $activePairs = [];
        foreach ($sectionsByGrade as $gl => $secs) {
            foreach ($secs as $sec) {
                $activePairs[$gl . '|' . $sec->section_name] = true;
            }
        }
        $orphans = Classes::where('school_year', $schoolYear)->where('status', 'active')->get()
            ->filter(fn($c) => !isset($activePairs[$c->grade_level . '|' . $c->section]));
        foreach ($orphans as $orphan) {
            DB::table('enrollment_subject')->where('class_id', $orphan->id)->delete();
            Schedule::where('class_id', $orphan->id)->delete();
            $orphan->delete();
        }
        if ($orphans->isNotEmpty()) {
            $this->warn("Removed {$orphans->count()} orphaned class(es) whose sections are no longer active.");
        }

        // SHS subject codes by strand and grade (used to build plans from the DB sections table)
        $shsSubjectCodes = [
            'STEM' => [
                'Grade 11' => ['SHS-OC', 'SHS-RW', 'SHS-GMATH', 'SHS-ELS', 'SHS-PD', 'SHS-PEH', 'STEM-PCAL', 'STEM-BCAL'],
                'Grade 12' => ['SHS-EAPP', 'SHS-PR2', 'SHS-EMTECH', 'SHS-III', 'STEM-BIO1', 'STEM-CHEM1', 'STEM-PHY1'],
            ],
            'ABM' => [
                'Grade 11' => ['SHS-OC', 'SHS-RW', 'SHS-GMATH', 'SHS-UCSP', 'SHS-PEH', 'ABM-BMATH', 'ABM-OAM', 'ABM-FABM1'],
                'Grade 12' => ['SHS-EAPP', 'SHS-FPL', 'SHS-ENTREP', 'SHS-III', 'ABM-FABM2', 'SHS-PR2'],
            ],
            'HUMSS' => [
                'Grade 11' => ['SHS-OC', 'SHS-21CL', 'SHS-UCSP', 'SHS-PEH', 'HUMSS-DISS', 'HUMSS-DIASS', 'SHS-PR1'],
                'Grade 12' => ['SHS-EAPP', 'SHS-FPL', 'HUMSS-CREW', 'HUMSS-TNCT', 'SHS-III', 'SHS-PR2'],
            ],
            'GAS' => [
                'Grade 11' => ['SHS-OC', 'SHS-RW', 'SHS-MIL', 'SHS-UCSP', 'SHS-PEH', 'GAS-HGP'],
                'Grade 12' => ['SHS-EAPP', 'SHS-ENTREP', 'SHS-EMTECH', 'SHS-III', 'GAS-ORG'],
            ],
        ];

        $plans = [];
        $roomCounters = ['K' => 100, 'E' => 100, 'J' => 100, 'S' => 200];

        foreach ($gradeSubjectCodes as $gradeLevel => $subjectCodes) {
            $department = $this->departmentForGrade($gradeLevel);
            $prefix = $gradeLevel === 'Kinder' ? 'K' : ($department === 'Elementary' ? 'E' : 'J');

            foreach (($sectionsByGrade[$gradeLevel] ?? collect()) as $section) {
                $roomCounters[$prefix]++;
                $plans[] = [
                    'grade_level' => $gradeLevel,
                    'section' => $section->section_name,
                    'term' => null,
                    'room' => $prefix . '-' . str_pad((string) $roomCounters[$prefix], 3, '0', STR_PAD_LEFT),
                    'department' => $department,
                    'subject_codes' => $subjectCodes,
                ];
            }
        }

        // SHS plans read straight from the DB sections table (hardcoded names drifted from live
        // data and left sections like Grade 12 GAS with zero classes)
        $shsIndex = 0;
        foreach (['Grade 11', 'Grade 12'] as $gradeLevel) {
            foreach (($sectionsByGrade[$gradeLevel] ?? collect()) as $section) {
                $shsIndex++;
                preg_match('/^(STEM|ABM|HUMSS|GAS)\b/i', $section->section_name, $m);
                $strand = $m ? strtoupper($m[1]) : ['STEM', 'ABM', 'HUMSS', 'GAS'][$shsIndex % 4];
                $roomCounters['S']++;
                $plans[] = [
                    'grade_level' => $gradeLevel,
                    'section' => $section->section_name,
                    'term' => $gradeLevel === 'Grade 11' ? '1st Term' : '2nd Term',
                    'room' => 'S-' . str_pad((string) $roomCounters['S'], 3, '0', STR_PAD_LEFT),
                    'department' => 'Senior High School',
                    'subject_codes' => $shsSubjectCodes[$strand][$gradeLevel],
                ];
            }
        }

        // Pre-load every existing schedule of active current-year classes so re-runs and
        // untouched classes can't be double-booked (teacher + section + room availability)
        $existingSchedules = Schedule::whereHas('schoolClass', function ($q) use ($schoolYear) {
            $q->where('school_year', $schoolYear)->where('status', 'active');
        })->with('schoolClass')->get();
        foreach ($existingSchedules as $existing) {
            if ($existing->schoolClass) {
                $this->reserveFor($existing->schoolClass, $existing->day_of_week, $existing->start_time, $existing->end_time, $existing->room);
            }
        }

        foreach ($plans as $plan) {
            $teacherPool = $teacherIdsByDept[$plan['department']] ?? [];
            if (empty($teacherPool)) continue;

            $sectionKey = $plan['grade_level'] . '|' . $plan['section'];
            $matched = 0;

            foreach ($plan['subject_codes'] as $subjectIndex => $subjectCode) {
                if (!isset($subjectMap[$subjectCode])) continue;
                $matched++;

                $seedKey = abs(crc32($plan['grade_level'] . '|' . $plan['section'] . '|' . $subjectCode));
                $key = [
                    'subject_id' => $subjectMap[$subjectCode],
                    'section' => $plan['section'],
                    'grade_level' => $plan['grade_level'],
                    'school_year' => $schoolYear,
                    'term' => $plan['term'],
                ];

                // Release this class's previous reservations first so it can keep its own slot
                $existingClass = Classes::where($key)->first();
                $oldSchedules = collect();
                if ($existingClass) {
                    $oldSchedules = Schedule::where('class_id', $existingClass->id)->get();
                    foreach ($oldSchedules as $old) {
                        $this->releaseFor($existingClass, $old->day_of_week, $old->start_time, $old->end_time, $old->room);
                    }
                }

                // Resolve a slot free for the teacher AND the section AND the room
                $assignment = $this->resolveAssignment($teacherPool, $seedKey, $sectionKey, $plan['room']);

                if (!$assignment) {
                    // No free slot anywhere — keep the existing schedule instead of forcing a conflict
                    if ($existingClass) {
                        foreach ($oldSchedules as $old) {
                            $this->reserveFor($existingClass, $old->day_of_week, $old->start_time, $old->end_time, $old->room);
                        }
                    }
                    if ($oldSchedules->isEmpty()) {
                        $this->warn("No conflict-free slot for {$plan['grade_level']} {$plan['section']} — {$subjectCode} left unscheduled.");
                    }
                    continue;
                }

                $class = Classes::updateOrCreate(
                    $key,
                    [
                        'teacher_id' => $assignment['teacher_id'],
                        'room' => $plan['room'],
                        'capacity' => 30,
                        'is_advisory' => $subjectIndex === 0,
                        'status' => 'active',
                    ]
                );

                Schedule::where('class_id', $class->id)
                    ->whereNotIn('day_of_week', $assignment['days'])
                    ->delete();

                foreach ($assignment['days'] as $day) {
                    Schedule::updateOrCreate(
                        ['class_id' => $class->id, 'day_of_week' => $day],
                        [
                            'start_time' => $assignment['slot'][0],
                            'end_time' => $assignment['slot'][1],
                            'room' => $plan['room'],
                        ]
                    );
                }
            }

            if ($matched === 0) {
                $this->warn("Plan produced no classes (missing subjects?): {$plan['grade_level']} / {$plan['section']}");
            }
        }

        // Post-repair: ensure every active Section has an adviser and every active Class has a teacher
        $allTeacherIds = User::where('role_id', 4)->where('status', 'active')->pluck('id')->toArray();
        if (!empty($allTeacherIds)) {
            $orphanSections = Section::where('is_active', true)->whereNull('adviser_id')->get();
            foreach ($orphanSections as $idx => $sec) {
                $sec->update(['adviser_id' => $allTeacherIds[$idx % count($allTeacherIds)]]);
            }
            $invalidAdviserSections = Section::where('is_active', true)->whereNotIn('adviser_id', $allTeacherIds)->whereNotNull('adviser_id')->get();
            foreach ($invalidAdviserSections as $idx => $sec) {
                $sec->update(['adviser_id' => $allTeacherIds[$idx % count($allTeacherIds)]]);
            }
            $orphanClasses = Classes::where('status', 'active')->where('school_year', $schoolYear)->whereNull('teacher_id')->get();
            foreach ($orphanClasses as $idx => $cls) {
                $cls->update(['teacher_id' => $allTeacherIds[$idx % count($allTeacherIds)]]);
            }
            $invalidTeacherClasses = Classes::where('status', 'active')->where('school_year', $schoolYear)->whereNotIn('teacher_id', $allTeacherIds)->whereNotNull('teacher_id')->get();
            foreach ($invalidTeacherClasses as $idx => $cls) {
                $cls->update(['teacher_id' => $allTeacherIds[$idx % count($allTeacherIds)]]);
            }
        }

        // Ensure every active class has at least one schedule — search a DB-verified free slot
        // (the old fallback forced Monday 07:00 with no conflict check)
        $classesWithoutSchedule = Classes::where('status', 'active')
            ->where('school_year', $schoolYear)
            ->whereDoesntHave('schedules')
            ->get();
        foreach ($classesWithoutSchedule as $cls) {
            $free = $this->findFreeSlotInDb($cls);
            if (!$free) {
                $this->warn("No conflict-free slot available for class #{$cls->id} ({$cls->grade_level} {$cls->section}) — left unscheduled.");
                continue;
            }
            Schedule::create([
                'class_id' => $cls->id,
                'day_of_week' => $free['day'],
                'start_time' => $free['slot'][0],
                'end_time' => $free['slot'][1],
                'room' => $cls->room,
            ]);
            $this->reserveFor($cls, $free['day'], $free['slot'][0], $free['slot'][1], $cls->room);
            $this->info("Scheduled class #{$cls->id} ({$cls->grade_level} {$cls->section}) on {$free['day']} {$free['slot'][0]}.");
        }
    }

    private function departmentForGrade(string $gradeLevel): string
    {
        if ($gradeLevel === 'Kinder') return 'Elementary';
        $gradeNumber = (int) filter_var($gradeLevel, FILTER_SANITIZE_NUMBER_INT);
        if ($gradeNumber >= 11) return 'Senior High School';
        if ($gradeNumber >= 7) return 'Junior High School';
        return 'Elementary';
    }

    /**
     * Find a slot free for the teacher AND section AND room across all day patterns.
     * Returns null when every combination is blocked (caller keeps the old schedule).
     */
    private function resolveAssignment(array $teacherPool, int $seedKey, string $sectionKey, string $room): ?array
    {
        $combos = count($this->timeSlots) * count($this->dayPatterns);
        for ($i = 0; $i < $combos; $i++) {
            $slot = $this->timeSlots[($seedKey + $i) % count($this->timeSlots)];
            $days = $this->dayPatterns[($seedKey + $i) % count($this->dayPatterns)];

            for ($teacherOffset = 0; $teacherOffset < count($teacherPool); $teacherOffset++) {
                $teacherId = $teacherPool[($seedKey + $teacherOffset) % count($teacherPool)];
                if ($this->assignmentAvailable($teacherId, $sectionKey, $room, $days, $slot)) {
                    $this->reserveAssignment($teacherId, $sectionKey, $room, $days, $slot);
                    return ['teacher_id' => $teacherId, 'slot' => $slot, 'days' => $days];
                }
            }
        }

        return null;
    }

    private function assignmentAvailable(int $teacherId, string $sectionKey, string $room, array $days, array $slot): bool
    {
        $slotKey = $slot[0] . '|' . $slot[1];
        foreach ($days as $day) {
            if (!empty($this->teacherAvailability[$teacherId][$day][$slotKey])) return false;
            if (!empty($this->sectionAvailability[$sectionKey][$day][$slotKey])) return false;
            if (!empty($this->roomAvailability[strtoupper($room)][$day][$slotKey])) return false;
        }
        return true;
    }

    private function reserveAssignment(int $teacherId, string $sectionKey, string $room, array $days, array $slot): void
    {
        $slotKey = $slot[0] . '|' . $slot[1];
        foreach ($days as $day) {
            $this->teacherAvailability[$teacherId][$day][$slotKey] = true;
            $this->sectionAvailability[$sectionKey][$day][$slotKey] = true;
            $this->roomAvailability[strtoupper($room)][$day][$slotKey] = true;
        }
    }

    private function reserveFor(Classes $class, string $day, string $start, string $end, ?string $room): void
    {
        $slotKey = $start . '|' . $end;
        if ($class->teacher_id) {
            $this->teacherAvailability[$class->teacher_id][$day][$slotKey] = true;
        }
        $this->sectionAvailability[$class->grade_level . '|' . $class->section][$day][$slotKey] = true;
        if (!empty($room)) {
            $this->roomAvailability[strtoupper($room)][$day][$slotKey] = true;
        }
    }

    private function releaseFor(Classes $class, string $day, string $start, string $end, ?string $room): void
    {
        $slotKey = $start . '|' . $end;
        if ($class->teacher_id) {
            unset($this->teacherAvailability[$class->teacher_id][$day][$slotKey]);
        }
        unset($this->sectionAvailability[$class->grade_level . '|' . $class->section][$day][$slotKey]);
        if (!empty($room)) {
            unset($this->roomAvailability[strtoupper($room)][$day][$slotKey]);
        }
    }

    /**
     * DB-verified free slot for a class: no overlapping schedule for the same class,
     * section, teacher or room among active classes of the same school year.
     */
    private function findFreeSlotInDb(Classes $class): ?array
    {
        foreach ($this->dayPatterns as $pattern) {
            foreach ($pattern as $day) {
                foreach ($this->timeSlots as $slot) {
                    if (!$this->dbSlotBlocked($class, $day, $slot)) {
                        return ['day' => $day, 'slot' => $slot];
                    }
                }
            }
        }
        return null;
    }

    private function dbSlotBlocked(Classes $class, string $day, array $slot): bool
    {
        $overlap = function ($q) use ($slot) {
            $q->where('start_time', '<', $slot[1])->where('end_time', '>', $slot[0]);
        };
        $activeYear = function ($q) use ($class) {
            $q->where('school_year', $class->school_year)->where('status', 'active');
        };

        if (Schedule::where('class_id', $class->id)->where('day_of_week', $day)->where($overlap)->exists()) {
            return true;
        }

        $sectionTeacherClash = Schedule::where('day_of_week', $day)->where($overlap)
            ->whereHas('schoolClass', function ($q) use ($class) {
                $q->where('school_year', $class->school_year)->where('status', 'active')
                    ->where(function ($qq) use ($class) {
                        $qq->where(function ($s) use ($class) {
                            $s->where('grade_level', $class->grade_level)->where('section', $class->section);
                        });
                        if ($class->teacher_id) {
                            $qq->orWhere('teacher_id', $class->teacher_id);
                        }
                    });
            })->exists();
        if ($sectionTeacherClash) {
            return true;
        }

        if (!empty($class->room)) {
            $roomClash = Schedule::where('day_of_week', $day)->where($overlap)
                ->where('room', $class->room)
                ->whereHas('schoolClass', $activeYear)->exists();
            if ($roomClash) {
                return true;
            }
        }

        return false;
    }

    private function warn(string $message): void
    {
        if ($this->command) $this->command->warn($message);
    }

    private function info(string $message): void
    {
        if ($this->command) $this->command->info($message);
    }
}
