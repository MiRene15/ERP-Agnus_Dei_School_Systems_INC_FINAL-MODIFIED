<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admission;
use App\Models\Requirement;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Spec: 1Dcoument&OtherInfo/specs/enrollment-payment-gating-review-forward.md §8 — Success Checks.
 */
class AdmissionReviewForwardTest extends TestCase
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

    private function makeStudent(User $user, string $firstName = 'Ana', string $lastName = 'Cruz', ?string $gender = 'Female'): Student
    {
        return Student::create([
            'user_id' => $user->id,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => $gender,
            'date_of_birth' => '2015-05-10',
            'personal_email' => strtolower($firstName) . '.' . strtolower($lastName) . '@example.com',
        ]);
    }

    private function makeRegistrar(): User
    {
        return User::factory()->create(['role_id' => 2]);
    }

    private function makeAdmission(Student $student, string $status = 'Draft'): Admission
    {
        return Admission::create([
            'student_id' => $student->id,
            'application_type' => 'New',
            'grade_level' => 'Grade 1',
            'strand' => null,
            'school_year' => self::SY,
            'status' => $status,
        ]);
    }

    private function uploadDocument(User $user, Admission $admission, string $documentType): void
    {
        $file = UploadedFile::fake()->create(strtolower(str_replace(' ', '_', $documentType)) . '.pdf', 100);

        $this->actingAs($user)->post(route('student.admission.requirements'), [
            'documents' => [$documentType => $file],
        ]);
    }

    private function uploadAllRequired(User $user, Admission $admission): void
    {
        foreach (self::REQUIRED_DOCS as $doc) {
            $this->uploadDocument($user, $admission, $doc);
        }
    }

    private function submitApplication(User $user, ?string $studentEmail = null, bool $confirmed = false)
    {
        return $this->actingAs($user)->post(route('student.admission.store'), [
            'application_type' => 'New',
            'grade_level' => 'Grade 1',
            'school_year' => self::SY,
            'first_name' => 'Ana',
            'last_name' => 'Cruz',
            'gender' => 'Female',
            'date_of_birth' => '2015-05-10',
            'place_of_birth' => 'Manila',
            'citizenship' => 'Filipino',
            'contact_number' => '09171234567',
            'permanent_address' => '123 Test St',
            'emergency_contact_name' => 'Juan Cruz',
            'emergency_contact_number' => '09171234567',
            'emergency_contact_relationship' => 'Father',
            'previous_school' => 'Test Elementary',
            'student_email' => $studentEmail ?? 'ana.student@example.com',
            'student_email_confirmed' => $confirmed ? '1' : null,
            '_idempotency_key' => 'test-key-' . uniqid(),
        ]);
    }

    /** A Pending file with every required document uploaded and verified. */
    private function makeCompletePendingFile(User $user): Admission
    {
        $student = $this->makeStudent($user);
        $admission = $this->makeAdmission($student, 'Pending');

        foreach (self::REQUIRED_DOCS as $doc) {
            Requirement::create([
                'admission_id' => $admission->id,
                'document_type' => $doc,
                'status' => 'Verified',
            ]);
        }

        return $admission;
    }

    // ------------------------------------------------------------------
    // §8 Success Checks
    // ------------------------------------------------------------------

    /** Check 1 — Family submits with uploads + student email → status shows Under Review. */
    public function test_family_submits_with_uploads_and_student_email(): void
    {
        $user = $this->makeFamily();
        $student = $this->makeStudent($user);
        $admission = $this->makeAdmission($student);

        $this->uploadAllRequired($user, $admission);

        $this->submitApplication($user)
            ->assertSessionHas('success')
            ->assertRedirect(route('student.admission.status') . '#upload-requirements');

        $admission->refresh();
        $this->assertSame('Pending', $admission->status);

        // The student's own email is saved on the student record.
        $student->refresh();
        $this->assertSame('ana.student@example.com', $student->personal_email);

        // The family sees "Under Review" on the status page.
        $response = $this->actingAs($user)->get(route('student.admission.status', ['ajax' => 1]));
        $response->assertOk();
        $this->assertStringContainsString('Under Review', $response->json('html'));
    }

    /** Check 2 — Submit with a missing required file → blocked naming the file. */
    public function test_submit_is_blocked_naming_the_missing_required_file(): void
    {
        $user = $this->makeFamily();
        $student = $this->makeStudent($user);
        $admission = $this->makeAdmission($student);

        // Upload two of the three required documents, then try to submit.
        $this->uploadDocument($user, $admission, 'PSA Birth Certificate');
        $this->uploadDocument($user, $admission, 'Form 138 (Report Card)');

        $this->submitApplication($user)->assertSessionHas('error');

        $error = session('error');
        $this->assertStringContainsString('Good Moral Certificate', $error);
        $this->assertStringNotContainsString('PSA Birth Certificate', $error);
        $this->assertStringNotContainsString('Form 138', $error);

        // Nothing was submitted — the draft is untouched.
        $admission->refresh();
        $this->assertSame('Draft', $admission->status);

        // Once the named document is uploaded, the same submit succeeds.
        $this->uploadDocument($user, $admission, 'Good Moral Certificate');
        $this->submitApplication($user)->assertSessionHas('success');
        $this->assertSame('Pending', $admission->refresh()->status);
    }

    /** Check 3 — Registrar Verifies All → Forwards → Awaiting Downpayment, reaches the queue. */
    public function test_registrar_forwards_complete_file_to_cashier_queue(): void
    {
        $registrar = $this->makeRegistrar();
        $admission = $this->makeCompletePendingFile($this->makeFamily());

        $this->actingAs($registrar)
            ->post(route('registrar.admissions.verify-all', $admission))
            ->assertSessionHas('success');

        $this->actingAs($registrar)
            ->post(route('registrar.admissions.forward', $admission))
            ->assertSessionHas('success')
            ->assertRedirect(route('registrar.admissions.index'));

        $admission->refresh();
        $this->assertSame('Awaiting Downpayment', $admission->status);
        $this->assertNotNull($admission->review_note);

        // The file is visible in the queue under the Awaiting Downpayment filter.
        $response = $this->actingAs($registrar)->get(route('registrar.admissions.index', [
            'ajax' => 1,
            'school_year' => self::SY,
            'status' => 'Awaiting Downpayment',
        ]));
        $response->assertOk();
        $this->assertStringContainsString($admission->application_number, $response->json('html'));
    }

    /** Check 4 — Incomplete file → returns naming missing files; re-upload keeps prior verified files. */
    public function test_incomplete_file_returns_and_resubmission_keeps_verified_files(): void
    {
        $registrar = $this->makeRegistrar();
        $user = $this->makeFamily();
        $student = $this->makeStudent($user);
        $admission = $this->makeAdmission($student, 'Pending');

        // One verified, one under review, one not uploaded at all.
        Requirement::create(['admission_id' => $admission->id, 'document_type' => 'PSA Birth Certificate', 'status' => 'Verified']);
        Requirement::create(['admission_id' => $admission->id, 'document_type' => 'Form 138 (Report Card)', 'status' => 'Under Review']);

        $this->actingAs($registrar)
            ->post(route('registrar.admissions.return', $admission))
            ->assertSessionHas('success')
            ->assertRedirect(route('registrar.admissions.index'));

        $admission->refresh();
        $this->assertSame('Returned', $admission->status);
        $this->assertStringContainsString('Good Moral Certificate', $admission->review_note);
        $this->assertStringContainsString('Form 138 (Report Card)', $admission->review_note);

        // The verified upload is kept.
        $this->assertSame(
            'Verified',
            $admission->requirements()->where('document_type', 'PSA Birth Certificate')->first()->status
        );

        // The family sees the named missing list.
        $response = $this->actingAs($user)->get(route('student.admission.status', ['ajax' => 1]));
        $response->assertOk();
        $html = $response->json('html');
        $this->assertStringContainsString('Returned', $html);
        $this->assertStringContainsString('Good Moral Certificate', $html);

        // The family supplies only the missing document; the file returns to
        // review automatically and the previously verified file is untouched.
        $this->uploadDocument($user, $admission, 'Good Moral Certificate');

        $admission->refresh();
        $this->assertSame('Pending', $admission->status);
        $this->assertNull($admission->review_note);
        $this->assertSame('Verified', $admission->requirements()->where('document_type', 'PSA Birth Certificate')->first()->status);
        $this->assertSame('Under Review', $admission->requirements()->where('document_type', 'Good Moral Certificate')->first()->status);
    }

    /** Check 5 — Forward with an unverified doc or missing gender → blocked with the reason. */
    public function test_forward_is_blocked_with_unverified_doc_or_missing_gender(): void
    {
        $registrar = $this->makeRegistrar();

        // Case A — one document still Under Review.
        $user = $this->makeFamily();
        $student = $this->makeStudent($user);
        $withUnverified = $this->makeAdmission($student, 'Pending');
        Requirement::create(['admission_id' => $withUnverified->id, 'document_type' => 'PSA Birth Certificate', 'status' => 'Verified']);
        Requirement::create(['admission_id' => $withUnverified->id, 'document_type' => 'Form 138 (Report Card)', 'status' => 'Verified']);
        Requirement::create(['admission_id' => $withUnverified->id, 'document_type' => 'Good Moral Certificate', 'status' => 'Under Review']);

        $this->actingAs($registrar)
            ->post(route('registrar.admissions.forward', $withUnverified))
            ->assertSessionHas('error');

        $this->assertStringContainsString('still Under Review', session('error'));
        $this->assertSame('Pending', $withUnverified->refresh()->status);

        // Case B — gender missing.
        $user2 = $this->makeFamily('parent2@example.com');
        $student2 = $this->makeStudent($user2, gender: null);
        $withoutGender = $this->makeCompletePendingFile($user2);

        $this->actingAs($registrar)
            ->post(route('registrar.admissions.forward', $withoutGender))
            ->assertSessionHas('error');

        $this->assertStringContainsString('Gender is still missing', session('error'));
        $this->assertSame('Pending', $withoutGender->refresh()->status);
    }

    /** Check 6 — Duplicate applications → one survivor, uploads consolidated. */
    public function test_duplicate_applications_consolidate_to_one_survivor(): void
    {
        $user = $this->makeFamily();
        $student = $this->makeStudent($user);

        // Two live applications for the same child + school year (race/legacy).
        $first = $this->makeAdmission($student, 'Pending');
        $first->created_at = now()->subHour();
        $first->save();

        $second = $this->makeAdmission($student, 'Pending');

        // The earliest has one document; the latest has all three (so the
        // submit gate passes against it).
        Requirement::create(['admission_id' => $first->id, 'document_type' => 'PSA Birth Certificate', 'status' => 'Under Review']);
        foreach (self::REQUIRED_DOCS as $doc) {
            Requirement::create(['admission_id' => $second->id, 'document_type' => $doc, 'status' => 'Under Review']);
        }

        $this->submitApplication($user)->assertSessionHas('success');

        // One survivor remains, with the uploads consolidated onto it.
        $this->assertSame(1, $student->admissions()->where('status', 'Pending')->count());

        $survivor = $student->admissions()->where('status', 'Pending')->first();
        $types = $survivor->requirements()->pluck('document_type')->all();
        $this->assertContains('PSA Birth Certificate', $types);
        $this->assertContains('Form 138 (Report Card)', $types);
        $this->assertContains('Good Moral Certificate', $types);
    }

    /** Check 7 — Sibling pair → two records, distinct student emails, one shared parent contact. */
    public function test_siblings_get_separate_applications_with_distinct_emails(): void
    {
        // Two accounts (one per child) sharing the parent's contact number.
        $parentA = $this->makeFamily('parent.a@example.com');
        $parentB = $this->makeFamily('parent.b@example.com');
        $this->assertSame('09171234567', '09171234567'); // shared contact asserted via both applications below

        $sibling1 = $this->makeStudent($parentA, 'Ana', 'Cruz');
        $sibling2 = $this->makeStudent($parentB, 'Ben', 'Cruz');

        $this->assertNotSame($sibling1->personal_email, $sibling2->personal_email);

        $registrar = $this->makeRegistrar();

        foreach ([[$parentA, $sibling1], [$parentB, $sibling2]] as [$parent, $sibling]) {
            $admission = $this->makeAdmission($sibling);
            $this->uploadAllRequired($parent, $admission);
            $this->submitApplication($parent, $sibling->personal_email)->assertSessionHas('success');
        }

        // Two separate applications, both Pending, both with the shared
        // parent contact number on file.
        $admissions = Admission::whereIn('student_id', [$sibling1->id, $sibling2->id])
            ->where('status', 'Pending')
            ->get();

        $this->assertCount(2, $admissions);
        $this->assertSame('09171234567', $admissions[0]->student->contact_number);
        $this->assertSame('09171234567', $admissions[1]->student->contact_number);

        // No duplicate per child.
        $this->assertSame(1, $sibling1->admissions()->where('status', 'Pending')->count());
        $this->assertSame(1, $sibling2->admissions()->where('status', 'Pending')->count());
    }

    // ------------------------------------------------------------------
    // §6 rule — warn-don't-block on a student email identical to the account
    // ------------------------------------------------------------------

    public function test_student_email_identical_to_account_email_requires_confirmation(): void
    {
        $user = $this->makeFamily('shared.address@example.com');
        $student = $this->makeStudent($user);
        $admission = $this->makeAdmission($student);
        $this->uploadAllRequired($user, $admission);

        // Same address, no confirmation → blocked with the plain message.
        $this->submitApplication($user, 'shared.address@example.com')
            ->assertSessionHas('error');
        $this->assertStringContainsString('same as your account email', session('error'));
        $this->assertSame('Draft', $admission->refresh()->status);

        // Same address, family confirms → accepted.
        $this->submitApplication($user, 'shared.address@example.com', confirmed: true)
            ->assertSessionHas('success');
        $this->assertSame('Pending', $admission->refresh()->status);
        $this->assertSame('shared.address@example.com', $student->refresh()->personal_email);
    }
}
