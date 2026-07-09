<?php

namespace Tests\Feature\Admin;

use App\Models\AdminSettings;
use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\RecurringCost;
use App\Models\User;
use Database\Seeders\CurrenciesSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminCostsFilterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(CurrenciesSeeder::class);

        $this->currency = Currency::first();
        AdminSettings::SetValue('business_currency', (string) $this->currency->id);

        $this->admin = User::factory()->create([
            'onboarding_completed' => true,
            'currency_id' => $this->currency->id,
        ]);
        $this->admin->assignRole('admin');
    }

    private function makeCost(string $reason, float $amount, $createdAt): CostTransaction
    {
        $c = new CostTransaction();
        $c->reason = $reason;
        $c->amount = $amount;
        $c->currency_id = $this->currency->id;
        $c->created_at = $createdAt;
        $c->updated_at = $createdAt;
        $c->save();

        return $c;
    }

    public function test_costs_page_exposes_year_and_month_filters_for_ui(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.costs.index'));

        $response->assertStatus(200);

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Business/Costs')
            ->has('filters', fn (Assert $filters) => $filters
                ->has('year')
                ->has('month')
                ->has('available_years')
                ->has('available_months')
                ->etc()
            )
        );
    }

    public function test_costs_page_only_returns_transactions_for_the_requested_year_and_month(): void
    {
        $this->makeCost('Hosting', 100.00, now());
        $this->makeCost('Hosting', 200.00, now()->subMonth());
        $this->makeCost('Hosting', 300.00, now()->subYear());

        $response = $this->actingAs($this->admin)->get(route('admin.costs.index', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertStatus(200);

        $props = $response->original->getData()['page']['props'];
        $amounts = collect($props['entries']['data'] ?? [])->pluck('amount')->all();

        $this->assertContains(100.00, $amounts);
        $this->assertNotContains(200.00, $amounts);
        $this->assertNotContains(300.00, $amounts);
    }

    public function test_costs_page_returns_transactions_for_the_explicitly_requested_period(): void
    {
        $this->makeCost('Hosting', 500.00, now()->subMonths(2));

        $target = now()->subMonths(2);
        $response = $this->actingAs($this->admin)->get(route('admin.costs.index', [
            'year' => $target->year,
            'month' => $target->month,
        ]));

        $response->assertStatus(200);

        $props = $response->original->getData()['page']['props'];
        $amounts = collect($props['entries']['data'] ?? [])->pluck('amount')->all();

        $this->assertContains(500.00, $amounts);
    }

    public function test_recurring_cost_generated_transactions_appear_on_costs_page_with_recurring_flag(): void
    {
        $rc = new RecurringCost();
        $rc->title = 'Server Sub';
        $rc->amount = 50;
        $rc->currency_id = $this->currency->id;
        $rc->start_date = now()->toDateString();
        $rc->current_date = now()->toDateString();
        $rc->recurring = 'month';
        $rc->recurring_times = 1;
        $rc->recurring_times_month = now()->day;
        $rc->reason = 'server';
        $rc->save();

        $cost = $this->makeCost('server', 50.00, now());
        $rc->transactions()->attach($cost->id, ['unique_id' => $rc->id . '-' . now()->toDateString()]);

        $response = $this->actingAs($this->admin)->get(route('admin.costs.index', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertStatus(200);

        $props = $response->original->getData()['page']['props'];
        $entry = collect($props['entries']['data'] ?? [])->firstWhere('id', $cost->id);

        $this->assertNotNull($entry, 'Recurring cost transaction should appear on costs page.');
        $this->assertTrue($entry['is_recurring']);
        $this->assertSame('Server Sub', $entry['title']);
    }

    public function test_costs_page_available_years_include_all_years_with_transactions(): void
    {
        $this->makeCost('Hosting', 100.00, now());
        $this->makeCost('Hosting', 200.00, now()->subYears(2));

        $response = $this->actingAs($this->admin)->get(route('admin.costs.index'));

        $response->assertStatus(200);

        $props = $response->original->getData()['page']['props'];
        $availableYears = $props['filters']['available_years'] ?? [];

        $this->assertContains(now()->year, $availableYears);
        $this->assertContains(now()->subYears(2)->year, $availableYears);
    }
}