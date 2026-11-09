<?php

namespace Tests\Feature\Admin;

use App\Models\AdminSettings;
use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AdminFinanceMenuTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CurrenciesSeeder::class);
        $this->currency = Currency::first();
        AdminSettings::SetValue('business_currency', (string) $this->currency->id);
        $this->admin = User::factory()->create(['onboarding_completed' => true, 'currency_id' => $this->currency->id]);
        $this->admin->assignRole('admin');
    }

    private function cost(string $reason, float $amount, array $extra = []): CostTransaction
    {
        $cost = new CostTransaction;
        $cost->forceFill($extra + [
            'reason' => $reason,
            'amount' => $amount,
            'business_amount' => $amount,
            'currency_id' => $this->currency->id,
            'created_at' => now(),
        ])->save();

        return $cost;
    }

    public function test_the_old_costs_and_income_tabs_open_the_full_pages_and_keep_their_filters(): void
    {
        $this->actingAs($this->admin);

        $this->get('/admin/finance?tab=expenses&search=rent')->assertRedirect(route('admin.costs.index', ['search' => 'rent']));
        $this->get('/admin/finance?tab=income')->assertRedirect(route('admin.income.index'));
    }

    public function test_the_overview_opens_on_project_profitability_and_an_unknown_tab_is_ignored(): void
    {
        $this->actingAs($this->admin);

        foreach (['/admin/finance', '/admin/finance?tab=nothing'] as $url) {
            $this->get($url)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('currentTab', 'projects'));
        }
    }

    public function test_the_payroll_budgets_and_calendar_tabs_still_open(): void
    {
        $this->actingAs($this->admin);

        foreach (['salaries', 'budgets', 'calendar'] as $tab) {
            $this->get('/admin/finance?tab='.$tab)->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->where('currentTab', $tab));
        }
    }

    public function test_salaries_are_listed_and_counted_on_the_costs_page(): void
    {
        $this->cost('salary', 8000);
        $this->cost('Electric', 4300);

        $this->actingAs($this->admin)->get('/admin/business/costs')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 2)
            ->where('stats.total_monthly_costs', fn ($total) => abs((float) $total) === 12300.0));
    }

    public function test_the_costs_page_can_show_salaries_alone_even_when_they_have_no_category(): void
    {
        $this->cost('salary', 8000);
        $this->cost('Electric', 4300);

        $this->actingAs($this->admin)->get('/admin/business/costs?category=salary')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->has('entries.data', 1)
            ->where('entries.data.0.reason', 'salary'));
    }

    public function test_the_costs_page_filters_by_status(): void
    {
        $this->cost('Rent', 100, ['status' => 'pending']);
        $this->cost('Water', 50, ['status' => 'completed']);
        $this->actingAs($this->admin);

        $this->get('/admin/business/costs?status=pending')->assertInertia(fn (AssertableInertia $page) => $page->has('entries.data', 1)->where('entries.data.0.reason', 'Rent'));
        $this->get('/admin/business/costs?status=completed')->assertInertia(fn (AssertableInertia $page) => $page->has('entries.data', 1)->where('entries.data.0.reason', 'Water'));
    }

    public function test_the_income_page_filters_by_status(): void
    {
        foreach (['pending', 'completed'] as $status) {
            $transaction = new Transaction;
            $transaction->forceFill(['user_id' => $this->admin->id, 'type' => 'received', 'reason' => $status, 'amount' => 10, 'business_amount' => 10, 'currency_id' => $this->currency->id, 'status' => $status, 'created_at' => now()])->save();
        }

        $this->actingAs($this->admin)->get('/admin/business/income?status=pending')->assertInertia(fn (AssertableInertia $page) => $page->has('entries.data', 1)->where('entries.data.0.reason', 'pending'));
    }

    public function test_the_costs_page_accepts_a_date_range_from_the_reports_page(): void
    {
        $old = now('Africa/Cairo')->subDays(10);
        $this->cost('Old', 10, ['created_at' => $old->copy()->setTimezone('UTC')]);
        $this->cost('New', 20);
        $day = $old->toDateString();

        $this->actingAs($this->admin)->get("/admin/business/costs?from={$day}&to={$day}")->assertInertia(fn (AssertableInertia $page) => $page->has('entries.data', 1)->where('entries.data.0.reason', 'Old'));
    }
}
