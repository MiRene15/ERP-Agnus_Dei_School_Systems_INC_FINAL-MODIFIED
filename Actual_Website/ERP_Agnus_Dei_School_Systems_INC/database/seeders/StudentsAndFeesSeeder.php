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
        ['first_name' => 'Juan', 'last_name' => 'dela Cruz',   'grade' => 'Kinder',  'strand' => null],
        ['first_name' => 'Maria', 'last_name' => 'Santos',      'grade' => 'Grade 1', 'strand' => null],
        ['first_name' => 'Jose', 'last_name' => 'Reyes',        'grade' => 'Grade 2', 'strand' => null],
        ['first_name' => 'Ana', 'last_name' => 'Gonzales',      'grade' => 'Grade 3', 'strand' => null],
        ['first_name' => 'Pedro', 'last_name' => 'Fernandez',    'grade' => 'Grade 4', 'strand' => null],
        ['first_name' => 'Luisa', 'last_name' => 'Villanueva',   'grade' => 'Grade 5', 'strand' => null],
        ['first_name' => 'Carlos', 'last_name' => 'Mendoza',     'grade' => 'Grade 6', 'strand' => null],
        ['first_name' => 'Sofia', 'last_name' => 'Garcia',       'grade' => 'Grade 7', 'strand' => null],
        ['first_name' => 'Miguel', 'last_name' => 'Lopez',       'grade' => 'Grade 8', 'strand' => null],
        ['first_name' => 'Isabella', 'last_name' => 'Martinez',  'grade' => 'Grade 9', 'strand' => null],
        ['first_name' => 'Rafael', 'last_name' => 'Torres',      'grade' => 'Grade 10','strand' => null],
        ['first_name' => 'Angela', 'last_name' => 'Ramirez',     'grade' => 'Grade 11','strand' => 'STEM', 'scholarship' => true],
        ['first_name' => 'Dante', 'last_name' => 'Cruz',         'grade' => 'Grade 12','strand' => 'ABM', 'scholarship' => false],
    ];

    public function run(): void
    {
        $schoolYear = active_school_year();
        $password = Hash::make('Agnus2026!');
        $cashierIds = User::where('role_id', 3)->pluck('id')->toArray();
        $today = now();

        foreach (self::$studentSeeds as $index => $seed) {
            $grade = $seed['grade'];
            $email = $this->resolveEmail($seed['first_name'], $seed['last_name']);

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $seed['first_name'] . ' ' . $seed['last_name'],
                    'password' => $password,
                    'role_id' => 7,
                ]
            );

            $existingStudent = Student::where('user_id', $user->id)->first();
            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'student_number' => $existingStudent ? $existingStudent->student_number : Student::generateStudentNumber(),
                    'first_name' => $seed['first_name'],
                    'last_name' => $seed['last_name'],
                    'personal_email' => $email,
                    'date_of_birth' => now()->subYears(match ($grade) {
                        'Kinder' => 5, 'Grade 1' => 6, 'Grade 2' => 7, 'Grade 3' => 8,
                        'Grade 4' => 9, 'Grade 5' => 10, 'Grade 6' => 11, 'Grade 7' => 12,
                        'Grade 8' => 13, 'Grade 9' => 14, 'Grade 10' => 15,
                        'Grade 11' => 16, 'Grade 12' => 17, default => 10,
                    })->subDays(rand(100, 300)),
                    'contact_number' => '+63917' . str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'permanent_address' => 'Sample Address, Barangay ' . chr(rand(65, 75)) . ' City',
                    'father_name' => 'Mr. ' . $seed['first_name'] . ' Sr.',
                    'mother_name' => 'Mrs. ' . $seed['last_name'],
                    'guardian_name' => $seed['first_name'] . ' ' . $seed['last_name'] . '\'s Guardian',
                    'guardian_contact' => '+63918' . str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'emergency_contact_name' => $seed['first_name'] . ' ' . $seed['last_name'] . '\'s Emergency Contact',
                    'emergency_contact_number' => '+63919' . str_pad((string) rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                    'status' => 'enrolled',
                    'previous_school' => 'Sample Previous School',
                    'scholarship' => $seed['scholarship'] ?? false,
                ]
            );

            $admission = Admission::updateOrCreate(
                ['student_id' => $student->id, 'school_year' => $schoolYear],
                [
                    'application_type' => 'New',
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
            $partialPayment = round($totalAssessed * (rand(30, 60) / 100), 2);

            $ledger = StudentLedger::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'payment_plan' => 'installment',
                    'total_assessed' => $totalAssessed,
                    'discount_applied' => 0,
                    'total_paid' => $partialPayment,
                    'balance' => $totalAssessed - $partialPayment,
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

            if ($index % 3 === 0 && $index > 0) {
                $extraPayment = round($totalAssessed * (rand(10, 20) / 100), 2);
                Payment::updateOrCreate(
                    ['receipt_number' => 'RCP-' . $student->student_number . '-002'],
                    [
                        'ledger_id' => $ledger->id,
                        'cashier_id' => $cashierIds[array_rand($cashierIds)],
                        'amount_paid' => $extraPayment,
                        'payment_date' => $today->copy()->subDays(rand(3, 15)),
                    ]
                );

                $ledger->update([
                    'total_paid' => $partialPayment + $extraPayment,
                    'balance' => $totalAssessed - $partialPayment - $extraPayment,
                ]);
            }
        }
    }
}
