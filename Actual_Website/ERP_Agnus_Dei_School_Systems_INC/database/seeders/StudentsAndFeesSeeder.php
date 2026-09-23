<?php

namespace Database\Seeders;

use App\Models\Admission;
use App\Models\Classes;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentLedger;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentsAndFeesSeeder extends Seeder
{
    protected function resolveEmail(string $firstName, string $lastName): string
    {
        $base = strtolower(str_replace(' ', '', $firstName) . '.' . str_replace(' ', '', $lastName));
        $baseEmail = $base . '@agnusdei.edu.ph';

        $existing = User::where('email', $baseEmail)->first();
        if ($existing) return $baseEmail;

        $counter = 1;
        $email = $baseEmail;
        while (User::where('email', $email)->exists()) {
            $email = $base . $counter . '@agnusdei.edu.ph';
            $counter++;
        }
        return $email;
    }

    protected static array $studentSeeds = [
        // Kinder
        ['first_name' => 'Juan', 'last_name' => 'dela Cruz',   'middle_name' => 'Bautista',  'grade' => 'Kinder',  'strand' => null],
        ['first_name' => 'Mia', 'last_name' => 'Aquino',       'middle_name' => 'Reyes',     'grade' => 'Kinder',  'strand' => null],
        // Grade 1
        ['first_name' => 'Maria', 'last_name' => 'Santos',      'middle_name' => 'Reyes',     'grade' => 'Grade 1', 'strand' => null],
        ['first_name' => 'Ethan', 'last_name' => 'Rivera',      'middle_name' => 'Garcia',    'grade' => 'Grade 1', 'strand' => null],
        ['first_name' => 'Chloe', 'last_name' => 'Torres',      'middle_name' => 'Navarro',   'grade' => 'Grade 1', 'strand' => null],
        // Grade 2
        ['first_name' => 'Jose', 'last_name' => 'Reyes',        'middle_name' => 'Aquino',    'grade' => 'Grade 2', 'strand' => null],
        ['first_name' => 'Zoe', 'last_name' => 'Fernando',      'middle_name' => 'Santiago',  'grade' => 'Grade 2', 'strand' => null],
        // Grade 3
        ['first_name' => 'Ana', 'last_name' => 'Gonzales',      'middle_name' => 'Villanueva','grade' => 'Grade 3', 'strand' => null],
        ['first_name' => 'Liam', 'last_name' => 'Pascual',      'middle_name' => 'Cruz',      'grade' => 'Grade 3', 'strand' => null],
        ['first_name' => 'Ava', 'last_name' => 'Mercado',       'middle_name' => 'Ramos',     'grade' => 'Grade 3', 'strand' => null],
        // Grade 4
        ['first_name' => 'Pedro', 'last_name' => 'Fernandez',    'middle_name' => 'Cruz',      'grade' => 'Grade 4', 'strand' => null],
        ['first_name' => 'Noah', 'last_name' => 'Santiago',      'middle_name' => 'Dela Peña', 'grade' => 'Grade 4', 'strand' => null],
        // Grade 5
        ['first_name' => 'Luisa', 'last_name' => 'Villanueva',   'middle_name' => 'Garcia',    'grade' => 'Grade 5', 'strand' => null],
        ['first_name' => 'Ella', 'last_name' => 'Navarro',       'middle_name' => 'Bautista',  'grade' => 'Grade 5', 'strand' => null],
        ['first_name' => 'James', 'last_name' => 'Ramos',        'middle_name' => 'Torres',    'grade' => 'Grade 5', 'strand' => null],
        // Grade 6
        ['first_name' => 'Carlos', 'last_name' => 'Mendoza',     'middle_name' => 'Ramos',     'grade' => 'Grade 6', 'strand' => null],
        ['first_name' => 'Luna', 'last_name' => 'Cruz',          'middle_name' => 'Mercado',   'grade' => 'Grade 6', 'strand' => null],
        // Grade 7
        ['first_name' => 'Sofia', 'last_name' => 'Garcia',       'middle_name' => 'Torres',    'grade' => 'Grade 7', 'strand' => null],
        ['first_name' => 'Lucas', 'last_name' => 'Dela Peña',    'middle_name' => 'Rivera',    'grade' => 'Grade 7', 'strand' => null],
        ['first_name' => 'Chloe', 'last_name' => 'Bautista',     'middle_name' => 'Santiago',  'grade' => 'Grade 7', 'strand' => null],
        // Grade 8
        ['first_name' => 'Miguel', 'last_name' => 'Lopez',       'middle_name' => 'Dela Peña', 'grade' => 'Grade 8', 'strand' => null],
        ['first_name' => 'Harper', 'last_name' => 'Garcia',      'middle_name' => 'Pascual',   'grade' => 'Grade 8', 'strand' => null],
        // Grade 9
        ['first_name' => 'Isabella', 'last_name' => 'Martinez',  'middle_name' => 'Navarro',   'grade' => 'Grade 9', 'strand' => null],
        ['first_name' => 'Elijah', 'last_name' => 'Rivera',      'middle_name' => 'Aquino',    'grade' => 'Grade 9', 'strand' => null],
        ['first_name' => 'Amelia', 'last_name' => 'Santos',      'middle_name' => 'Fernando',  'grade' => 'Grade 9', 'strand' => null],
        // Grade 10
        ['first_name' => 'Rafael', 'last_name' => 'Torres',      'middle_name' => 'Santiago',  'grade' => 'Grade 10','strand' => null],
        ['first_name' => 'Evelyn', 'last_name' => 'Pascual',     'middle_name' => 'Reyes',     'grade' => 'Grade 10','strand' => null],
        ['first_name' => 'Daniel', 'last_name' => 'Mercado',     'middle_name' => 'Cruz',      'grade' => 'Grade 10','strand' => null],
        // Grade 11
        ['first_name' => 'Angela', 'last_name' => 'Ramirez',     'middle_name' => 'Mercado',   'grade' => 'Grade 11','strand' => 'STEM', 'scholarship' => true],
        ['first_name' => 'Mateo', 'last_name' => 'Villanueva',   'middle_name' => 'Ramos',     'grade' => 'Grade 11','strand' => 'STEM', 'scholarship' => false],
        ['first_name' => 'Sofia', 'last_name' => 'Reyes',        'middle_name' => 'Garcia',    'grade' => 'Grade 11','strand' => 'ABM', 'scholarship' => false],
        ['first_name' => 'Sebastian', 'last_name' => 'Cruz',     'middle_name' => 'Torres',    'grade' => 'Grade 11','strand' => 'HUMSS', 'scholarship' => true],
        ['first_name' => 'Camille', 'last_name' => 'Navarro',    'middle_name' => 'Santiago',  'grade' => 'Grade 11','strand' => 'ABM', 'scholarship' => false],
        // Grade 12
        ['first_name' => 'Dante', 'last_name' => 'Cruz',         'middle_name' => 'Pascual',   'grade' => 'Grade 12','strand' => 'ABM', 'scholarship' => false],
        ['first_name' => 'Gabriela', 'last_name' => 'Santos',    'middle_name' => 'Rivera',    'grade' => 'Grade 12','strand' => 'STEM', 'scholarship' => true],
        ['first_name' => 'Andrei', 'last_name' => 'Torres',      'middle_name' => 'Bautista',  'grade' => 'Grade 12','strand' => 'HUMSS', 'scholarship' => false],
        ['first_name' => 'Patricia', 'last_name' => 'Garcia',    'middle_name' => 'Navarro',   'grade' => 'Grade 12','strand' => 'GAS', 'scholarship' => false],
        ['first_name' => 'Mikhail', 'last_name' => 'Rivera',     'middle_name' => 'Aquino',    'grade' => 'Grade 12','strand' => 'STEM', 'scholarship' => false],
        // Withdrawn students (status = 'withdrawn')
        ['first_name' => 'Bianca', 'last_name' => 'Lopez',       'middle_name' => 'Fernando',  'grade' => 'Grade 4', 'strand' => null, 'status' => 'withdrawn'],
        ['first_name' => 'Rico', 'last_name' => 'dela Cruz',     'middle_name' => 'Mercado',   'grade' => 'Grade 8', 'strand' => null, 'status' => 'withdrawn'],
        // Graduated students (status = 'graduated')
        ['first_name' => 'Jasmine', 'last_name' => 'Ramos',      'middle_name' => 'Santiago',  'grade' => 'Grade 12','strand' => 'STEM', 'status' => 'graduated'],
        ['first_name' => 'Victor', 'last_name' => 'Gonzales',    'middle_name' => 'Pascual',   'grade' => 'Grade 12','strand' => 'ABM', 'status' => 'graduated'],
        // --- Additional 33 students to reach 75 total (diverse, realistic Filipino names) ---
        // Kinder (+3)
        ['first_name' => 'Amara', 'last_name' => 'Reyes',        'middle_name' => 'Santos',    'grade' => 'Kinder',  'strand' => null],
        ['first_name' => 'Ethan', 'last_name' => 'Dela Rosa',    'middle_name' => 'Cruz',      'grade' => 'Kinder',  'strand' => null],
        ['first_name' => 'Sofia', 'last_name' => 'Alonzo',       'middle_name' => 'Garcia',    'grade' => 'Kinder',  'strand' => null],
        // Grade 1 (+2)
        ['first_name' => 'Bianca', 'last_name' => 'Navarro',     'middle_name' => 'Lim',       'grade' => 'Grade 1', 'strand' => null],
        ['first_name' => 'Rafael', 'last_name' => 'Domingo',     'middle_name' => 'Reyes',     'grade' => 'Grade 1', 'strand' => null],
        // Grade 2 (+3)
        ['first_name' => 'Marco', 'last_name' => 'Santos',       'middle_name' => 'Villanueva','grade' => 'Grade 2', 'strand' => null],
        ['first_name' => 'Isabella', 'last_name' => 'Villanueva','middle_name' => 'Aquino',   'grade' => 'Grade 2', 'strand' => null],
        ['first_name' => 'Nathan', 'last_name' => 'Cruz',        'middle_name' => 'Torres',    'grade' => 'Grade 2', 'strand' => null],
        // Grade 3 (+2)
        ['first_name' => 'Miguel', 'last_name' => 'De Guzman',   'middle_name' => 'Ramos',     'grade' => 'Grade 3', 'strand' => null],
        ['first_name' => 'Hannah', 'last_name' => 'Flores',      'middle_name' => 'Mendoza',   'grade' => 'Grade 3', 'strand' => null],
        // Grade 4 (+2) — one withdrawn
        ['first_name' => 'Sabrina', 'last_name' => 'Ortega',     'middle_name' => 'Bautista',  'grade' => 'Grade 4', 'strand' => null],
        ['first_name' => 'Joaquin', 'last_name' => 'Ramos',      'middle_name' => 'Mercado',   'grade' => 'Grade 4', 'strand' => null, 'status' => 'withdrawn'],
        // Grade 5 (+3)
        ['first_name' => 'Andre', 'last_name' => 'Bautista',     'middle_name' => 'Soriano',   'grade' => 'Grade 5', 'strand' => null],
        ['first_name' => 'Juliana', 'last_name' => 'Soriano',    'middle_name' => 'Delgado',   'grade' => 'Grade 5', 'strand' => null],
        ['first_name' => 'Francis', 'last_name' => 'Delgado',    'middle_name' => 'Aquino',    'grade' => 'Grade 5', 'strand' => null],
        // Grade 6 (+3) — one transferred
        ['first_name' => 'Alyssa', 'last_name' => 'Mercado',     'middle_name' => 'Santiago',  'grade' => 'Grade 6', 'strand' => null],
        ['first_name' => 'Tristan', 'last_name' => 'Rivera',     'middle_name' => 'Cruz',      'grade' => 'Grade 6', 'strand' => null],
        ['first_name' => 'Diego', 'last_name' => 'Hernandez',    'middle_name' => 'Reyes',     'grade' => 'Grade 6', 'strand' => null, 'status' => 'transferred'],
        // Grade 7 (+3)
        ['first_name' => 'Janelle', 'last_name' => 'Cruz',       'middle_name' => 'Villanueva','grade' => 'Grade 7', 'strand' => null],
        ['first_name' => 'Jerome', 'last_name' => 'Aquino',      'middle_name' => 'Dela Peña', 'grade' => 'Grade 7', 'strand' => null],
        ['first_name' => 'Kiera', 'last_name' => 'Santos',       'middle_name' => 'Fernando',  'grade' => 'Grade 7', 'strand' => null],
        // Grade 8 (+2)
        ['first_name' => 'Paolo', 'last_name' => 'Santos',       'middle_name' => 'Garcia',    'grade' => 'Grade 8', 'strand' => null],
        ['first_name' => 'Nina', 'last_name' => 'Esquivel',      'middle_name' => 'Ramos',     'grade' => 'Grade 8', 'strand' => null],
        // Grade 9 (+2)
        ['first_name' => 'Nathaniel', 'last_name' => 'De Leon',  'middle_name' => 'Cruz',      'grade' => 'Grade 9', 'strand' => null],
        ['first_name' => 'Elise', 'last_name' => 'Tan',          'middle_name' => 'Lim',       'grade' => 'Grade 9', 'strand' => null],
        // Grade 10 (+2)
        ['first_name' => 'Julian', 'last_name' => 'Perez',       'middle_name' => 'Santiago',  'grade' => 'Grade 10','strand' => null],
        ['first_name' => 'Andrea', 'last_name' => 'Lim',         'middle_name' => 'Villanueva','grade' => 'Grade 10','strand' => null],
        // Grade 11 (+3) — mix strands
        ['first_name' => 'Lorenzo', 'last_name' => 'Valdez',     'middle_name' => 'Santos',    'grade' => 'Grade 11','strand' => 'STEM', 'scholarship' => false],
        ['first_name' => 'Mariel', 'last_name' => 'De Vera',     'middle_name' => 'Cruz',      'grade' => 'Grade 11','strand' => 'ABM', 'scholarship' => true],
        ['first_name' => 'Clarisse', 'last_name' => 'Abad',      'middle_name' => 'Reyes',     'grade' => 'Grade 11','strand' => 'HUMSS', 'scholarship' => false],
        // Grade 12 (+3) — mix strands including one graduated, one transferred
        ['first_name' => 'Enzo', 'last_name' => 'Santiago',      'middle_name' => 'Ramos',     'grade' => 'Grade 12','strand' => 'STEM', 'scholarship' => false],
        ['first_name' => 'Katrina', 'last_name' => 'Dizon',      'middle_name' => 'Mercado',   'grade' => 'Grade 12','strand' => 'GAS', 'status' => 'graduated'],
        ['first_name' => 'Samuel', 'last_name' => 'Ortiz',       'middle_name' => 'Garcia',    'grade' => 'Grade 12','strand' => 'HUMSS', 'status' => 'transferred'],
    ];

    public function run(): void
    {
        $schoolYear = active_school_year();
        $password = Hash::make('Agnus2026!');
        $cashierIds = User::where('role_id', 3)->pluck('id')->toArray();
        $today = now();

        $religions = ['Catholic', 'Christian', 'Iglesia ni Cristo', 'Islam', 'Buddhist', 'None'];
        $citizenships = ['Filipino', 'Filipino', 'Filipino', 'Dual Filipino-American', 'Dual Filipino-Chinese'];
        $occupations = ['Teacher', 'Engineer', 'Nurse', 'Accountant', 'Business Owner', 'OFW', 'Government Employee', 'Housewife', 'Driver', 'Farmer', 'Mechanic', 'Architect'];
        $barangays = ['San Antonio', 'Santa Maria', 'San Jose', 'Santo Niño', 'San Isidro', 'Santiago', 'San Miguel', 'Santo Tomas', 'San Francisco', 'Santa Cruz'];
        $cities = ['Quezon City', 'Manila', 'Makati', 'Pasig', 'Mandaluyong', 'Caloocan', 'Pasay', 'Parañaque', 'Las Piñas', 'Taguig'];
        $middleNames = ['Bautista', 'Reyes', 'Aquino', 'Villanueva', 'Cruz', 'Garcia', 'Ramos', 'Torres', 'Dela Peña', 'Navarro', 'Santiago', 'Mercado', 'Pascual', 'Rivera', 'Fernando'];
        $relationships = ['Mother', 'Father', 'Aunt', 'Uncle', 'Grandmother', 'Grandfather', 'Sibling'];
        $previousSchools = ['Sample Elementary School', 'St. Mary\'s Academy', 'Holy Child School', 'Sacred Heart Academy', 'Don Bosco School', 'La Salle Greenhills', 'Ateneo de Manila', 'St. Scholastica\'s College', 'Immaculate Conception Academy'];

        foreach (self::$studentSeeds as $index => $seed) {
            $grade = $seed['grade'];
            $email = $this->resolveEmail($seed['first_name'], $seed['last_name']);
            $middleName = $seed['middle_name'] ?? $middleNames[array_rand($middleNames)];
            $studentStatus = $seed['status'] ?? 'enrolled';

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $seed['first_name'] . ' ' . $seed['last_name'],
                    'password' => $password,
                    'role_id' => 7,
                ]
            );

            $existingStudent = Student::where('user_id', $user->id)->first();
            $barangay = $barangays[array_rand($barangays)];
            $city = $cities[array_rand($cities)];

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_number' => $existingStudent ? $existingStudent->student_number : Student::generateStudentNumber(),
                    'first_name' => $seed['first_name'],
                    'middle_name' => $middleName,
                    'last_name' => $seed['last_name'],
                    'personal_email' => $email,
                    'date_of_birth' => now()->subYears(match ($grade) {
                        'Kinder' => 5, 'Grade 1' => 6, 'Grade 2' => 7, 'Grade 3' => 8,
                        'Grade 4' => 9, 'Grade 5' => 10, 'Grade 6' => 11, 'Grade 7' => 12,
                        'Grade 8' => 13, 'Grade 9' => 14, 'Grade 10' => 15,
                        'Grade 11' => 16, 'Grade 12' => 17, default => 10,
                    })->subDays(rand(100, 300)),
                    'place_of_birth' => $cities[array_rand($cities)] . ', Philippines',
                    'citizenship' => $citizenships[array_rand($citizenships)],
                    'religion' => $religions[array_rand($religions)],
                    'contact_number' => '+63917' . str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'permanent_address' => 'Blk ' . rand(1, 20) . ' Lot ' . rand(1, 30) . ', ' . $barangay . ', ' . $city,
                    'current_address' => 'Blk ' . rand(1, 20) . ' Lot ' . rand(1, 30) . ', ' . $barangay . ', ' . $city,
                    'legacy_lrn' => str_pad((string) rand(10000000000, 99999999999), 12, '0', STR_PAD_LEFT),
                    'father_name' => 'Mr. ' . $seed['first_name'] . ' ' . $middleName . ' ' . $seed['last_name'] . ' Sr.',
                    'father_occupation' => $occupations[array_rand($occupations)],
                    'mother_name' => 'Mrs. ' . $middleNames[array_rand($middleNames)] . ' ' . $seed['last_name'],
                    'mother_occupation' => $occupations[array_rand($occupations)],
                    'guardian_name' => 'Mr./Mrs. ' . $seed['first_name'] . ' ' . $seed['last_name'] . '\'s Guardian',
                    'guardian_contact' => '+63918' . str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'emergency_contact_name' => $seed['first_name'] . ' ' . $middleName . ' ' . $seed['last_name'] . '\'s Emergency Contact',
                    'emergency_contact_number' => '+63919' . str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'emergency_contact_relationship' => $relationships[array_rand($relationships)],
                    'previous_school' => $previousSchools[array_rand($previousSchools)],
                    'previous_school_address' => $cities[array_rand($cities)] . ', Philippines',
                    'status' => $studentStatus,
                    'scholarship' => $seed['scholarship'] ?? false,
                    'archived_at' => $studentStatus === 'graduated' ? $today->copy()->subMonths(rand(1, 3)) : null,
                ]
            );

            // Withdrawn/transferred and graduated students get archived_at too
            if (in_array($studentStatus, ['withdrawn', 'transferred'], true)) {
                $student->update([
                    'archived_at' => $today->copy()->subMonths(rand(1, 6)),
                    'archive_action' => $studentStatus === 'transferred' ? 'transferred' : 'withdrawn',
                    'archive_reason' => $studentStatus === 'transferred' ? 'Transferred to another school' : 'Voluntary withdrawal',
                ]);
            }

            $admissionTypes = ['New', 'New', 'New', 'Honor', 'Sibling', 'Transferee'];
            $admission = Admission::updateOrCreate(
                ['student_id' => $student->id, 'school_year' => $schoolYear],
                [
                    'application_type' => $admissionTypes[array_rand($admissionTypes)],
                    'grade_level' => $grade,
                    'strand' => $seed['strand'],
                    'status' => $studentStatus === 'graduated' ? 'Approved By Registrar' : 'Approved By Registrar',
                ]
            );

            $section = Section::where('grade_level', $grade)
                ->when($seed['strand'], fn($q, $strand) => $q->where('section_name', 'LIKE', "$strand%"))
                ->first() ?? Section::where('grade_level', $grade)->first();

            if (!$section) continue;

            $enrollment = Enrollment::updateOrCreate(
                ['student_id' => $student->id, 'school_year' => $schoolYear],
                [
                    'section_id' => $section->id,
                    'strand' => $seed['strand'],
                    'status' => $studentStatus === 'withdrawn' ? 'Withdrawn' : ($studentStatus === 'transferred' ? 'Transferred' : ($studentStatus === 'graduated' ? 'Graduated' : 'Active')),
                ]
            );

            $classes = Classes::where('grade_level', $grade)
                ->where('section', $section->section_name)
                ->where(function ($q) {
                    $q->whereNull('term')->orWhere('term', '');
                })
                ->get();

            if ($classes->isEmpty()) {
                $classes = Classes::where('grade_level', $grade)
                    ->where('section', $section->section_name)
                    ->get();
            }

            $pivotRows = [];
            foreach ($classes as $class) {
                $pivotRows[] = [
                    'enrollment_id' => $enrollment->id,
                    'class_id' => $class->id,
                ];
            }

            if (!empty($pivotRows)) {
                DB::table('enrollment_subject')->upsert(
                    $pivotRows,
                    ['enrollment_id', 'class_id']
                );
            }

            $schedules = FeeSchedule::where('grade_level', $grade)
                ->where('school_year', $schoolYear)
                ->get();

            $totalAssessed = $schedules->sum(fn($f) => $f->tuition_fee + $f->misc_fee);

            $discountType = null;
            $discountAmount = 0;
            if ($seed['scholarship'] ?? false) {
                $discountType = 'esc';
                $discountAmount = $schedules->sum('tuition_fee');
            } elseif (in_array($index, [3, 6, 45, 67], true)) {
                $discountType = 'honor';
                $discountAmount = round($totalAssessed * 0.10, 2);
            } elseif (in_array($index, [5, 14, 50, 62], true)) {
                $discountType = 'sibling';
                $discountAmount = round($totalAssessed * 0.05, 2);
            }

            // Varied payment behaviors - expanded for 75 students
            $effectiveAssessed = $totalAssessed - $discountAmount;
            $paymentRatio = match (true) {
                in_array($index, [0, 12, 42, 60], true) => 1.0,    // fully paid
                in_array($index, [2, 15, 47, 65], true) => 0.75,   // mostly paid
                in_array($index, [4, 18, 52, 68], true) => 0.5,    // half paid
                in_array($index, [7, 22, 57, 70], true) => 0.25,   // quarter paid
                in_array($index, [9, 20, 29, 44, 54, 63], true) => 0.0,    // unpaid (overdue)
                in_array($index, [38, 39, 40, 41, 53, 59, 73, 74], true) => 0.0, // withdrawn/graduated/transferred unpaid
                default => rand(30, 60) / 100,
            };
            $partialPayment = round($effectiveAssessed * $paymentRatio, 2);

            $clearanceStatus = 'Uncleared';
            if ($partialPayment >= $effectiveAssessed) {
                $clearanceStatus = 'Cleared';
            }

            $ledger = StudentLedger::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'payment_plan' => $index % 5 === 0 ? 'full' : 'installment',
                    'total_assessed' => $totalAssessed,
                    'discount_type' => $discountType,
                    'discount_applied' => $discountAmount,
                    'total_paid' => $partialPayment,
                    'balance' => max(0, $effectiveAssessed - $partialPayment),
                    'clearance_status' => $clearanceStatus,
                ]
            );

            if ($partialPayment > 0) {
                Payment::updateOrCreate(
                    ['receipt_number' => 'RCP-' . $student->student_number . '-001'],
                    [
                        'ledger_id' => $ledger->id,
                        'cashier_id' => !empty($cashierIds) ? $cashierIds[array_rand($cashierIds)] : null,
                        'amount_paid' => $partialPayment,
                        'payment_date' => $today->copy()->subDays(rand(1, 30)),
                    ]
                );
            }

            // Some students get a second payment
            if (($index % 3 === 0 && $index > 0 && $paymentRatio < 1.0) || in_array($index, [2, 5, 8, 11, 16, 21, 25])) {
                $extraPayment = round($effectiveAssessed * (rand(10, 25) / 100), 2);
                Payment::updateOrCreate(
                    ['receipt_number' => 'RCP-' . $student->student_number . '-002'],
                    [
                        'ledger_id' => $ledger->id,
                        'cashier_id' => !empty($cashierIds) ? $cashierIds[array_rand($cashierIds)] : null,
                        'amount_paid' => $extraPayment,
                        'payment_date' => $today->copy()->subDays(rand(3, 15)),
                    ]
                );

                $newPaid = $partialPayment + $extraPayment;
                $ledger->update([
                    'total_paid' => $newPaid,
                    'balance' => max(0, $effectiveAssessed - $newPaid),
                    'clearance_status' => ($ledger->payment_plan === 'full' && $newPaid >= $effectiveAssessed) ? 'Cleared' : 'Uncleared',
                ]);
            }
        }
    }
}
