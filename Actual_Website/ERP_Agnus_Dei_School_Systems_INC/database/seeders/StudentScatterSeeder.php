<?php

declare(strict_types=1);

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
use Illuminate\Support\Facades\Hash;

class StudentScatterSeeder extends Seeder
{
    public const TARGET_PER_SECTION = 15;

    /** students.max(id) before this batch first ran — resume-safe name-slot offset */
    public const BASELINE_STUDENT_ID = 167;

    protected function resolveEmail(string $firstName, string $lastName): string
    {
        $base = strtolower(str_replace(' ', '', $firstName) . '.' . str_replace(' ', '', $lastName));
        $baseEmail = $base . '@agnusdei.edu.ph';
        if (!User::where('email', $baseEmail)->exists()) return $baseEmail;

        $counter = 1;
        $email = $base . $counter . '@agnusdei.edu.ph';
        while (User::where('email', $email)->exists()) {
            $counter++;
            $email = $base . $counter . '@agnusdei.edu.ph';
        }
        return $email;
    }

    public function run(): void
    {
        $schoolYear = active_school_year();
        $password = Hash::make('Agnus2026!');
        $cashierIds = User::where('role_id', 3)->pluck('id')->toArray();
        $today = now();

        $beforeUsers = (int) \DB::table('users')->max('id');
        $beforeStudents = (int) \DB::table('students')->max('id');
        $this->command->info("Baseline id ranges — users <= {$beforeUsers}, students <= {$beforeStudents}");

        $firstNames = [
            'Aiden', 'Althea', 'Amber', 'Anton', 'Bea', 'Benjamin', 'Bless', 'Carlo', 'Catherine', 'Cedric',
            'Dianne', 'Dominic', 'Elena', 'Emman', 'Erika', 'Francis', 'Gabriel', 'Gemma', 'Hannah', 'Hazel',
            'Ivan', 'Jasmine', 'Jomar', 'Joyce', 'Kevin', 'Lara', 'Lester', 'Maica', 'Nico', 'Olivia',
        ];
        $lastNames = [
            'Aguilar', 'Bartolome', 'Castillo', 'Dimaculangan', 'Espinosa', 'Fabian', 'Gatchalian', 'Hernandez', 'Ignacio', 'Jimenez',
            'Lardizabal', 'Manalo', 'Natividad', 'Ocampo', 'Panganiban', 'Quizon', 'Rosales', 'Salvador', 'Tolentino', 'Ubaldo',
            'Vergara', 'Whitmore', 'Yamamoto', 'Zabala',
        ];
        $middlePool = ['Bautista', 'Reyes', 'Aquino', 'Villanueva', 'Cruz', 'Garcia', 'Ramos', 'Torres', 'Navarro', 'Santiago'];
        $religions = ['Catholic', 'Christian', 'Iglesia ni Cristo', 'Islam', 'Buddhist', 'None'];
        $citizenships = ['Filipino', 'Filipino', 'Filipino', 'Dual Filipino-American', 'Dual Filipino-Chinese'];
        $occupations = ['Teacher', 'Engineer', 'Nurse', 'Accountant', 'Business Owner', 'OFW', 'Government Employee', 'Housewife', 'Driver', 'Farmer', 'Mechanic', 'Architect'];
        $barangays = ['San Antonio', 'Santa Maria', 'San Jose', 'Santo Niño', 'San Isidro', 'Santiago', 'San Miguel', 'Santo Tomas', 'San Francisco', 'Santa Cruz'];
        $cities = ['Quezon City', 'Manila', 'Makati', 'Pasig', 'Mandaluyong', 'Caloocan', 'Pasay', 'Parañaque', 'Las Piñas', 'Taguig'];
        $relationships = ['Mother', 'Father', 'Aunt', 'Uncle', 'Grandmother', 'Grandfather', 'Sibling'];
        $previousSchools = ['Sample Elementary School', 'St. Mary\'s Academy', 'Holy Child School', 'Sacred Heart Academy', 'Don Bosco School', 'La Salle Greenhills', 'Ateneo de Manila', 'St. Scholastica\'s College', 'Immaculate Conception Academy'];
        $admissionTypes = ['New', 'New', 'New', 'Honor', 'Sibling', 'Transferee'];

        $sections = Section::where('is_active', true)
            ->orderBy('grade_level')
            ->orderBy('section_name')
            ->get();

        // resume-safe: continue the name sequence where a previous (possibly killed) run stopped
        $slot = (int) Student::where('id', '>', self::BASELINE_STUDENT_ID)->count();
        $created = 0;
        $summary = [];

        foreach ($sections as $section) {
            $current = Enrollment::where('section_id', $section->id)
                ->where('status', 'Active')
                ->count();
            $deficit = self::TARGET_PER_SECTION - $current;

            $grade = $section->grade_level;
            $strand = null;
            if (in_array($grade, ['Grade 11', 'Grade 12'], true)) {
                // Trim the prefix so "STEM - ..." never saves as "STEM ".
                $strand = trim(explode('-', $section->section_name)[0]) ?: null;
            }

            $made = 0;
            if ($deficit > 0) {
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

                for ($i = 0; $i < $deficit; $i++, $slot++) {
                    $firstName = $firstNames[$slot % count($firstNames)];
                    $lastName = $lastNames[intdiv($slot, count($firstNames)) % count($lastNames)];
                    $middleName = $middlePool[$slot % count($middlePool)];
                    $email = $this->resolveEmail($firstName, $lastName);
                    $scholarship = $slot % 10 === 4;

                    $user = User::updateOrCreate(
                        ['email' => $email],
                        [
                            'name' => $firstName . ' ' . $lastName,
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
                            'first_name' => $firstName,
                            'middle_name' => $middleName,
                            'last_name' => $lastName,
                            'gender' => (($scatterGender = rand(1, 100)) <= 47 ? 'Male' : ($scatterGender <= 94 ? 'Female' : ($scatterGender <= 97 ? 'Non-binary' : 'Prefer not to say'))),
                            'personal_email' => $email,
                            'date_of_birth' => now()->subYears(match ($grade) {
                                'Kinder' => 5, 'Grade 1' => 6, 'Grade 2' => 7, 'Grade 3' => 8,
                                'Grade 4' => 9, 'Grade 5' => 10, 'Grade 6' => 11, 'Grade 7' => 12,
                                'Grade 8' => 13, 'Grade 9' => 14, 'Grade 10' => 15,
                                'Grade 11' => 16, 'Grade 12' => 17, default => 10,
                            })->subDays(mt_rand(100, 300)),
                            'place_of_birth' => $cities[array_rand($cities)] . ', Philippines',
                            'citizenship' => $citizenships[array_rand($citizenships)],
                            'religion' => $religions[array_rand($religions)],
                            'contact_number' => '+63917' . str_pad((string) mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                            'permanent_address' => 'Blk ' . mt_rand(1, 20) . ' Lot ' . mt_rand(1, 30) . ', ' . $barangay . ', ' . $city,
                            'current_address' => 'Blk ' . mt_rand(1, 20) . ' Lot ' . mt_rand(1, 30) . ', ' . $barangay . ', ' . $city,
                            'legacy_lrn' => str_pad((string) mt_rand(10000000000, 99999999999), 12, '0', STR_PAD_LEFT),
                            'father_name' => 'Mr. ' . $firstName . ' ' . $middleName . ' ' . $lastName . ' Sr.',
                            'father_occupation' => $occupations[array_rand($occupations)],
                            'mother_name' => 'Mrs. ' . $middlePool[array_rand($middlePool)] . ' ' . $lastName,
                            'mother_occupation' => $occupations[array_rand($occupations)],
                            'guardian_name' => 'Mr./Mrs. ' . $firstName . ' ' . $lastName . '\'s Guardian',
                            'guardian_contact' => '+63918' . str_pad((string) mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                            'emergency_contact_name' => $firstName . ' ' . $middleName . ' ' . $lastName . '\'s Emergency Contact',
                            'emergency_contact_number' => '+63919' . str_pad((string) mt_rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                            'emergency_contact_relationship' => $relationships[array_rand($relationships)],
                            'previous_school' => $previousSchools[array_rand($previousSchools)],
                            'previous_school_address' => $cities[array_rand($cities)] . ', Philippines',
                            'status' => 'enrolled',
                            'scholarship' => $scholarship,
                            'archived_at' => null,
                        ]
                    );

                    $statusRoll = rand(0, 99);
                    $admissionStatus = match (true) {
                        $statusRoll < 60 => 'Approved By Registrar',
                        $statusRoll < 85 => 'Pending',
                        default => 'Rejected',
                    };
                    Admission::updateOrCreate(
                        ['student_id' => $student->id, 'school_year' => $schoolYear],
                        [
                            'application_type' => $admissionTypes[array_rand($admissionTypes)],
                            'grade_level' => $grade,
                            'strand' => $strand,
                            'status' => $admissionStatus,
                        ]
                    );

                    $enrollment = Enrollment::updateOrCreate(
                        ['student_id' => $student->id, 'school_year' => $schoolYear],
                        [
                            'section_id' => $section->id,
                            'strand' => $strand,
                            'status' => 'Active',
                        ]
                    );

                    $pivotRows = [];
                    foreach ($classes as $class) {
                        $pivotRows[] = [
                            'enrollment_id' => $enrollment->id,
                            'class_id' => $class->id,
                        ];
                    }
                    if (!empty($pivotRows)) {
                        \DB::table('enrollment_subject')->upsert($pivotRows, ['enrollment_id', 'class_id']);
                    }

                    // Ledger + payments (varied mix)
                    $schedules = FeeSchedule::where('grade_level', $grade)
                        ->where('school_year', $schoolYear)
                        ->get();
                    $totalAssessed = $schedules->sum(fn($f) => $f->tuition_fee + $f->misc_fee);

                    $discountType = null;
                    $discountAmount = 0;
                    if ($scholarship) {
                        $discountType = 'esc';
                        $discountAmount = $schedules->sum('tuition_fee');
                    } elseif ($slot % 13 === 2) {
                        $discountType = 'honor';
                        $discountAmount = round($totalAssessed * 0.10, 2);
                    } elseif ($slot % 13 === 7) {
                        $discountType = 'sibling';
                        $discountAmount = round($totalAssessed * 0.05, 2);
                    }

                    $effectiveAssessed = $totalAssessed - $discountAmount;
                    $paymentRatio = match (true) {
                        $slot % 14 === 3 => 0.0,
                        $slot % 14 === 6 => 0.5,
                        $slot % 14 === 10 => 1.0,
                        $slot % 14 === 1 => 0.75,
                        default => mt_rand(25, 65) / 100,
                    };
                    $partialPayment = round($effectiveAssessed * $paymentRatio, 2);

                    $ledger = StudentLedger::updateOrCreate(
                        ['student_id' => $student->id],
                        [
                            'payment_plan' => $slot % 5 === 0 ? 'full' : 'installment',
                            'total_assessed' => $totalAssessed,
                            'discount_type' => $discountType,
                            'discount_applied' => $discountAmount,
                            'total_paid' => $partialPayment,
                            'balance' => max(0, $effectiveAssessed - $partialPayment),
                            'clearance_status' => $partialPayment >= $effectiveAssessed ? 'Cleared' : 'Uncleared',
                        ]
                    );

                    if ($partialPayment > 0) {
                        Payment::updateOrCreate(
                            ['receipt_number' => 'RCP-' . $student->student_number . '-001'],
                            [
                                'ledger_id' => $ledger->id,
                                'cashier_id' => !empty($cashierIds) ? $cashierIds[array_rand($cashierIds)] : null,
                                'amount_paid' => $partialPayment,
                                'payment_date' => $today->copy()->subDays(mt_rand(1, 30)),
                            ]
                        );
                    }

                    $made++;
                    $created++;
                }
            }

            $summary[] = sprintf('  %-14s %-10s %2d -> %2d%s', $grade, $section->section_name, $current, $current + $made, $made ? " (+{$made})" : '');
        }

        foreach ($summary as $line) $this->command->info($line);

        $afterUsers = (int) \DB::table('users')->max('id');
        $afterStudents = (int) \DB::table('students')->max('id');
        $active = Enrollment::where('status', 'Active')->count();
        $this->command->info("Created {$created} students. Active enrollments now: {$active}");
        $this->command->info("Cleanup id ranges — users ({$beforeUsers}, {$afterUsers}], students ({$beforeStudents}, {$afterStudents}]");
    }
}
