<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentLedger;
use App\Models\User;
use App\Services\CashierProjectionService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CashierProjectionTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        $this->cashier = User::factory()->create(['role_id' => 3]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function makeLedger(float $totalAssessed, float $totalPaid, float $balance): StudentLedger
    {
        $student = Student::create([
            'user_id' => $this->cashier->id,
            'student_number' => 'S-' . uniqid(),
            'first_name' => 'Test',
            'last_name' => 'Student',
        ]);

        return StudentLedger::create([
            'student_id' => $student->id,
            'payment_plan' => 'installment',
            'total_assessed' => $totalAssessed,
            'discount_applied' => 0,
            'total_paid' => $totalPaid,
            'balance' => $balance,
        ]);
    }

    private function pay(StudentLedger $ledger, float $amount, string $date): void
    {
        Payment::create([
            'ledger_id' => $ledger->id,
            'cashier_id' => $this->cashier->id,
            'amount_paid' => $amount,
            'receipt_number' => 'RCP-' . uniqid(),
            'payment_date' => $date,
        ]);
    }

    private function summary(string $from, string $to): array
    {
        return app(CashierProjectionService::class)->summaryForPeriod(
            Carbon::parse($from),
            Carbon::parse($to)
        );
    }

    public function test_dashboard_shows_collectibles_and_collections(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(10000.00, 5000.00, 5000.00);
        $this->pay($ledger, 1000.00, '2026-09-10');

        $response = $this->actingAs($this->cashier)->get(route('cashier.dashboard'));

        $response->assertOk();
        $response->assertSee('Collectibles');
        $response->assertSee('Collections');
        $response->assertDontSee('Estimate');
    }

    public function test_dashboard_defaults_to_month_to_date(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(20000.00, 8000.00, 12000.00);
        $this->pay($ledger, 5000.00, '2026-08-05');
        $this->pay($ledger, 300.00, '2026-09-05');

        $response = $this->actingAs($this->cashier)->get(route('cashier.dashboard'));

        $response->assertOk();
        $this->assertSame('2026-09-01', $response->viewData('dateFrom'));
        $this->assertSame('2026-09-15', $response->viewData('dateTo'));
        $this->assertSame('Month to Date', $response->viewData('periodLabel'));
        $this->assertSame(300.0, $response->viewData('summary')['collections']);
    }

    public function test_dashboard_collections_follow_the_date_filter(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(20000.00, 8000.00, 12000.00);
        $this->pay($ledger, 5000.00, '2026-08-05');
        $this->pay($ledger, 300.00, '2026-09-05');

        $response = $this->actingAs($this->cashier)->get(route('cashier.dashboard', [
            'date_from' => '2026-08-01',
            'date_to' => '2026-08-31',
        ]));

        $response->assertOk();
        $this->assertSame(5000.0, $response->viewData('summary')['collections']);
    }

    public function test_collectibles_follow_the_period_filter(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(10000.00, 2000.00, 8000.00);
        $this->pay($ledger, 2000.00, '2026-08-10');

        // As of end of August the 2000 receipt is not booked yet -> full 10000 owed.
        $august = $this->summary('2026-08-01', '2026-08-31');
        $this->assertSame(10000.0, $august['receivables']);

        // As of end of September the receipt counts -> 8000 owed.
        $september = $this->summary('2026-09-01', '2026-09-30');
        $this->assertSame(8000.0, $september['receivables']);
    }

    public function test_derived_receivables_match_the_stored_balance_for_the_current_period(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(10000.00, 2000.00, 8000.00);
        $this->pay($ledger, 2000.00, '2026-09-10');

        $derived = $this->summary('2026-09-01', '2026-09-30')['receivables'];

        $stored = (float) StudentLedger::where('balance', '>', 0)->sum('balance');

        $this->assertSame($stored, $derived);
    }

    public function test_ledgers_created_after_the_period_are_excluded(): void
    {
        Carbon::setTestNow('2026-09-15');

        $this->makeLedger(10000.00, 2000.00, 8000.00);

        $january = $this->summary('2026-01-01', '2026-01-31');

        $this->assertSame(0.0, $january['receivables']);
    }

    public function test_period_with_no_collections_reports_zero_collections(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(10000.00, 1000.00, 9000.00);

        $summary = $this->summary('2026-09-01', '2026-09-30');

        $this->assertSame(0.0, $summary['collections']);
        $this->assertSame(9000.0, $summary['receivables']);

        unset($ledger);
    }

    public function test_all_settled_reports_zero_for_both_figures(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(5000.00, 5000.00, 0);
        $this->pay($ledger, 500.00, '2026-09-10');

        $summary = $this->summary('2026-09-01', '2026-09-30');

        $this->assertSame(0.0, $summary['receivables']);
        $this->assertSame(500.0, $summary['collections']);
    }

    public function test_summary_exposes_no_estimate_keys(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(10000.00, 2000.00, 8000.00);
        $this->pay($ledger, 2000.00, '2026-09-10');

        $summary = $this->summary('2026-09-01', '2026-09-30');

        $this->assertArrayNotHasKey('estimate', $summary);
        $this->assertArrayNotHasKey('hasCollections', $summary);
        $this->assertArrayNotHasKey('isFinalized', $summary);
        $this->assertArrayNotHasKey('periodHasStarted', $summary);
    }

    public function test_wide_date_ranges_are_clamped_to_ten_years(): void
    {
        Carbon::setTestNow('2026-09-15');

        $service = app(CashierProjectionService::class);

        [$from, $to] = $service->clampRange(
            Carbon::parse('1987-01-01'),
            Carbon::parse('2026-09-15')
        );

        $this->assertSame($to->copy()->subYears(10)->toDateString(), $from->toDateString());
        $this->assertSame('2026-09-15', $to->toDateString());
    }

    public function test_monthly_series_keeps_query_count_constant_for_wide_ranges(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(20000.00, 8000.00, 12000.00);
        $this->pay($ledger, 3000.00, '2026-07-10');

        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        $series = app(CashierProjectionService::class)->monthlySeriesForRange(
            Carbon::parse('2000-01-01'),
            Carbon::parse('2026-09-15')
        );

        $this->assertLessThanOrEqual(3, $queries);
        $this->assertCount(count($series['labels']), $series['collected']);
    }

    public function test_projections_page_shows_monthly_lines(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(20000.00, 8000.00, 12000.00);
        $this->pay($ledger, 3000.00, '2026-07-10');

        $response = $this->actingAs($this->cashier)->get(route('cashier.projections'));

        $response->assertOk();
        $response->assertSee('Collected');
        $response->assertSee('projectionChart');

        $outstandingSeries = $response->viewData('outstanding');
        $this->assertIsArray($outstandingSeries);
        $this->assertCount(12, $outstandingSeries);

        $summary = $response->viewData('summary');
        $this->assertSame(12000.0, $summary['receivables']);
        $this->assertSame(3000.0, $summary['collections']);
    }

    public function test_projections_defaults_to_the_school_year_range(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(20000.00, 8000.00, 12000.00);
        $this->pay($ledger, 3000.00, '2026-07-10');

        $response = $this->actingAs($this->cashier)->get(route('cashier.projections', [
            'school_year' => '2026-2027',
        ]));

        $response->assertOk();
        $this->assertSame('2026-06-01', $response->viewData('dateFrom'));
        $this->assertSame('2027-05-31', $response->viewData('dateTo'));
        $this->assertCount(12, $response->viewData('labels'));
        $this->assertFalse($response->viewData('usingCustomDates'));
    }

    public function test_date_filter_is_prefilled_with_the_default_period(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(20000.00, 8000.00, 12000.00);

        $response = $this->actingAs($this->cashier)->get(route('cashier.projections', [
            'school_year' => '2026-2027',
        ]));

        $response->assertOk();
        $response->assertSee('value="2026-06-01"', false);
        $response->assertSee('value="2027-05-31"', false);
    }

    public function test_custom_dates_override_the_school_year(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(20000.00, 8000.00, 12000.00);
        $this->pay($ledger, 3000.00, '2026-07-10');

        $response = $this->actingAs($this->cashier)->get(route('cashier.projections', [
            'school_year' => '2026-2027',
            'date_from' => '2026-07-01',
            'date_to' => '2026-09-30',
        ]));

        $response->assertOk();
        $this->assertSame('2026-07-01', $response->viewData('dateFrom'));
        $this->assertSame('2026-09-30', $response->viewData('dateTo'));
        $this->assertSame(['Jul', 'Aug', 'Sep'], $response->viewData('labels'));
        $this->assertTrue($response->viewData('usingCustomDates'));
    }

    public function test_school_year_selection_clears_custom_dates(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(20000.00, 8000.00, 12000.00);

        $response = $this->actingAs($this->cashier)->get(route('cashier.projections', [
            'school_year' => '2025-2026',
        ]));

        $response->assertOk();
        $this->assertSame('2025-06-01', $response->viewData('dateFrom'));
        $this->assertSame('2026-05-31', $response->viewData('dateTo'));
        $this->assertFalse($response->viewData('usingCustomDates'));
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        Carbon::setTestNow('2026-09-15');

        $ledger = $this->makeLedger(10000.00, 0, 10000.00);

        $response = $this->actingAs($this->cashier)->get(route('cashier.projections', [
            'date_from' => '2026-09-30',
            'date_to' => '2026-09-01',
        ]));

        $response->assertSessionHasErrors('date_to');
    }

    public function test_projections_page_shows_no_collections_message(): void
    {
        Carbon::setTestNow('2026-09-15');

        $this->makeLedger(10000.00, 0, 10000.00);

        $response = $this->actingAs($this->cashier)->get(route('cashier.projections'));

        $response->assertOk();
        $response->assertSee('No Collections Yet');
        $response->assertDontSee('projectionChart');
    }

    public function test_monthly_series_spans_june_to_may(): void
    {
        $series = app(CashierProjectionService::class)->monthlySeries('2026-2027');

        $this->assertCount(12, $series['labels']);
        $this->assertSame('Jun', $series['labels'][0]);
        $this->assertSame('May', $series['labels'][11]);
    }

    public function test_non_cashier_is_denied_projections(): void
    {
        $teacher = User::factory()->create(['role_id' => 4]);

        $response = $this->actingAs($teacher)->get(route('cashier.projections'));

        $response->assertForbidden();
    }

    public function test_non_cashier_sidebar_has_no_projections_link(): void
    {
        $teacher = User::factory()->create(['role_id' => 4]);

        $response = $this->actingAs($teacher)->get(route('teacher.dashboard'));

        $response->assertOk();
        $response->assertDontSee(route('cashier.projections'), false);
    }
}