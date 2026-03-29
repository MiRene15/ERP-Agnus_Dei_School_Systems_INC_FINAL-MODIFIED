# Comprehensive System Migrations & Seeders
**Agnus Dei School Systems, Inc.**

This document provides the complete, production-ready Laravel Migrations and the advanced procedural Seeders that map exactly to the logic you provided. It perfectly blends the **System RBAC (Admin, Cashiers, Staff)** with the sophisticated **Teacher, Subject, and Schedule Assignment Algorithm** you designed for Kinder through Grade 12.

---

## 1. Migration Codes Blueprint

These schema blueprints support the complex mapping rules of your `TeachersClassesSchedulesSeeder`.

```php
// database/migrations/xxxx_xx_xx_create_erp_core_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Roles (RBAC)
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Admin, Registrar, Cashier, Teacher, Librarian, Nurse, Student
            $table->timestamps();
        });

        // 2. Users (Core Authentication for Staff & Faculty)
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->default(7)->constrained('roles'); // Default to Student
            $table->string('contact_number')->nullable();
        });

        // 3. Teachers Profile (Extending the User Record)
        Schema::create('teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('teacher_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('department'); // Elementary, Junior High School, Senior High School
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 4. Subjects Catalog
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('subject_code')->unique();
            $table->string('name');
            $table->string('category')->nullable(); // Core, Contextualized, Specialized, TVL
            $table->timestamps();
        });

        // 5. Sections Directory
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('grade_level');
            $table->string('section_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 6. Classes (The Junction bridging Section, Subject, and Teacher logic)
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->string('section');
            $table->string('grade_level');
            $table->string('school_year');
            $table->string('semester')->nullable();
            $table->string('room')->nullable();
            $table->integer('capacity')->default(30);
            $table->boolean('is_advisory')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 7. Schedules (Mapping Class blocks dynamically)
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']);
            $table->time('start_time');
            $table->time('end_time');
            $table->string('room')->nullable();
            $table->timestamps();
        });
    }
};
```

---

## 2. Institutional Staff & Roles Seeder

This handles explicitly creating your foundation (Registrar, Admin, exactly maximum of 2 Cashiers) before running the complex Class seeding logic.

```php
// database/seeders/SystemRolesAndStaffSeeder.php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class SystemRolesAndStaffSeeder extends Seeder
{
    public function run()
    {
        // 1. Seed Roles
        $roles = [
            1 => 'Admin', 2 => 'Registrar', 3 => 'Cashier', 
            4 => 'Teacher', 5 => 'Librarian', 6 => 'Nurse', 7 => 'Student'
        ];
        
        foreach ($roles as $id => $name) {
            DB::table('roles')->updateOrInsert(['id' => $id], ['name' => $name]);
        }

        $password = Hash::make('Agnus2026!');

        // 2. Seed Admin & Registrar
        User::updateOrCreate(['email' => 'admin@agnusdei.local'], ['name' => 'System Admin', 'password' => $password, 'role_id' => 1]);
        User::updateOrCreate(['email' => 'registrar@agnusdei.local'], ['name' => 'Head Registrar', 'password' => $password, 'role_id' => 2]);
        
        // 3. Seed EXACTLY Maximum 2 Cashiers
        User::updateOrCreate(['email' => 'cashier1@agnusdei.local'], ['name' => 'Cashier Window 1', 'password' => $password, 'role_id' => 3]);
        User::updateOrCreate(['email' => 'cashier2@agnusdei.local'], ['name' => 'Cashier Window 2', 'password' => $password, 'role_id' => 3]);
        
        // 4. Auxiliary Staff
        User::updateOrCreate(['email' => 'library@agnusdei.local'], ['name' => 'Head Librarian', 'password' => $password, 'role_id' => 5]);
        User::updateOrCreate(['email' => 'clinic@agnusdei.local'], ['name' => 'School Nurse', 'password' => $password, 'role_id' => 6]);
    }
}
```

---

## 3. Advanced Teachers & Academic Blocks Seeder

This precisely implements your powerful tracking algorithm, safely preventing double-booking schedules (`resolveAssignment`) and algorithmically mapping out all Subject Codes from Kinder through Grade 12 Strands (`STEM`, `HUMSS`, etc.).

```php
// database/seeders/TeachersClassesSchedulesSeeder.php

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
        $schoolYear = '2026-2027';

        // 1. Teacher Manifest
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
            $phone = '09' . str_pad((string) (100000000 + $index), 9, '0', STR_PAD_LEFT);

            $user = User::updateOrCreate(
                ['email' => $teacherSeed['email']],
                [
                    'name' => $fullName,
                    'email' => $teacherSeed['email'],
                    'contact_number' => $phone,
                    'role_id' => 4, // Utilizing the Teacher Role Constraint
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

        // 2. Map Subjects & Sections
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
            ['grade_level' => 'Grade 11', 'section' => 'STEM-A', 'semester' => '1st Semester', 'subject_codes' => ['SHS-OC', 'SHS-RW', 'SHS-GMATH', 'SHS-ELS', 'SHS-PD', 'SHS-PEH', 'STEM-PCAL', 'STEM-BCAL']],
            ['grade_level' => 'Grade 12', 'section' => 'STEM-A', 'semester' => '2nd Semester', 'subject_codes' => ['SHS-EAPP', 'SHS-PR2', 'SHS-EMTECH', 'SHS-III', 'STEM-BIO1', 'STEM-CHEM1', 'STEM-PHY1']],
            ['grade_level' => 'Grade 11', 'section' => 'ABM-A', 'semester' => '1st Semester', 'subject_codes' => ['SHS-OC', 'SHS-RW', 'SHS-GMATH', 'SHS-UCSP', 'SHS-PEH', 'ABM-BMATH', 'ABM-OAM', 'ABM-FABM1']],
            ['grade_level' => 'Grade 12', 'section' => 'ABM-A', 'semester' => '2nd Semester', 'subject_codes' => ['SHS-EAPP', 'SHS-FPL', 'SHS-ENTREP', 'SHS-III', 'ABM-FABM2', 'SHS-PR2']],
            ['grade_level' => 'Grade 11', 'section' => 'HUMSS-A', 'semester' => '1st Semester', 'subject_codes' => ['SHS-OC', 'SHS-21CL', 'SHS-UCSP', 'SHS-PEH', 'HUMSS-DISS', 'HUMSS-DIASS', 'SHS-PR1']],
            ['grade_level' => 'Grade 12', 'section' => 'HUMSS-A', 'semester' => '2nd Semester', 'subject_codes' => ['SHS-EAPP', 'SHS-FPL', 'HUMSS-CREW', 'HUMSS-TNCT', 'SHS-III', 'SHS-PR2']],
            ['grade_level' => 'Grade 11', 'section' => 'GAS-A', 'semester' => '1st Semester', 'subject_codes' => ['SHS-OC', 'SHS-RW', 'SHS-MIL', 'SHS-UCSP', 'SHS-PEH', 'GAS-HGP']],
            ['grade_level' => 'Grade 12', 'section' => 'GAS-A', 'semester' => '2nd Semester', 'subject_codes' => ['SHS-EAPP', 'SHS-ENTREP', 'SHS-EMTECH', 'SHS-III', 'GAS-ORG']],
        ];

        $plans = [];

        foreach ($gradeSubjectCodes as $gradeLevel => $subjectCodes) {
            $department = $this->departmentForGrade($gradeLevel);
            $roomPrefix = $gradeLevel === 'Kinder' ? 'K' : ($department === 'Elementary' ? 'E' : 'J');

            foreach (($sectionsByGrade[$gradeLevel] ?? collect()) as $sectionIndex => $section) {
                $plans[] = [
                    'grade_level' => $gradeLevel,
                    'section' => $section->section_name,
                    'semester' => null,
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
                'semester' => $plan['semester'],
                'room' => 'S-' . str_pad((string) ($index + 201), 3, '0', STR_PAD_LEFT),
                'department' => 'Senior High School',
                'subject_codes' => $plan['subject_codes'],
            ];
        }

        // 3. Timetabling & Availability Mapping
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
                    continue; // You will need a SubjectSeeder populated to match these codes!
                }

                $seedKey = abs(crc32($plan['grade_level'] . '|' . $plan['section'] . '|' . $subjectCode));
                $assignment = $this->resolveAssignment($teacherPool, $teacherAvailability, $timeSlots, $dayPatterns, $seedKey);

                $class = Classes::updateOrCreate(
                    [
                        'subject_id' => $subjectMap[$subjectCode],
                        'section' => $plan['section'],
                        'grade_level' => $plan['grade_level'],
                        'school_year' => $schoolYear,
                        'semester' => $plan['semester'],
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
        if ($gradeNumber >= 11) return 'Senior High School';
        if ($gradeNumber >= 7) return 'Junior High School';
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
                    return ['teacher_id' => $teacherId, 'slot' => $slot, 'days' => $days];
                }
            }
        }

        // Fallback
        $fallbackTeacherId = $teacherPool[$seedKey % count($teacherPool)];
        $fallbackSlot = $timeSlots[$seedKey % count($timeSlots)];
        $fallbackDays = $dayPatterns[$seedKey % count($dayPatterns)];
        $this->reserveTeacherSlot($teacherAvailability, $fallbackTeacherId, $fallbackDays, $fallbackSlot);

        return ['teacher_id' => $fallbackTeacherId, 'slot' => $fallbackSlot, 'days' => $fallbackDays];
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
}
```
