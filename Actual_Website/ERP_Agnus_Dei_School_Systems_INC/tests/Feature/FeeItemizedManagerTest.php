<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\ClinicLog;
use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\LibraryTransaction;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentLedger;
use App\Models\User;
use App\Services\FeeAssessmentService;
use App\Services\HoldService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Spec: 1Dcoument&OtherInfo/specs/fee-itemized-manager.md §8 — Success Checks.
 */
class FeeItemizedManagerTest extends TestCase
{
    use RefreshDatabase;

    private User $directress;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Carbon::setTestNow('2026-09-15');
        Mail::fake();

        $this->directress = User::factory()->create(['role_id' => 8]);
        $this->cashier = User::factory()->create(['role_id' => 3]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function feePayload(float $miscTotal, float $books, float $uniform, float $id, float $misc): array
    {
        return [
            'grade_level' => 'Grade 1',
            'term' => '1st Term',
            'tuition_fee' => 16000,
            'misc_fee' => $miscTotal,
            'misc_fee_items' => [
                'books' => $books,
                'uniform' => $uniform,
                'id' => $id,
                'miscellaneous' => $misc,
            ],
            'school_year' => '2026-2027',
        ];
    }

    private function makeNewApplicant(string $schoolYear = '2026-2027'): Student
    {
        // New applicants carry no student number and no enrollments yet.
        $user = User::factory()->create(['role_id' => 7, 'email_verified_at' => now()]);
        $student = Student::create([
            'user_id' => $user->id,
            'first_name' => 'New',
            'last_name' => 'Applicant',
            'status' => 'applicant',
        ]);
        Admission::create([
            'student_id' => $student->id,
            'application_type' => 'New',
            'grade_level' => 'Grade 1',
            'school_year' => $schoolYear,
            'status' => 'Pending',
            'downpayment_receipt' => 'RCP-20260915-0001',
            'downpayment_paid_at' => now(),
        ]);

        return $student->refresh();
    }

    private function makeCurrentLedger(Student $student, string $path, float $balance, string $schoolYear = '2026-2027'): StudentLedger
    {
        return StudentLedger::create([
            'student_id' => $student->id,
            'school_year' => $schoolYear,
            'payment_plan' => $path,
            'assessment_path' => $path,
            'enrollment_fee' => $path === 'installment' ? 1500.00 : 0.0,
            'tuition_assessed' => 16000,
            'installment_count' => $path === 'installment' ? 10 : 0,
            'total_assessed' => 20000,
            'discount_applied' => 0,
            'total_paid' => 20000 - $balance,
            'balance' => $balance,
            'clearance_status' => $balance > 0 ? 'Uncleared' : 'Cleared',
        ]);
    }

    // §8.1 — Directress saves 4 boxes that add to total; mismatch blocked naming the difference.
    public function test_directress_saves_four_boxes_that_add_to_total(): void
    {
        $response = $this->actingAs($this->directress)->post(
            route('directress.fees.store'),
            $this->feePayload(5200, 2080, 1040, 520, 1560)
        );

        $response->assertRedirect(route('directress.fees'));
        $this->assertDatabaseHas('fee_schedules', [
            'grade_level' => 'Grade 1',
            'term' => '1st Term',
            'school_year' => '2026-2027',
        ]);
        $row = FeeSchedule::where('grade_level', 'Grade 1')->where('school_year', '2026-2027')->first();
        $this->assertSame(2080.00, (float) ($row->misc_fee_items['books'] ?? 0));
        $this->assertSame(1040.00, (float) ($row->misc_fee_items['uniform'] ?? 0));
        $this->assertSame(520.00, (float) ($row->misc_fee_items['id'] ?? 0));
        $this->assertSame(1560.00, (float) ($row->misc_fee_items['miscellaneous'] ?? 0));
    }

    public function test_directress_split_mismatch_blocked_naming_the_difference(): void
    {
        $response = $this->actingAs($this->directress)->post(
            route('directress.fees.store'),
            $this->feePayload(5200, 2000, 1000, 500, 1500)
        );

        $response->assertSessionHasErrors('misc_fee_items');
        $this->assertStringContainsString(
            'off by ₱200.00',
            (string) session('errors')->get('misc_fee_items')[0]
        );
        $this->assertDatabaseMissing('fee_schedules', [
            'grade_level' => 'Grade 1',
            'school_year' => '2026-2027',
        ]);
    }

    public function test_fee_update_keeps_split_never_wipes_to_one_line(): void
    {
        $this->actingAs($this->directress)->post(
            route('directress.fees.store'),
            $this->feePayload(5200, 2080, 1040, 520, 1560)
        );
        $fee = FeeSchedule::where('grade_level', 'Grade 1')->where('school_year', '2026-2027')->firstOrFail();

        $response = $this->actingAs($this->directress)->patch(
            route('directress.fees.update', $fee),
            $this->feePayload(6000, 2400, 1200, 600, 1800)
        );

        $response->assertRedirect(route('directress.fees'));
        $split = $fee->refresh()->misc_fee_items;
        $this->assertSame(2400.00, (float) ($split['books'] ?? 0));
        $this->assertSame(600.00, (float) ($split['id'] ?? 0));
    }

    // §8.2/§8.3 — Saved split feeds assessments as named lines (data source fix).
    public function test_saved_split_posts_named_assessment_lines(): void
    {
        FeeSchedule::create([
            'grade_level' => 'Grade 1',
            'term' => '1st Term',
            'school_year' => '2026-2027',
            'tuition_fee' => 16000,
            'misc_fee' => 5200,
            'misc_fee_items' => ['books' => 2080, 'uniform' => 1040, 'id' => 520, 'miscellaneous' => 1560],
        ]);

        $split = app(FeeAssessmentService::class)->splitSchedules(
            FeeSchedule::where('grade_level', 'Grade 1')->where('school_year', '2026-2027')->get()
        );

        $this->assertSame(2080.00, $split['items']['books']);
        $this->assertSame(520.00, $split['items']['id']);
        $this->assertSame(5200.00, round(array_sum($split['items']), 2));
    }

    // §8.4 — New installment file with receipt passes the gate despite monthly remainder.
    public function test_new_installment_with_receipt_passes_approval_gate(): void
    {
        $student = $this->makeNewApplicant();
        $this->makeCurrentLedger($student, 'installment', 18000.00);
        $admission = $student->admissions()->latest()->firstOrFail();

        $staffHolds = HoldService::forStudent($student->refresh());
        $this->assertNotEmpty(array_filter($staffHolds, fn (array $h): bool => $h['source'] === 'finance'));

        $approvalHolds = HoldService::forApproval($student->refresh(), $admission->refresh());
        $this->assertEmpty(array_filter($approvalHolds, fn (array $h): bool => $h['source'] === 'finance'));
    }

    // §8.5 — Returnee with last-year dues still blocked until clearance.
    public function test_returnee_with_prior_year_dues_still_blocked(): void
    {
        $user = User::factory()->create(['role_id' => 7, 'email_verified_at' => now()]);
        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => 'S-' . uniqid(),
            'first_name' => 'Return',
            'last_name' => 'Ee',
            'status' => 'enrolled',
        ]);
        $section = Section::create([
            'grade_level' => 'Grade 1',
            'section_name' => 'St. Test',
            'is_active' => true,
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'school_year' => '2026-2027',
            'status' => 'Active',
        ]);
        $this->makeCurrentLedger($student, 'installment', 5000.00, '2026-2027');
        Admission::create([
            'student_id' => $student->id,
            'application_type' => 'Old',
            'grade_level' => 'Grade 2',
            'school_year' => '2027-2028',
            'status' => 'Pending',
            'downpayment_receipt' => 'RCP-20260915-0002',
            'downpayment_paid_at' => now(),
        ]);
        $this->makeCurrentLedger($student, 'installment', 18000.00, '2027-2028');
        $admission = $student->admissions()->latest()->firstOrFail();

        $approvalHolds = HoldService::forApproval($student->refresh(), $admission->refresh());
        $this->assertNotEmpty(array_filter($approvalHolds, fn (array $h): bool => $h['source'] === 'finance'));
    }

    // §8.6 — Library overdue and open clinic cases still block new-on-receipt files.
    public function test_library_and_clinic_holds_still_block_new_on_receipt(): void
    {
        $student = $this->makeNewApplicant();
        $this->makeCurrentLedger($student, 'installment', 18000.00);
        LibraryTransaction::create([
            'student_id' => $student->id,
            'librarian_id' => $this->cashier->id,
            'book_title' => 'Overdue Book',
            'borrow_date' => '2026-08-01',
            'return_date' => '2026-08-10',
            'status' => 'Borrowed',
            'total_fees' => 100.00,
            'fees_assessed' => true,
        ]);
        ClinicLog::create([
            'student_id' => $student->id,
            'nurse_id' => $this->cashier->id,
            'complaint' => 'Follow-up check',
            'visit_date' => '2026-09-10',
            'is_open' => true,
        ]);
        $admission = $student->admissions()->latest()->firstOrFail();

        $approvalHolds = HoldService::forApproval($student->refresh(), $admission->refresh());
        $sources = array_column($approvalHolds, 'source');
        $this->assertContains('library', $sources);
        $this->assertContains('clinic', $sources);
        $this->assertNotContains('finance', $sources);
    }

    // Full-path new files with a real balance are not exempt — only the
    // installment monthly remainder is.
    public function test_full_path_balance_still_blocked(): void
    {
        $student = $this->makeNewApplicant();
        $this->makeCurrentLedger($student, 'full', 5000.00);
        $admission = $student->admissions()->latest()->firstOrFail();

        $approvalHolds = HoldService::forApproval($student->refresh(), $admission->refresh());
        $this->assertNotEmpty(array_filter($approvalHolds, fn (array $h): bool => $h['source'] === 'finance'));
    }
}
