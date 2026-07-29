<?php

namespace Database\Seeders;

use App\Models\Classes;
use App\Models\Schedule;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeachersClassesSchedulesSeeder extends Seeder
{
    public function run(): void
    {
        $schoolYear = active_school_year();

        $teacherSeeds = [
            ['first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria.santos@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Jose', 'last_name' => 'Reyes', 'email' => 'jose.reyes@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Ana', 'last_name' => 'Cruz', 'email' => 'ana.cruz@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Paolo', 'last_name' => 'Garcia', 'email' => 'paolo.garcia@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Rosa', 'last_name' => 'Villanueva', 'email' => 'rosa.villanueva@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Daniel', 'last_name' => 'Mercado', 'email' => 'daniel.mercado@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Liza', 'last_name' => 'Mendoza', 'email' => 'liza.mendoza@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Mark', 'last_name' => 'Torres', 'email' => 'mark.torres@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Rina', 'last_name' => 'Flores', 'email' => 'rina.flores@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Dennis', 'last_name' => 'Aquino', 'email' => 'dennis.aquino@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Grace', 'last_name' => 'Domingo', 'email' => 'grace.domingo@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Carlo', 'last_name' => 'Bautista', 'email' => 'carlo.bautista@agnusdei.local', 'department' => 'Junior High School'],
            ['first_name' => 'Carla', 'last_name' => 'Navarro', 'email' => 'carla.navarro@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Vincent', 'last_name' => 'Luna', 'email' => 'vincent.luna@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Sheila', 'last_name' => 'Ramos', 'email' => 'sheila.ramos@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Adrian', 'last_name' => 'Castro', 'email' => 'adrian.castro@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Elaine', 'last_name' => 'Sy', 'email' => 'elaine.sy@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Patrick', 'last_name' => 'Lopez', 'email' => 'patrick.lopez@agnusdei.local', 'department' => 'Senior High School'],
            ['first_name' => 'Teresa', 'last_name' => 'Natividad', 'email' => 'teresa.natividad@agnusdei.local', 'department' => 'Elementary'],
            ['first_name' => 'Nico', 'last_name' => 'Salazar', 'email' => 'nico.salazar@agnusdei.local', 'department' => 'Elementary'],
        ];

        $teacherIdsByDepartment = [
            'Elementary' => [],
            'Junior High School' => [],
            'Senior High School' => [],
        ];

        foreach ($teacherSeeds as $index => $teacherSeed) {
            $fullName = $teacherSeed['first_name'] . ' ' . $teacherSeed['last_name'];
            $phone = '+63' . str_pad((string) (910000000 + $index), 10, '0', STR_PAD_LEFT);

            $user = User::updateOrCreate(
                ['email' => $teacherSeed['email']],
                [
                    'name' => $fullName,
                    'email' => $teacherSeed['email'],
                    'contact_number' => $phone,
                    'role_id' => 4, // Utilizing the Teacher Role
                    'password' => Hash::make('Agnus2026!'),
                ]
            );

            $teacher = Teacher::updateOrCreate(
                ['email' => $teacherSeed['email']],
                [
                    'user_id' => $user->id,
                    'teacher_number' => Teacher::where('email', $teacherSeed['email'])->value('teacher_number') ?: $this->generateTeacherNumber(),
                    'first_name' => $teacherSeed['first_name'],
                    'last_name' => $teacherSeed['last_name'],
                    'email' => $teacherSeed['email'],
                    'phone' => $phone,
                    'department' => $teacherSeed['department'],
                    'status' => 'active',
                ]
            );

            $teacherIdsByDepartment[$teacherSeed['department']][] = $user->id;
        }

        // Fake Subjects (Ensure the DB has these mapped properly so the algorithm doesn't skip)
        $this->ensureSubjectsAndSectionsExist();

        // Assign advisers to sections based on department
        $this->assignSectionAdvisers($teacherIdsByDepartment);

        $subjectMap = Subject::pluck('id', 'subject_code')->toArray();
        $sectionsByGrade = Section::where('is_active', true)
            ->orderBy('grade_level')
            ->orderBy('section_name')
            ->get()
            ->groupBy('grade_level');

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

        $teacherAvailability = [];

        foreach ($plans as $planIndex => $plan) {
            $teacherPool = $teacherIdsByDepartment[$plan['department']] ?? [];
            if (empty($teacherPool)) {
                continue;
            }

            foreach ($plan['subject_codes'] as $subjectIndex => $subjectCode) {
                if (!isset($subjectMap[$subjectCode])) {
                    continue;
                }

                $seedKey = abs(crc32($plan['grade_level'] . '|' . $plan['section'] . '|' . $subjectCode));
                $assignment = $this->resolveAssignment($teacherPool, $teacherAvailability, $timeSlots, $dayPatterns, $seedKey);

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
                        [
                            'class_id' => $class->id,
                            'day_of_week' => $day,
                        ],
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

    private function generateTeacherNumber(): string
    {
        do {
            $teacherNumber = 'TCH-' . str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        } while (Teacher::where('teacher_number', $teacherNumber)->exists());

        return $teacherNumber;
    }

    private function departmentForGrade(string $gradeLevel): string
    {
        if ($gradeLevel === 'Kinder') {
            return 'Elementary';
        }

        $gradeNumber = (int) filter_var($gradeLevel, FILTER_SANITIZE_NUMBER_INT);

        if ($gradeNumber >= 11) {
            return 'Senior High School';
        }

        if ($gradeNumber >= 7) {
            return 'Junior High School';
        }

        return 'Elementary';
    }

    private function resolveAssignment(array $teacherPool, array &$teacherAvailability, array $timeSlots, array $dayPatterns, int $seedKey): array
    {
        for ($slotOffset = 0; $slotOffset < count($timeSlots); $slotOffset++) {
            $slotIndex = ($seedKey + $slotOffset) % count($timeSlots);
            $patternIndex = ($seedKey + $slotOffset) % count($dayPatterns);
            $slot = $timeSlots[$slotIndex];
            $days = $dayPatterns[$patternIndex];

            for ($teacherOffset = 0; $teacherOffset < count($teacherPool); $teacherOffset++) {
                $teacherId = $teacherPool[($seedKey + $teacherOffset) % count($teacherPool)];

                if ($this->teacherIsAvailable($teacherAvailability, $teacherId, $days, $slot)) {
                    $this->reserveTeacherSlot($teacherAvailability, $teacherId, $days, $slot);

                    return [
                        'teacher_id' => $teacherId,
                        'slot' => $slot,
                        'days' => $days,
                    ];
                }
            }
        }

        $fallbackTeacherId = $teacherPool[$seedKey % count($teacherPool)];
        $fallbackSlot = $timeSlots[$seedKey % count($timeSlots)];
        $fallbackDays = $dayPatterns[$seedKey % count($dayPatterns)];
        $this->reserveTeacherSlot($teacherAvailability, $fallbackTeacherId, $fallbackDays, $fallbackSlot);

        return [
            'teacher_id' => $fallbackTeacherId,
            'slot' => $fallbackSlot,
            'days' => $fallbackDays,
        ];
    }

    private function teacherIsAvailable(array $teacherAvailability, int $teacherId, array $days, array $slot): bool
    {
        $slotKey = implode('-', $slot);

        foreach ($days as $day) {
            if (!empty($teacherAvailability[$teacherId][$day][$slotKey])) {
                return false;
            }
        }

        return true;
    }

    private function reserveTeacherSlot(array &$teacherAvailability, int $teacherId, array $days, array $slot): void
    {
        $slotKey = implode('-', $slot);

        foreach ($days as $day) {
            $teacherAvailability[$teacherId][$day][$slotKey] = true;
        }
    }

    /**
     * Bootstrap the Subjects and Sections so the array loops actually execute Class assignments
     */
    private function ensureSubjectsAndSectionsExist()
    {
        $grades = ['Kinder', 'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10'];
        foreach($grades as $g) {
            Section::firstOrCreate(['grade_level' => $g, 'section_name' => 'A']);
            Section::firstOrCreate(['grade_level' => $g, 'section_name' => 'B']);
        }
        
        $strands = ['STEM-A', 'ABM-A', 'HUMSS-A', 'GAS-A'];
        foreach($strands as $s) {
            Section::firstOrCreate(['grade_level' => 'Grade 11', 'section_name' => $s]);
            Section::firstOrCreate(['grade_level' => 'Grade 12', 'section_name' => $s]);
        }
        
        // Mock all required subject codes because the previous logic plucked it directly
        $subjectData = [
            // Kinder
            'K-ENG'   => ['name' => 'English',              'grade_level' => 'Kinder',      'category' => 'Core'],
            'K-MAT'   => ['name' => 'Mathematics',          'grade_level' => 'Kinder',      'category' => 'Core'],
            'K-SCI'   => ['name' => 'Science',              'grade_level' => 'Kinder',      'category' => 'Core'],
            'K-READ'  => ['name' => 'Reading',              'grade_level' => 'Kinder',      'category' => 'Core'],
            'K-MAPEH' => ['name' => 'MAPEH',                'grade_level' => 'Kinder',      'category' => 'Core'],
            'K-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Kinder', 'category' => 'Core'],
            // Grade 1
            'G1-ENG'   => ['name' => 'English',             'grade_level' => 'Grade 1',     'category' => 'Core'],
            'G1-FIL'   => ['name' => 'Filipino',            'grade_level' => 'Grade 1',     'category' => 'Core'],
            'G1-MAT'   => ['name' => 'Mathematics',         'grade_level' => 'Grade 1',     'category' => 'Core'],
            'G1-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Grade 1', 'category' => 'Core'],
            'G1-MAPEH' => ['name' => 'MAPEH',               'grade_level' => 'Grade 1',     'category' => 'Core'],
            'G1-AP'    => ['name' => 'Araling Panlipunan',  'grade_level' => 'Grade 1',     'category' => 'Contextualized'],
            // Grade 2
            'G2-ENG'   => ['name' => 'English',             'grade_level' => 'Grade 2',     'category' => 'Core'],
            'G2-FIL'   => ['name' => 'Filipino',            'grade_level' => 'Grade 2',     'category' => 'Core'],
            'G2-MAT'   => ['name' => 'Mathematics',         'grade_level' => 'Grade 2',     'category' => 'Core'],
            'G2-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Grade 2', 'category' => 'Core'],
            'G2-MAPEH' => ['name' => 'MAPEH',               'grade_level' => 'Grade 2',     'category' => 'Core'],
            'G2-AP'    => ['name' => 'Araling Panlipunan',  'grade_level' => 'Grade 2',     'category' => 'Contextualized'],
            // Grade 3
            'G3-ENG'   => ['name' => 'English',             'grade_level' => 'Grade 3',     'category' => 'Core'],
            'G3-FIL'   => ['name' => 'Filipino',            'grade_level' => 'Grade 3',     'category' => 'Core'],
            'G3-MAT'   => ['name' => 'Mathematics',         'grade_level' => 'Grade 3',     'category' => 'Core'],
            'G3-SCI'   => ['name' => 'Science',             'grade_level' => 'Grade 3',     'category' => 'Core'],
            'G3-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Grade 3', 'category' => 'Core'],
            'G3-MAPEH' => ['name' => 'MAPEH',               'grade_level' => 'Grade 3',     'category' => 'Core'],
            'G3-AP'    => ['name' => 'Araling Panlipunan',  'grade_level' => 'Grade 3',     'category' => 'Contextualized'],
            // Grade 4
            'G4-ENG'   => ['name' => 'English',             'grade_level' => 'Grade 4',     'category' => 'Core'],
            'G4-FIL'   => ['name' => 'Filipino',            'grade_level' => 'Grade 4',     'category' => 'Core'],
            'G4-MAT'   => ['name' => 'Mathematics',         'grade_level' => 'Grade 4',     'category' => 'Core'],
            'G4-SCI'   => ['name' => 'Science',             'grade_level' => 'Grade 4',     'category' => 'Core'],
            'G4-AP'    => ['name' => 'Araling Panlipunan',  'grade_level' => 'Grade 4',     'category' => 'Contextualized'],
            'G4-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Grade 4', 'category' => 'Core'],
            'G4-MAPEH' => ['name' => 'MAPEH',               'grade_level' => 'Grade 4',     'category' => 'Core'],
            'G4-EPP'   => ['name' => 'Edukasyong Pantahanan at Pangkabuhayan', 'grade_level' => 'Grade 4', 'category' => 'Contextualized'],
            // Grade 5
            'G5-ENG'   => ['name' => 'English',             'grade_level' => 'Grade 5',     'category' => 'Core'],
            'G5-FIL'   => ['name' => 'Filipino',            'grade_level' => 'Grade 5',     'category' => 'Core'],
            'G5-MAT'   => ['name' => 'Mathematics',         'grade_level' => 'Grade 5',     'category' => 'Core'],
            'G5-SCI'   => ['name' => 'Science',             'grade_level' => 'Grade 5',     'category' => 'Core'],
            'G5-AP'    => ['name' => 'Araling Panlipunan',  'grade_level' => 'Grade 5',     'category' => 'Contextualized'],
            'G5-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Grade 5', 'category' => 'Core'],
            'G5-MAPEH' => ['name' => 'MAPEH',               'grade_level' => 'Grade 5',     'category' => 'Core'],
            'G5-EPP'   => ['name' => 'Edukasyong Pantahanan at Pangkabuhayan', 'grade_level' => 'Grade 5', 'category' => 'Contextualized'],
            // Grade 6
            'G6-ENG'   => ['name' => 'English',             'grade_level' => 'Grade 6',     'category' => 'Core'],
            'G6-FIL'   => ['name' => 'Filipino',            'grade_level' => 'Grade 6',     'category' => 'Core'],
            'G6-MAT'   => ['name' => 'Mathematics',         'grade_level' => 'Grade 6',     'category' => 'Core'],
            'G6-SCI'   => ['name' => 'Science',             'grade_level' => 'Grade 6',     'category' => 'Core'],
            'G6-AP'    => ['name' => 'Araling Panlipunan',  'grade_level' => 'Grade 6',     'category' => 'Contextualized'],
            'G6-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Grade 6', 'category' => 'Core'],
            'G6-MAPEH' => ['name' => 'MAPEH',               'grade_level' => 'Grade 6',     'category' => 'Core'],
            'G6-EPP'   => ['name' => 'Edukasyong Pantahanan at Pangkabuhayan', 'grade_level' => 'Grade 6', 'category' => 'Contextualized'],
            // Grade 7
            'G7-ENG'   => ['name' => 'English',             'grade_level' => 'Grade 7',     'category' => 'Core'],
            'G7-FIL'   => ['name' => 'Filipino',            'grade_level' => 'Grade 7',     'category' => 'Core'],
            'G7-MAT'   => ['name' => 'Mathematics',         'grade_level' => 'Grade 7',     'category' => 'Core'],
            'G7-SCI'   => ['name' => 'Science',             'grade_level' => 'Grade 7',     'category' => 'Core'],
            'G7-AP'    => ['name' => 'Araling Panlipunan',  'grade_level' => 'Grade 7',     'category' => 'Contextualized'],
            'G7-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Grade 7', 'category' => 'Core'],
            'G7-MAPEH' => ['name' => 'MAPEH',               'grade_level' => 'Grade 7',     'category' => 'Core'],
            'G7-TLE'   => ['name' => 'Technology and Livelihood Education', 'grade_level' => 'Grade 7', 'category' => 'Contextualized'],
            // Grade 8
            'G8-ENG'   => ['name' => 'English',             'grade_level' => 'Grade 8',     'category' => 'Core'],
            'G8-FIL'   => ['name' => 'Filipino',            'grade_level' => 'Grade 8',     'category' => 'Core'],
            'G8-MAT'   => ['name' => 'Mathematics',         'grade_level' => 'Grade 8',     'category' => 'Core'],
            'G8-SCI'   => ['name' => 'Science',             'grade_level' => 'Grade 8',     'category' => 'Core'],
            'G8-AP'    => ['name' => 'Araling Panlipunan',  'grade_level' => 'Grade 8',     'category' => 'Contextualized'],
            'G8-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Grade 8', 'category' => 'Core'],
            'G8-MAPEH' => ['name' => 'MAPEH',               'grade_level' => 'Grade 8',     'category' => 'Core'],
            'G8-TLE'   => ['name' => 'Technology and Livelihood Education', 'grade_level' => 'Grade 8', 'category' => 'Contextualized'],
            // Grade 9
            'G9-ENG'   => ['name' => 'English',             'grade_level' => 'Grade 9',     'category' => 'Core'],
            'G9-FIL'   => ['name' => 'Filipino',            'grade_level' => 'Grade 9',     'category' => 'Core'],
            'G9-MAT'   => ['name' => 'Mathematics',         'grade_level' => 'Grade 9',     'category' => 'Core'],
            'G9-SCI'   => ['name' => 'Science',             'grade_level' => 'Grade 9',     'category' => 'Core'],
            'G9-AP'    => ['name' => 'Araling Panlipunan',  'grade_level' => 'Grade 9',     'category' => 'Contextualized'],
            'G9-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Grade 9', 'category' => 'Core'],
            'G9-MAPEH' => ['name' => 'MAPEH',               'grade_level' => 'Grade 9',     'category' => 'Core'],
            'G9-TLE'   => ['name' => 'Technology and Livelihood Education', 'grade_level' => 'Grade 9', 'category' => 'Contextualized'],
            // Grade 10
            'G10-ENG'   => ['name' => 'English',            'grade_level' => 'Grade 10',    'category' => 'Core'],
            'G10-FIL'   => ['name' => 'Filipino',           'grade_level' => 'Grade 10',    'category' => 'Core'],
            'G10-MAT'   => ['name' => 'Mathematics',        'grade_level' => 'Grade 10',    'category' => 'Core'],
            'G10-SCI'   => ['name' => 'Science',            'grade_level' => 'Grade 10',    'category' => 'Core'],
            'G10-AP'    => ['name' => 'Araling Panlipunan', 'grade_level' => 'Grade 10',    'category' => 'Contextualized'],
            'G10-ESP'   => ['name' => 'Edukasyon sa Pagpapakatao', 'grade_level' => 'Grade 10', 'category' => 'Core'],
            'G10-MAPEH' => ['name' => 'MAPEH',              'grade_level' => 'Grade 10',    'category' => 'Core'],
            'G10-TLE'   => ['name' => 'Technology and Livelihood Education', 'grade_level' => 'Grade 10', 'category' => 'Contextualized'],
            // SHS Common
            'SHS-OC'     => ['name' => 'Oral Communication',      'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-RW'     => ['name' => 'Reading and Writing',     'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-GMATH'  => ['name' => 'General Mathematics',     'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-ELS'    => ['name' => 'Earth and Life Science',  'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-PD'     => ['name' => 'Physical Education and Health', 'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-PEH'    => ['name' => 'Physical Education and Health', 'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-EAPP'   => ['name' => 'Ethics and Moral Principles in the Workplace', 'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-PR2'    => ['name' => 'Personal Development 2',  'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-EMTECH' => ['name' => 'Empowerment Technologies', 'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-III'    => ['name' => 'Inquiries, Investigations, and Immersion', 'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-UCSP'   => ['name' => 'Understanding Culture, Society, and Politics', 'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-FPL'    => ['name' => 'Financial Literacy, Business Plan', 'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-ENTREP' => ['name' => 'Entrepreneurship',       'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-21CL'   => ['name' => '21st Century Literature', 'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-PR1'    => ['name' => 'Personal Development 1', 'grade_level' => 'SHS', 'category' => 'Core'],
            'SHS-MIL'    => ['name' => 'Media and Information Literacy', 'grade_level' => 'SHS', 'category' => 'Core'],
            // SHS Strand-Specific
            'STEM-PCAL'  => ['name' => 'Pre-Calculus',           'grade_level' => 'SHS', 'category' => 'Specialized'],
            'STEM-BCAL'  => ['name' => 'Basic Calculus',         'grade_level' => 'SHS', 'category' => 'Specialized'],
            'STEM-BIO1'  => ['name' => 'Biology 1',              'grade_level' => 'SHS', 'category' => 'Specialized'],
            'STEM-CHEM1' => ['name' => 'Chemistry 1',            'grade_level' => 'SHS', 'category' => 'Specialized'],
            'STEM-PHY1'  => ['name' => 'Physics 1',              'grade_level' => 'SHS', 'category' => 'Specialized'],
            'ABM-BMATH'  => ['name' => 'Business Mathematics',   'grade_level' => 'SHS', 'category' => 'Specialized'],
            'ABM-OAM'    => ['name' => 'Organization and Management', 'grade_level' => 'SHS', 'category' => 'Specialized'],
            'ABM-FABM1'  => ['name' => 'Fundamentals of Accountancy 1', 'grade_level' => 'SHS', 'category' => 'Specialized'],
            'ABM-FABM2'  => ['name' => 'Fundamentals of Accountancy 2', 'grade_level' => 'SHS', 'category' => 'Specialized'],
            'HUMSS-DISS'  => ['name' => 'Disciplines and Ideas in Social Sciences', 'grade_level' => 'SHS', 'category' => 'Specialized'],
            'HUMSS-DIASS' => ['name' => 'Disciplines and Ideas in Applied Social Sciences', 'grade_level' => 'SHS', 'category' => 'Specialized'],
            'HUMSS-CREW'  => ['name' => 'Creative Writing',      'grade_level' => 'SHS', 'category' => 'Specialized'],
            'HUMSS-TNCT'  => ['name' => 'Trends, Networks, and Critical Thinking', 'grade_level' => 'SHS', 'category' => 'Specialized'],
            'GAS-HGP'     => ['name' => 'Human Geography',       'grade_level' => 'SHS', 'category' => 'Specialized'],
            'GAS-ORG'     => ['name' => 'Organization and Management', 'grade_level' => 'SHS', 'category' => 'Specialized'],
        ];

        foreach ($subjectData as $code => $data) {
            Subject::updateOrCreate(
                ['subject_code' => $code],
                ['name' => $data['name'], 'grade_level' => $data['grade_level'], 'category' => $data['category']]
            );
        }
    }

    private function assignSectionAdvisers(array $teacherIdsByDepartment): void
    {
        $sections = Section::where('is_active', true)->orderBy('grade_level')->orderBy('section_name')->get();

        $deptSections = ['Elementary' => [], 'Junior High School' => [], 'Senior High School' => []];

        foreach ($sections as $section) {
            $dept = $this->departmentForGrade($section->grade_level);
            $deptSections[$dept][] = $section;
        }

        foreach ($deptSections as $department => $deptSectionList) {
            $teacherIds = $teacherIdsByDepartment[$department] ?? [];
            if (empty($teacherIds) || empty($deptSectionList)) {
                continue;
            }

            foreach ($deptSectionList as $index => $section) {
                $teacherId = $teacherIds[$index % count($teacherIds)];

                $section->adviser_id = $teacherId;
                $section->save();
            }
        }
    }
}
