<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\AdmissionCredentialsMail;
use App\Mail\PaymentConfirmationMail;
use App\Models\Admission;
use App\Models\Classes;
use App\Models\FeeSchedule;
use App\Models\Payment;
use App\Models\Requirement;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Spec: 1Dcoument&OtherInfo/specs/enrollment-payment-gating-payment-access.md §8 — Success Checks.
 *
 * Run one test at a time on PostgreSQL (phpunit.xml defaults to MySQL):
 *   set DB_CONNECTION=pgsql&& set DB_DATABASE=agnusdei_erp_testing&& php artisan test --filter=test_forwarded_file_confirms_downpayment
 */
class DownpaymentAccessTest extends TestCase
{
    use RefreshDatabase;

    private const SY = '2026-2027';

    private const REQUIRED_DOCS = [
        'PSA Birth Certificate',
        'Form 138 (Report Card)',
        'Good Moral Certificate',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Mail::fake();
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function makeFamily(string $email = 'parent@example.com'): User
    {
        return User::factory()->create([
            'role_id' => 7,
            'email_verified_at' => now(),
            'email' => $email,
        ]);
    }

    private function makeCashier(): User
    {
        return User::factory()->create(['role_id' => 3]);
    }

    private function makeRegistrar(): User
    {
        return User::factory()->create(['role_id' => 2]);
    }

    private function makeStudent(User $user, string $firstName = 'Ana', string $lastName = 'Cruz'): Student
    {
        return Student::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => 'Female',
            'date_of_birth' => '2015-05-10',
            'personal_email' => strtolower($firstName) . '.' . strtolower($lastName) . '@example.com',
            'status' => 'Awaiting Downpayment',
        ]);
    }

    private function makeForwardedFile(User $user): Admission
    {
        $student = $this->makeStudent($user);

        $admission = Admission::create([
            'student_id' => $student->id,
            'application_type' => 'New',
            'grade_level' => 'Grade 1',
            'strand' => null,
            'school_year' => self::SY,
            'status' => 'Awaiting Downpayment',
        ]);

        foreach (self::REQUIRED_DOCS as $doc) {
            Requirement::create([
                'admission_id' => $admission->id,
                'document_type' => $doc,
                'status' => 'Verified',
            ]);
        }

        return $admission;
    }

    private function seedFees(): void
    {
        foreach (['1st Term', '2nd Term', '3rd Term'] as $term) {
            FeeSchedule::create([
                'grade_level' => 'Grade 1',
                'term' => $term,
                'tuition_fee' => 5000,
                'misc_fee' => 1000,
                'school_year' => self::SY,
            ]);
        }
    }

    private function seedSectionAndClass(): array
    {
        $section = Section::create([
            'grade_level' => 'Grade 1',
            'section_name' => 'St. Agnes',
            'is_active' => true,
        ]);

        $subject = Subject::create([
            'subject_code' => 'MATH1',
            'name' => 'Mathematics',
            'grade_level' => 'Grade 1',
        ]);

        $class = Classes::create([
            'subject_id' => $subject->id,
            'section' => 'St. Agnes',
            'grade_level' => 'Grade 1',
            'school_year' => self::SY,
            'term' => '1st Term',
            'status' => 'active',
            'capacity' => 30,
        ]);

        return [$section, $class];
    }

    private function confirmDownpayment(User $cashier, Student $student, float $amount = 1500.00)
    {
        return $this->actingAs($cashier)->post(route('cashier.payment.downpayment', $student), [
            'payment_plan' => 'installment',
            'amount_paid' => $amount,
        ]);
    }

    // ------------------------------------------------------------------
    // §8 Success Checks
    // ------------------------------------------------------------------

    /** Check 1 — Forwarded file → Cashier confirms with receipt → back to Registrar as Pending with badge. */
    public function test_forwarded_file_confirms_downpayment(): void
    {
        $this->seedFees();
        $cashier = $this->makeCashier();
        $registrar = $this->makeRegistrar();
        $admission = $this->makeForwardedFile($this->makeFamily());

        $this->confirmDownpayment($cashier, $admission->student)
            ->assertSessionHas('success');

        $admission->refresh();
        $this->assertSame('Pending', $admission->status);
        $this->assertNotNull($admission->downpayment_receipt);
        $this->assertStringStartsWith('RCP-', $admission->downpayment_receipt);
        $this->assertNotNull($admission->downpayment_paid_at);

        Mail::assertSent(PaymentConfirmationMail::class);

        // The Registrar Paid filter surfaces the file.
        $response = $this->actingAs($registrar)->get(route('registrar.admissions.index', [
            'ajax' => 1,
            'school_year' => self::SY,
            'status' => 'Paid',
        ]));
        $response->assertOk();
        $this->assertStringContainsString($admission->application_number, $response->json('html'));
    }

    /** Check 2 — Paid Pending → Registrar assigns section + approves → enrollment, institution email, dashboard opens. */
    public function test_paid_file_approves_with_section_and_provisions_email(): void
    {
        $this->seedFees();
        [$section, $class] = $this->seedSectionAndClass();
        $cashier = $this->makeCashier();
        $registrar = $this->makeRegistrar();
        $family = $this->makeFamily();
        $admission = $this->makeForwardedFile($family);

        $this->confirmDownpayment($cashier, $admission->student)
            ->assertSessionHas('success');

        $this->actingAs($registrar)->post(route('registrar.admissions.approve', $admission), [
            'section_id' => $section->id,
            'subject_ids' => [$class->id],
        ])->assertSessionHas('success')
            ->assertRedirect(route('registrar.admissions.index'));

        $admission->refresh();
        $this->assertSame('Approved By Registrar', $admission->status);

        $enrollment = $admission->student->enrollments()->where('status', 'Active')->first();
        $this->assertNotNull($enrollment);
        $this->assertSame($section->id, $enrollment->section_id);
        $this->assertTrue($enrollment->subjects()->where('classes.id', $class->id)->exists());

        $student = $admission->student->refresh();
        $this->assertNotNull($student->student_number);
        $this->assertSame('ana.cruz@agnusdei.edu.ph', $student->user->email);

        Mail::assertSent(AdmissionCredentialsMail::class);

        // Full dashboard opens after approval.
        $this->actingAs($family)->get(route('student.dashboard'))
            ->assertOk();
    }

    /** Check 3 — Before approval, a dashboard visit redirects to the checklist naming the next step. */
    public function test_dashboard_redirects_before_approval(): void
    {
        $this->seedFees();
        $family = $this->makeFamily();
        $admission = $this->makeForwardedFile($family);

        $this->actingAs($family)->get(route('student.dashboard'))
            ->assertRedirect(route('student.admission.status'));
        $this->assertStringContainsString('downpayment', strtolower((string) session('info')));
    }

    /** Check 4 — Double-tap Confirm advances the file exactly once (no double charge). */
    public function test_double_tap_confirms_once(): void
    {
        $this->seedFees();
        $cashier = $this->makeCashier();
        $admission = $this->makeForwardedFile($this->makeFamily());

        $this->confirmDownpayment($cashier, $admission->student)
            ->assertSessionHas('success');
        $this->confirmDownpayment($cashier, $admission->student)
            ->assertSessionHas('success');

        $ledgerId = $admission->student->ledger()->latest()->first()->id;
        $this->assertSame(1, Payment::where('ledger_id', $ledgerId)->where('amount_paid', '>', 0)->count());
        $this->assertSame('Pending', $admission->refresh()->status);
    }

    /** Check 5 — Refunded downpayment before approval → file back to Awaiting Downpayment. */
    public function test_voided_downpayment_returns_to_awaiting(): void
    {
        $this->seedFees();
        $cashier = $this->makeCashier();
        $admission = $this->makeForwardedFile($this->makeFamily());

        $this->confirmDownpayment($cashier, $admission->student)
            ->assertSessionHas('success');

        $receipt = $admission->refresh()->downpayment_receipt;
        $payment = Payment::where('receipt_number', $receipt)->first();
        $this->assertNotNull($payment);

        $this->actingAs($cashier)->post(route('cashier.payments.void', $payment), [
            'reason' => 'Family asked for a reversal at the window.',
        ])->assertSessionHas('success');

        $admission->refresh();
        $this->assertSame('Awaiting Downpayment', $admission->status);
        $this->assertNull($admission->downpayment_receipt);
    }

    /** Check 6 — Name collision → numbered address, family informed in the notice. */
    public function test_name_collision_numbers_the_address(): void
    {
        $this->seedFees();
        [$section, $class] = $this->seedSectionAndClass();
        $cashier = $this->makeCashier();
        $registrar = $this->makeRegistrar();

        // Another student already holds ana.cruz@.
        $other = $this->makeFamily('other.parent@example.com');
        $this->makeStudent($other);
        $other->student->user()->update(['email' => 'ana.cruz@agnusdei.edu.ph']);

        $family = $this->makeFamily('ana.parent@example.com');
        $admission = $this->makeForwardedFile($family);

        $this->confirmDownpayment($cashier, $admission->student)
            ->assertSessionHas('success');

        $this->actingAs($registrar)->post(route('registrar.admissions.approve', $admission), [
            'section_id' => $section->id,
            'subject_ids' => [$class->id],
        ])->assertSessionHas('success');

        $this->assertSame('ana.cruz2@agnusdei.edu.ph', $admission->student->refresh()->user->email);

        Mail::assertSent(AdmissionCredentialsMail::class, function ($mail) use ($admission): bool {
            return $mail->student->id === $admission->student_id;
        });
    }
}
