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
        ['first_name' => 'Juan', 'last_name' => 'dela Cruz',   'middle_name' => 'Bautista',  'grade' => 'Kinder',  'strand' => null],
        ['first_name' => 'Maria', 'last_name' => 'Santos',      'middle_name' => 'Reyes',     'grade' => 'Grade 1', 'strand' => null],
        ['first_name' => 'Jose', 'last_name' => 'Reyes',        'middle_name' => 'Aquino',    'grade' => 'Grade 2', 'strand' => null],
        ['first_name' => 'Ana', 'last_name' => 'Gonzales',      'middle_name' => 'Villanueva','grade' => 'Grade 3', 'strand' => null],
        ['first_name' => 'Pedro', 'last_name' => 'Fernandez',    'middle_name' => 'Cruz',      'grade' => 'Grade 4', 'strand' => null],
        ['first_name' => 'Luisa', 'last_name' => 'Villanueva',   'middle_name' => 'Garcia',    'grade' => 'Grade 5', 'strand' => null],
        ['first_name' => 'Carlos', 'last_name' => 'Mendoza',     'middle_name' => 'Ramos',     'grade' => 'Grade 6', 'strand' => null],
        ['first_name' => 'Sofia', 'last_name' => 'Garcia',       'middle_name' => 'Torres',    'grade' => 'Grade 7', 'strand' => null],
        ['first_name' => 'Miguel', 'last_name' => 'Lopez',       'middle_name' => 'Dela Peña', 'grade' => 'Grade 8', 'strand' => null],
        ['first_name' => 'Isabella', 'last_name' => 'Martinez',  'middle_name' => 'Navarro',   'grade' => 'Grade 9', 'strand' => null],
        ['first_name' => 'Rafael', 'last_name' => 'Torres',      'middle_name' => 'Santiago',  'grade' => 'Grade 10','strand' => null],
        ['first_name' => 'Angela', 'last_name' => 'Ramirez',     'middle_name' => 'Mercado',   'grade' => 'Grade 11','strand' => 'STEM', 'scholarship' => true],
        ['first_name' => 'Dante', 'last_name' => 'Cruz',         'middle_name' => 'Pascual',   'grade' => 'Grade 12','strand' => 'ABM', 'scholarship' => false],
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

        foreach (self::$studentSeeds as $index => $seed) {
            $grade = $seed['grade'];
            $email = $this->resolveEmail($seed['first_name'], $seed['last_name']);
            $middleName = $seed['middle_name'] ?? $middleNames[array_rand($middleNames)];

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
                    'previous_school' => ['Sample Elementary School', 'St. Mary\'s Academy', 'Holy Child School', 'Sacred Heart Academy', 'Don Bosco School'][array_rand([0, 1, 2, 3, 4])],
                    'previous_school_address' => $cities[array_rand($cities)] . ', Philippines',
                    'status' => 'enrolled',
                    'scholarship' => $seed['scholarship'] ?? false,
                ]
            );

            $admissionTypes = ['New', 'New', 'New', 'Honor', 'Sibling'];
            $admission = Admission::updateOrCreate(
                ['student_id' => $student->id, 'school_year' => $schoolYear],
                [
                    'application_type' => $admissionTypes[array_rand($admissionTypes)],
                    'grade_level' => $grade,
                    'strand' => $seed['strand'],
                    'status' => 'Approved By Registrar',
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
                    'status' => 'Active',
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
            } elseif ($index === 3 || $index === 6) {
                $discountType = 'honor';
                $discountAmount = round($totalAssessed * 0.10, 2);
            } elseif ($index === 5) {
                $discountType = 'sibling';
                $discountAmount = round($totalAssessed * 0.05, 2);
            }

            $partialPayment = round($totalAssessed * (rand(30, 60) / 100), 2);
            $effectiveAssessed = $totalAssessed - $discountAmount;

            $ledger = StudentLedger::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'payment_plan' => 'installment',
                    'total_assessed' => $totalAssessed,
                    'discount_type' => $discountType,
                    'discount_applied' => $discountAmount,
                    'total_paid' => $partialPayment,
                    'balance' => max(0, $effectiveAssessed - $partialPayment),
                    'clearance_status' => 'Uncleared',
                ]
            );

            Payment::updateOrCreate(
                ['receipt_number' => 'RCP-' . $student->student_number . '-001'],
                [
                    'ledger_id' => $ledger->id,
                    'cashier_id' => $cashierIds[array_rand($cashierIds)],
                    'amount_paid' => $partialPayment,
                    'payment_date' => $today->copy()->subDays(rand(1, 30)),
                ]
            );

            if ($index % 2 === 0 && $index > 0) {
                $extraPayment = round($effectiveAssessed * (rand(10, 20) / 100), 2);
                Payment::updateOrCreate(
                    ['receipt_number' => 'RCP-' . $student->student_number . '-002'],
                    [
                        'ledger_id' => $ledger->id,
                        'cashier_id' => $cashierIds[array_rand($cashierIds)],
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
