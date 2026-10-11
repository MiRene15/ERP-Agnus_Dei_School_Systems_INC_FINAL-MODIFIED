<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Setting;
use App\Models\Student;
use App\Models\StudentLedger;
use App\Models\User;
use App\Services\GradingCloseService;
use App\Services\HoldService;
use App\Services\SchoolYearService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Spec: 1Dcoument&OtherInfo/specs/enrollment-payment-gating-calendar-holds.md §8.
 */
class CalendarHoldsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::setValue('active_school_year', '2026-2027');
        Setting::setValue('announced_school_year', '');
        Cache::flush();
        Carbon::setTestNow('2026-10-11');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeReturnee(string $grade = 'Grade 1', float $balance = 0): Student
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
            'grade_level' => $grade,
            'section_name' => 'St. Test',
            'is_active' => true,
        ]);
        Enrollment::create([
            'student_id' => $student->id,
            'section_id' => $section->id,
            'school_year' => '2026-2027',
            'status' => 'Active',
        ]);
        StudentLedger::create([
            'student_id' => $student->id,
            'school_year' => '2026-2027',
            'payment_plan' => 'installment',
            'total_assessed' => 10000,
            'discount_applied' => 0,
            'total_paid' => 10000 - $balance,
            'balance' => $balance,
            'clearance_status' => $balance > 0 ? 'Uncleared' : 'Cleared',
        ]);

        return $student->refresh();
    }

    public function test_school_year_period_is_july_to_march(): void
    {
        [$from, $to] = SchoolYearService::monthRange('2027-2028');

        $this->assertSame('2027-07-01', $from->format('Y-m-d'));
        $this->assertSame('2028-03-31', $to->format('Y-m-d'));
        $this->assertStringContainsString('Jul–Mar', SchoolYearService::periodLabel('2027-2028'));
        $this->assertTrue(SchoolYearService::isValidFormat('2027-2028'));
        $this->assertFalse(SchoolYearService::isValidFormat('2027-2029'));
        $this->assertFalse(SchoolYearService::isValidFormat('2027'));
    }

    public function test_grading_close_is_seven_days_after_exam_end(): void
    {
        GradingCloseService::setExamEnd('1st Term', '2026-10-04');

        $this->assertSame('2026-10-11', GradingCloseService::getCloseDate('1st Term')?->format('Y-m-d'));
        $this->assertSame('Closes Oct 11, 2026', GradingCloseService::banner('1st Term'));
        $this->assertFalse(GradingCloseService::isClosed('1st Term'));

        Carbon::setTestNow('2026-10-12');

        $this->assertTrue(GradingCloseService::isClosed('1st Term'));
        $this->assertSame('Closed — request an edit', GradingCloseService::banner('1st Term'));
    }

    public function test_holds_hidden_before_announcement_shown_after(): void
    {
        $student = $this->makeReturnee('Grade 1', 500);

        $this->assertFalse(SchoolYearService::isNewYearAnnounced());
        $this->assertSame([], HoldService::visibleForStudent($student));

        SchoolYearService::announce('2026-2027');

        $this->assertTrue(SchoolYearService::isNewYearAnnounced());
        $holds = HoldService::visibleForStudent($student);
        $this->assertNotEmpty($holds);
        $this->assertStringContainsString('Cashier', $holds[0]['action']);
        $this->assertStringNotContainsString('₱', $holds[0]['reason']);
    }

    public function test_settled_returnee_and_new_applicant_never_see_holds(): void
    {
        SchoolYearService::announce('2026-2027');

        $settled = $this->makeReturnee('Grade 1', 0);
        $this->assertSame([], HoldService::visibleForStudent($settled));

        $user = User::factory()->create(['role_id' => 7, 'email_verified_at' => now()]);
        $applicant = Student::create([
            'user_id' => $user->id,
            'student_number' => null,
            'first_name' => 'New',
            'last_name' => 'Applicant',
            'status' => 'applicant',
        ]);
        $this->assertSame([], HoldService::visibleForStudent($applicant->refresh()));
    }

    public function test_final_graduate_never_sees_reenrollment_hold(): void
    {
        SchoolYearService::announce('2026-2027');

        $graduate = $this->makeReturnee('Grade 12', 500);

        $this->assertSame([], HoldService::visibleForStudent($graduate));
        $this->assertSame('complete', HoldService::bannerForStudent($graduate)['state']);
    }

    public function test_cleared_due_lifts_hold_without_hand_edit(): void
    {
        SchoolYearService::announce('2026-2027');

        $student = $this->makeReturnee('Grade 1', 500);
        $this->assertNotEmpty(HoldService::visibleForStudent($student));

        $student->ledger->update(['balance' => 0]);

        $this->assertSame([], HoldService::visibleForStudent($student->refresh()));
    }

    public function test_clearance_status_appears_nowhere_in_views_or_emails(): void
    {
        $roots = [
            resource_path('views'),
        ];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $contents = (string) file_get_contents($file->getPathname());
                $this->assertStringNotContainsStringIgnoringCase(
                    'clearance',
                    $contents,
                    'Clearance status found in ' . $file->getPathname()
                );
            }
        }
    }
}
