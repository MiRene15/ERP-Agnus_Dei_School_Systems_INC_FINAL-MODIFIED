<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\FeeSchedule;
use App\Models\LibraryTransaction;
use App\Models\Payment;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentLedger;
use App\Models\User;
use App\Services\CashierProjectionService;
use App\Services\FeeAssessmentService;
use App\Services\HoldService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Spec: 1Dcoument&OtherInfo/specs/cashier-fee-rules.md §8 — Success Checks.
 */
class CashierFeeRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private User $directress;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Carbon::setTestNow('2026-09-15');
        Mail::fake();

        $this->cashier = User::factory()->create(['role_id' => 3]);
        $this->directress = User::factory()->create(['role_id' => 8]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeLedger(Student $student, string $schoolYear = '2026-2027'): StudentLedger
    {
        return StudentLedger::create([
            'student_id' => $student->id,
            'school_year' => $schoolYear,
            'payment_plan' => 'installment',
            'total_assessed' => 0,
            'discount_applied' => 0,
            'total_paid' => 0,
            'balance' => 0,
            'clearance_status' => 'Pending',
        ]);
    }

    private function makeStudent(string $schoolYear = '2026-2027'): Student
    {
        $user = User::factory()->create(['role_id' => 7, 'email_verified_at' => now()]);
        $student = Student::create([
            'user_id' => $user->id,
            'student_number' => 'S-' . uniqid(),
            'first_name' => 'Fee',
            'last_name' => 'Rules',
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
            'school_year' => $schoolYear,
            'status' => 'Active',
        ]);

        return $student->refresh();
    }

    private function seedGradeFees(string $grade = 'Grade 1', string $schoolYear = '2026-2027'): void
    {
        FeeSchedule::create([
            'grade_level' => $grade,
            'term' => '1st Term',
            'school_year' => $schoolYear,
            'tuition_fee' => 16000,
            'misc_fee' => 5200,
        ]);
    }

    // §8.1 — Fee list filters by school year; edits save under that year
    // only; a new year starts empty until rolled.
    public function test_fee_list_filters_by_school_year_and_new_year_starts_empty_until_rolled(): void
    {
        $this->seedGradeFees('Grade 1', '2026-2027');

        $response = $this->actingAs($this->directress)->get(route('directress.fees', ['school_year' => '2027-2028']));
        $response->assertOk();
        $this->assertSame('2027-2028', $response->viewData('selectedYear'));
        $this->assertTrue($response->viewData('fees')->isEmpty());

        $roll = $this->actingAs($this->directress)->post(route('directress.fees.roll-over'), [
            'from_year' => '2026-2027',
            'to_year' => '2027-2028',
        ]);
        $roll->assertRedirect(route('directress.fees', ['school_year' => '2027-2028']));

        $this->assertSame(1, FeeSchedule::forSchoolYear('2027-2028')->count());
        $this->assertSame(1, FeeSchedule::forSchoolYear('2026-2027')->count());

        $after = $this->actingAs($this->directress)->get(route('directress.fees', ['school_year' => '2027-2028']));
        $after->assertOk();
        $this->assertFalse($after->viewData('fees')->isEmpty());
    }

    // §8.2 — Full payment = 1.5k + 90% of tuition lines, computed once.
    public function test_full_payment_math(): void
    {
        $student = $this->makeStudent();
        $ledger = $this->makeLedger($student);

        app(FeeAssessmentService::class)->assessLedger($ledger->refresh(), 20000.00, '2026-2027', 'full');

        $ledger = $ledger->refresh();
        $this->assertSame(19500.00, round((float) $ledger->total_assessed, 2));
        $this->assertSame(0.00, (float) $ledger->discount_applied);

        $lines = $ledger->ledgerLines()->orderBy('id')->get();
        $this->assertCount(2, $lines);
        $this->assertSame(1500.00, (float) $lines[0]->amount_assessed);
        $this->assertSame('enrollment', $lines[0]->kind);
        $this->assertSame(18000.00, (float) $lines[1]->amount_assessed);
    }

    // §8.3 — Installment plan = 1.5k at enrollment + 10 equal monthly lines.
    public function test_installment_plan_math(): void
    {
        $student = $this->makeStudent();
        $ledger = $this->makeLedger($student);

        app(FeeAssessmentService::class)->assessLedger($ledger->refresh(), 20000.00, '2026-2027', 'installment');

        $ledger = $ledger->refresh();
        $this->assertSame(21500.00, round((float) $ledger->total_assessed, 2));

        $lines = $ledger->ledgerLines()->orderBy('id')->get();
        $this->assertCount(11, $lines);
        $months = $lines->where('kind', 'tuition');
        $this->assertCount(10, $months);
        $this->assertSame(20000.00, round((float) $months->sum('amount_assessed'), 2));

        $status = app(FeeAssessmentService::class)->statusFor($ledger);
        $this->assertSame('Has Balance', $status['status']);

        $floor = app(FeeAssessmentService::class)->floorFor($ledger);
        $this->assertSame(1500.00, $floor['floor']);
        $this->assertSame('Enrollment Fee', $floor['label']);
    }

    // §8.4 — Below-term-total payment → blocked naming the floor.
    public function test_below_floor_payment_blocked_naming_the_floor(): void
    {
        $student = $this->makeStudent();
        $this->seedGradeFees();

        $response = $this->actingAs($this->cashier)->post(route('cashier.payment.process', $student), [
            'payment_plan' => 'installment',
            'amount_paid' => 100,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Minimum', session('error'));
        $this->assertStringContainsString('Enrollment Fee', session('error'));
        $this->assertSame(0, Payment::count());
    }

    // §8.5 — Library due paid at Cashier → one shared receipt,
    // Librarian view reads paid, hold lifts.
    public function test_library_due_shared_receipt_and_hold_lifts(): void
    {
        $student = $this->makeStudent();
        $ledger = $this->makeLedger($student);

        $due = LibraryTransaction::create([
            'student_id' => $student->id,
            'librarian_id' => $this->cashier->id,
            'book_title' => 'Test Book',
            'borrow_date' => '2026-08-01',
            'return_date' => '2026-08-10',
            'status' => 'Borrowed',
            'total_fees' => 100.00,
            'fees_assessed' => true,
        ]);

        $payment = Payment::create([
            'ledger_id' => $ledger->id,
            'cashier_id' => $this->cashier->id,
            'amount_paid' => 100.00,
            'receipt_number' => 'RCP-TEST-0001',
            'payment_date' => now(),
        ]);

        app(FeeAssessmentService::class)->markLibraryDuesPaid($payment, collect([$due]));

        $this->assertDatabaseHas('library_transaction_payment', [
            'payment_id' => $payment->id,
            'library_transaction_id' => $due->id,
        ]);
        $this->assertSame($payment->id, $due->refresh()->paid_payment_id);
        $this->assertSame(100.00, (float) $payment->refresh()->library_amount);

        $holds = HoldService::forStudent($student->refresh());
        $this->assertEmpty(array_filter($holds, fn (array $h): bool => $h['source'] === 'library'));
    }

    // §8.6 — Family view shows Fully Paid / Has Balance + named breakdown.
    public function test_family_view_shows_status_first(): void
    {
        $student = $this->makeStudent();
        $ledger = $this->makeLedger($student);
        app(FeeAssessmentService::class)->assessLedger($ledger->refresh(), 20000.00, '2026-2027', 'installment');

        $response = $this->actingAs($student->user)->get(route('student.ledger'));

        $response->assertOk();
        $response->assertSee('Has Balance');
        $response->assertSee('Month 1');
    }

    // §8.7 — Projections show the yearly stacked bar (full vs installment).
    public function test_projections_show_yearly_stacked_bar(): void
    {
        $fullStudent = $this->makeStudent();
        $fullLedger = $this->makeLedger($fullStudent);
        app(FeeAssessmentService::class)->assessLedger($fullLedger->refresh(), 20000.00, '2026-2027', 'full');

        $instStudent = $this->makeStudent();
        $instLedger = $this->makeLedger($instStudent);
        app(FeeAssessmentService::class)->assessLedger($instLedger->refresh(), 20000.00, '2026-2027', 'installment');

        Payment::create([
            'ledger_id' => $fullLedger->id,
            'cashier_id' => $this->cashier->id,
            'amount_paid' => 5000.00,
            'receipt_number' => 'RCP-TEST-FULL',
            'payment_date' => '2026-07-10',
        ]);
        Payment::create([
            'ledger_id' => $instLedger->id,
            'cashier_id' => $this->cashier->id,
            'amount_paid' => 1500.00,
            'receipt_number' => 'RCP-TEST-INST',
            'payment_date' => '2026-07-12',
        ]);

        $response = $this->actingAs($this->cashier)->get(route('cashier.projections', ['school_year' => '2026-2027']));

        $response->assertOk();
        $response->assertSee('planStackChart', false);
        $this->assertCount(12, $response->viewData('stackFull'));
        $this->assertSame(5000.00, array_sum($response->viewData('stackFull')));
        $this->assertSame(1500.00, array_sum($response->viewData('stackInstallment')));
    }

    // §8.8 — Mid-year enrollee → remaining tuition over remaining months.
    public function test_mid_year_enrollee_proration(): void
    {
        $student = $this->makeStudent();
        $ledger = $this->makeLedger($student);

        app(FeeAssessmentService::class)->assessLedger($ledger->refresh(), 20000.00, '2026-2027', 'installment', 4);

        $lines = $ledger->refresh()->ledgerLines()->orderBy('id')->get();
        $this->assertCount(5, $lines);
        $this->assertSame(1500.00, (float) $lines->firstWhere('kind', 'enrollment')->amount_assessed);
        $this->assertSame(20000.00, round((float) $lines->where('kind', 'tuition')->sum('amount_assessed'), 2));
    }

    public function test_fully_paid_account_takes_no_further_payments(): void
    {
        $student = $this->makeStudent();
        $ledger = $this->makeLedger($student);
        $fees = app(FeeAssessmentService::class);
        $fees->assessLedger($ledger->refresh(), 20000.00, '2026-2027', 'full');
        $ledger = $ledger->refresh();
        $fees->allocatePayment($ledger, (float) $ledger->total_assessed);
        $ledger->refresh();
        $ledger->total_paid = (float) $ledger->ledgerLines()->sum('amount_paid');
        $ledger->balance = 0;
        $ledger->save();

        $response = $this->actingAs($this->cashier)->post(route('cashier.payment.process', $student), [
            'payment_plan' => 'full',
            'amount_paid' => 500,
        ]);

        $response->assertSessionHas('error');
        $this->assertStringContainsString('Fully Paid', session('error'));
    }
}
