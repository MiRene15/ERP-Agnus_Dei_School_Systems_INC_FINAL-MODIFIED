<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GraduationFee;
use App\Models\StudentGraduationFee;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AuditLogsAndExtrasSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAuditLogs();
        $this->seedLockedSchoolYears();
        $this->seedGraduationFees();
        $this->seedWithdrawals();
        $this->seedInquiries();
        $this->seedRequirements();
    }

    private function seedAuditLogs(): void
    {
        $users = User::whereIn('email', [
            'admin@agnusdei.local', 'registrar@agnusdei.local', 'cashier1@agnusdei.local',
            'cashier2@agnusdei.local', 'library@agnusdei.local', 'clinic@agnusdei.local',
            'directress@agnusdei.local', 'principal@agnusdei.local',
        ])->get()->keyBy('email');

        $admin = $users['admin@agnusdei.local']?->id;
        $registrar = $users['registrar@agnusdei.local']?->id;
        $cashier = $users['cashier1@agnusdei.local']?->id;
        $librarian = $users['library@agnusdei.local']?->id;
        $nurse = $users['clinic@agnusdei.local']?->id;
        $directress = $users['directress@agnusdei.local']?->id;

        $student = Student::orderBy('id')->first();
        $student2 = Student::orderBy('id')->skip(1)->first();
        $payment = Payment::with('ledger')->orderByDesc('payment_date')->first();
        $enrollment = Enrollment::where('status', 'Active')->first();

        $today = now();
        // [event, causer_id, subject_type, subject_id, description, days_ago]
        $entries = [
            ['Login', $admin, null, null, 'System Admin logged in.', 14],
            ['Login', $cashier, null, null, 'Cashier Window 1 logged in.', 14],
            ['Login', $registrar, null, null, 'Head Registrar logged in.', 13],
            ['Login Failed', null, null, null, 'Failed login attempt for unknown account (IP 127.0.0.1).', 13],
            ['Logout', $admin, null, null, 'System Admin logged out.', 13],
            ['Payment', $cashier, 'App\Models\Payment', $payment?->id, 'Payment of ₱' . number_format($payment?->amount_paid ?? 0, 2) . ' recorded. Receipt ' . ($payment?->receipt_number ?? 'N/A') . '.', 12],
            ['Payment', $cashier, 'App\Models\Payment', $payment?->id, 'Payment of ₱1,500.00 recorded. Receipt OR-2026-0142.', 11],
            ['Payment', $cashier, 'App\Models\Payment', null, 'Payment of ₱3,200.00 recorded. Receipt OR-2026-0157.', 9],
            ['Account Confirmed', $registrar, 'App\Models\User', $admin, 'System Admin confirmed pending account: Juan Dela Cruz (juan.delacruz@agnusdei.edu.ph).', 12],
            ['Account Confirmed', $registrar, 'App\Models\User', null, 'Head Registrar confirmed pending account: Maria Santos (maria.santos@agnusdei.local).', 10],
            ['Settings Updated', $admin, null, null, 'System Admin updated settings: current_term.', 12],
            ['Settings Updated', $directress, null, null, 'School Directress updated settings: active_school_year.', 11],
            ['Fee Schedule Updated', $directress, null, null, 'School Directress updated fee schedule for Grade 11 (1st Term).', 11],
            ['Graduation Fee Assigned', $directress, null, null, 'School Directress assigned graduation fee to 12 students of Grade 12.', 10],
            ['School Year Lock Toggled', $directress, 'App\Models\SchoolYear', null, 'School Directress locked school year: 2025-2026.', 10],
            ['Grades Submitted', null, null, null, 'Grades submitted for Grade 9 - Sampaguita (1st Term).', 9],
            ['Grades Submitted', $registrar, null, null, 'Batch grades submitted for Grade 10 - Ilang-Ilang (1st Term).', 8],
            ['Exported', $admin, 'App\Models\Payment', null, 'System Admin exported the collections report CSV (48 payments, 2026-09-01 to 2026-09-30).', 8],
            ['Exported', $directress, 'App\Models\StudentLedger', null, 'School Directress exported the receivables report CSV (31 student(s), total ₱128,450.00).', 7],
            ['Report Card Exported', $registrar, null, null, 'Head Registrar exported report cards for Grade 6 - Narra (52 cards).', 7],
            ['Book Borrowed', $librarian, null, null, 'Head Librarian recorded a book borrow: The Little Prince by Antoine de Saint-Exupery.', 7],
            ['Book Returned', $librarian, null, null, 'Head Librarian recorded a book return: Charlotte\'s Web (1 day late, ₱5.00 fine).', 6],
            ['Library Clock In', $librarian, null, null, 'Student clocked in to the library.', 6],
            ['Clinic Log Created', $nurse, 'App\Models\Student', $student?->id, 'School Nurse recorded a clinic visit for ' . trim(($student?->first_name ?? '') . ' ' . ($student?->last_name ?? '')) . ': Tension headache with mild fever.', 6],
            ['Clinic Log Created', $nurse, 'App\Models\Student', $student2?->id, 'School Nurse recorded a clinic visit for ' . trim(($student2?->first_name ?? '') . ' ' . ($student2?->last_name ?? '')) . ': Mild indigestion.', 5],
            ['Announcement Created', $admin, null, null, 'System Admin created announcement: Enrollment for SY 2026-2027 Opens.', 5],
            ['Withdrawal Approved', $registrar, 'App\Models\Enrollment', $enrollment?->id, 'Head Registrar approved withdrawal for Bianca Lopez (Grade 4).', 4],
            ['Withdrawal Rejected', $registrar, 'App\Models\Enrollment', null, 'Head Registrar rejected withdrawal request: incomplete requirements.', 4],
            ['Requirement Verified', $registrar, null, null, 'Head Registrar verified requirements: PSA Birth Certificate (Form 138 on file).', 4],
            ['Archived', $registrar, 'App\Models\Student', $student?->id, 'Student archived: voluntary withdrawal.', 3],
            ['Profile Updated', $directress, 'App\Models\User', $directress, 'School Directress updated her profile.', 3],
            ['Password Changed', $admin, 'App\Models\User', $admin, 'System Admin changed his password.', 2],
            ['Status Changed', $registrar, 'App\Models\Enrollment', null, 'Enrollment status changed from Pending to Active.', 2],
            ['Login', $directress, null, null, 'School Directress logged in.', 1],
            ['Logout', $cashier, null, null, 'Cashier Window 1 logged out.', 1],
        ];

        foreach ($entries as [$event, $causerId, $subjectType, $subjectId, $description, $daysAgo]) {
            $at = $today->copy()->subDays($daysAgo)->subHours(rand(0, 8))->subMinutes(rand(0, 59));
            DB::table('activity_log')->updateOrInsert(
                ['description' => $description],
                [
                    'event' => $event,
                    'causer_id' => $causerId,
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'properties' => json_encode([]),
                    'created_at' => $at,
                    'updated_at' => $at,
                ]
            );
        }
    }

    private function seedLockedSchoolYears(): void
    {
        Setting::setValue('locked_school_years', '');
    }

    private function seedGraduationFees(): void
    {
        $schoolYear = active_school_year();
        $feeRows = [
            ['grade_level' => 'Grade 10', 'graduation_fee' => 1500.00, 'other_fees' => 250.00],
            ['grade_level' => 'Grade 12', 'graduation_fee' => 2500.00, 'other_fees' => 500.00],
        ];

        foreach ($feeRows as $row) {
            $graduationFee = GraduationFee::updateOrCreate(
                ['grade_level' => $row['grade_level'], 'school_year' => $schoolYear],
                ['graduation_fee' => $row['graduation_fee'], 'other_fees' => $row['other_fees']]
            );

            $enrollments = Enrollment::with('student')
                ->where('status', 'Active')
                ->whereHas('section', fn($q) => $q->where('grade_level', $row['grade_level']))
                ->get();

            foreach ($enrollments as $index => $enrollment) {
                StudentGraduationFee::updateOrCreate(
                    [
                        'student_id' => $enrollment->student_id,
                        'graduation_fee_id' => $graduationFee->id,
                    ],
                    [
                        'enrollment_id' => $enrollment->id,
                        'amount' => $graduationFee->graduation_fee,
                        'paid' => $index % 3 === 0,
                    ]
                );
            }
        }
    }

    private function seedWithdrawals(): void
    {
        $admin = User::where('email', 'admin@agnusdei.local')->first()
            ?? User::where('role_id', 1)->first();

        $withdrawn = Enrollment::with('student')
            ->whereIn('status', ['Withdrawn', 'Transferred'])
            ->get();

        foreach ($withdrawn as $enrollment) {
            \App\Models\Withdrawal::updateOrCreate(
                ['enrollment_id' => $enrollment->id],
                [
                    'student_id' => $enrollment->student_id,
                    'reason' => $enrollment->status === 'Transferred'
                        ? 'Transferred to another school.'
                        : 'Family relocation / voluntary withdrawal.',
                    'status' => 'Approved',
                    'processed_by' => $admin?->id,
                    'remarks' => 'Seeded record — no outstanding balance.',
                    'refund_amount' => 0,
                    'refund_processed_at' => now()->subDays(7),
                ]
            );
        }
    }

    private function seedInquiries(): void
    {
        $rows = [
            ['first_name' => 'Angela', 'last_name' => 'Bautista', 'personal_email' => 'angela.bautista@example.com'],
            ['first_name' => 'Mark', 'last_name' => 'Fernandez', 'personal_email' => 'mark.fernandez@example.com'],
            ['first_name' => 'Princess', 'last_name' => 'Gonzaga', 'personal_email' => 'princess.gonzaga@example.com'],
        ];

        foreach ($rows as $row) {
            DB::table('inquiries')->updateOrInsert(
                ['personal_email' => $row['personal_email']],
                [
                    'first_name' => $row['first_name'],
                    'last_name' => $row['last_name'],
                    'otp_code' => '000000',
                    'status' => 'pending',
                    'expires_at' => now()->addDays(7),
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(3),
                ]
            );
        }
    }

    private function seedRequirements(): void
    {
        $documentTypes = ['PSA Birth Certificate', 'Form 138', 'Good Moral'];
        $statuses = ['Verified', 'Verified', 'Under Review'];

        $admissions = DB::table('admissions')->orderBy('id')->limit(15)->get();

        foreach ($admissions as $index => $admission) {
            foreach ($documentTypes as $docIndex => $documentType) {
                DB::table('requirements')->updateOrInsert(
                    ['admission_id' => $admission->id, 'document_type' => $documentType],
                    [
                        'file_path' => 'requirements/' . $admission->id . '/' . strtolower(str_replace(' ', '_', $documentType)) . '.pdf',
                        'status' => $statuses[($index + $docIndex) % count($statuses)],
                        'created_at' => now()->subDays(10 - min($docIndex, 9)),
                        'updated_at' => now()->subDays(10 - min($docIndex, 9)),
                    ]
                );
            }
        }
    }
}
