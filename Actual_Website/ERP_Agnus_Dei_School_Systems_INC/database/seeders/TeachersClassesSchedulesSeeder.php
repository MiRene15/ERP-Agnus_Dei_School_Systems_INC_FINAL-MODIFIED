<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Classes;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;

class TeachersClassesSchedulesSeeder extends Seeder
{
    private array $teacherAvailability = [];

    public function run(): void
    {
        $schoolYear = '2026-2027';

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

        $seniorHighPlans = [
            ['grade_level' => 'Grade 11', 'section' => 'STEM-A', 'term' => '1st Term', 'subject_codes' => ['SHS-OC', 'SHS-RW', 'SHS-GMATH', 'SHS-ELS', 'SHS-PD', 'SHS-PEH', 'STEM-PCAL', 'STEM-BCAL']],
            ['grade_level' => 'Grade 12', 'section' => 'STEM-A', 'term' => '2nd Term', 'subject_codes' => ['SHS-EAPP', 'SHS-PR2', 'SHS-EMTECH', 'SHS-III', 'STEM-BIO1', 'STEM-CHEM1', 'STEM-PHY1']],
            ['grade_level' => 'Grade 11', 'section' => 'ABM-A', 'term' => '1st Term', 'subject_codes' => ['SHS-OC', 'SHS-RW', 'SHS-GMATH', 'SHS-UCSP', 'SHS-PEH', 'ABM-BMATH', 'ABM-OAM', 'ABM-FABM1']],
            ['grade_level' => 'Grade 12', 'section' => 'ABM-A', 'term' => '2nd Term', 'subject_codes' => ['SHS-EAPP', 'SHS-FPL', 'SHS-ENTREP', 'SHS-III', 'ABM-FABM2', 'SHS-PR2']],
            ['grade_level' => 'Grade 11', 'section' => 'HUMSS-A', 'term' => '1st Term', 'subject_codes' => ['SHS-OC', 'SHS-21CL', 'SHS-UCSP', 'SHS-PEH', 'HUMSS-DISS', 'HUMSS-DIASS', 'SHS-PR1']],
            ['grade_level' => 'Grade 12', 'section' => 'HUMSS-A', 'term' => '2nd Term', 'subject_codes' => ['SHS-EAPP', 'SHS-FPL', 'HUMSS-CREW', 'HUMSS-TNCT', 'SHS-III', 'SHS-PR2']],
            ['grade_level' => 'Grade 11', 'section' => 'GAS-A', 'term' => '1st Term', 'subject_codes' => ['SHS-OC', 'SHS-RW', 'SHS-MIL', 'SHS-UCSP', 'SHS-PEH', 'GAS-HGP']],
            ['grade_level' => 'Grade 12', 'section' => 'GAS-A', 'term' => '2nd Term', 'subject_codes' => ['SHS-EAPP', 'SHS-ENTREP', 'SHS-EMTECH', 'SHS-III', 'GAS-ORG']],
        ];

        $plans = [];

        foreach ($gradeSubjectCodes as $gradeLevel => $subjectCodes) {
            $department = $this->departmentForGrade($gradeLevel);
            $roomPrefix = $gradeLevel === 'Kinder' ? 'K' : ($department === 'Elementary' ? 'E' : 'J');

            foreach (($sectionsByGrade[$gradeLevel] ?? collect()) as $sectionIndex => $section) {
                $plans[] = [
                    'grade_level' => $gradeLevel,
                    'section' => $section->section_name,
                    'term' => null,
                    'room' => $roomPrefix . '-' . str_pad((string) ($sectionIndex + 101), 3, '0', STR_PAD_LEFT),
                    'department' => $department,
                    'subject_codes' => $subjectCodes,
                ];
            }
        }

        foreach ($seniorHighPlans as $index => $plan) {
            $plans[] = [
                'grade_level' => $plan['grade_level'],
                'section' => $plan['section'],
                'term' => $plan['term'],
                'room' => 'S-' . str_pad((string) ($index + 201), 3, '0', STR_PAD_LEFT),
                'department' => 'Senior High School',
                'subject_codes' => $plan['subject_codes'],
            ];
        }

        $timeSlots = [
            ['07:00:00', '08:00:00'],
            ['08:00:00', '09:00:00'],
            ['09:00:00', '10:00:00'],
            ['10:00:00', '11:00:00'],
            ['11:00:00', '12:00:00'],
            ['13:00:00', '14:00:00'],
            ['14:00:00', '15:00:00'],
            ['15:00:00', '16:00:00'],
        ];

        $dayPatterns = [
            ['Monday', 'Wednesday'],
            ['Tuesday', 'Thursday'],
            ['Monday', 'Thursday'],
            ['Tuesday', 'Friday'],
            ['Wednesday', 'Friday'],
        ];

        foreach ($plans as $planIndex => $plan) {
            $teacherPool = $teacherIdsByDept[$plan['department']] ?? [];
            if (empty($teacherPool)) continue;

            foreach ($plan['subject_codes'] as $subjectIndex => $subjectCode) {
                if (!isset($subjectMap[$subjectCode])) continue;

                $seedKey = abs(crc32($plan['grade_level'] . '|' . $plan['section'] . '|' . $subjectCode));
                $assignment = $this->resolveAssignment($teacherPool, $timeSlots, $dayPatterns, $seedKey);

                $class = Classes::updateOrCreate(
                    [
                        'subject_id' => $subjectMap[$subjectCode],
                        'section' => $plan['section'],
                        'grade_level' => $plan['grade_level'],
                        'school_year' => $schoolYear,
                        'term' => $plan['term'],
                    ],
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

    private function resolveAssignment(array $teacherPool, array $timeSlots, array $dayPatterns, int $seedKey): array
    {
        for ($slotOffset = 0; $slotOffset < count($timeSlots); $slotOffset++) {
            $slotIndex = ($seedKey + $slotOffset) % count($timeSlots);
            $patternIndex = ($seedKey + $slotOffset) % count($dayPatterns);
            $slot = $timeSlots[$slotIndex];
            $days = $dayPatterns[$patternIndex];

            for ($teacherOffset = 0; $teacherOffset < count($teacherPool); $teacherOffset++) {
                $teacherId = $teacherPool[($seedKey + $teacherOffset) % count($teacherPool)];
                if ($this->teacherIsAvailable($teacherId, $days, $slot)) {
                    $this->reserveTeacherSlot($teacherId, $days, $slot);
                    return ['teacher_id' => $teacherId, 'slot' => $slot, 'days' => $days];
                }
            }
        }

        $fallbackTeacherId = $teacherPool[$seedKey % count($teacherPool)];
        $fallbackSlot = $timeSlots[$seedKey % count($timeSlots)];
        $fallbackDays = $dayPatterns[$seedKey % count($dayPatterns)];
        $this->reserveTeacherSlot($fallbackTeacherId, $fallbackDays, $fallbackSlot);
        return ['teacher_id' => $fallbackTeacherId, 'slot' => $fallbackSlot, 'days' => $fallbackDays];
    }

    private function teacherIsAvailable(int $teacherId, array $days, array $slot): bool
    {
        $slotKey = implode('-', $slot);
        foreach ($days as $day) {
            if (!empty($this->teacherAvailability[$teacherId][$day][$slotKey])) return false;
        }
        return true;
    }

    private function reserveTeacherSlot(int $teacherId, array $days, array $slot): void
    {
        $slotKey = implode('-', $slot);
        foreach ($days as $day) {
            $this->teacherAvailability[$teacherId][$day][$slotKey] = true;
        }
    }
}
