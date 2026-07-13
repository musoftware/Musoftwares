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
use Tests\TestCase;

class AdminRecurringBusinessTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $clientUser;

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

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');
    }

    // Costs
    public function test_admin_can_view_recurring_costs_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.recurring_costs.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_store_recurring_cost(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.recurring_costs.store'), [
            'title' => 'Server Sub',
            'amount' => 50,
            'currency' => 1,
            'reason_choice' => 'custom',
            'custom_reason' => 'server',
            'start_date' => now()->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => [1],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('recurring_costs', ['title' => 'Server Sub']);
    }

    public function test_admin_can_delete_recurring_cost(): void
    {
        $rCost = new RecurringCost;
        $rCost->title = 'Server Sub';
        $rCost->amount = 50;
        $rCost->currency_id = 1;
        $rCost->start_date = now()->toDateString();
        $rCost->current_date = now()->toDateString();
        $rCost->recurring = 'month';
        $rCost->recurring_times = 1;
        $rCost->reason = 'server';
        $rCost->save();

        $response = $this->actingAs($this->admin)->delete(route('admin.recurring_costs.delete', $rCost->id));
        $response->assertRedirect();
        $this->assertSoftDeleted('recurring_costs', ['id' => $rCost->id]);
    }

    // Income
    public function test_admin_can_view_recurring_income_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.recurring_income.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_store_recurring_income(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.recurring_income.store'), [
            'title' => 'Retainer',
            'amount' => 500,
            'currency' => 1,
            'reason_choice' => 'custom',
            'custom_reason' => 'retainer',
            'start_date' => now()->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => [1],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('recurring_incomes', ['title' => 'Retainer']);
    }

    // Salaries
    public function test_admin_can_view_recurring_salaries_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.recurring_salaries.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_store_recurring_salary(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.recurring_salaries.store'), [
            'user_id' => $this->clientUser->id,
            'title' => 'Monthly Salary',
            'amount' => 1000,
            'currency' => 1,
            'start_date' => now()->toDateString(),
            'recurring' => 'month',
            'recurring_times' => 1,
            'recurring_times_month' => [1],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('recurring_salaries', ['title' => 'Monthly Salary', 'user_id' => $this->clientUser->id]);
    }

    public function test_recurring_cost_view_exposes_cost_transaction_id_for_each_transaction(): void
    {
        $rc = new RecurringCost;
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

        $cost = new CostTransaction;
        $cost->reason = 'server';
        $cost->amount = 50.00;
        $cost->currency_id = $this->currency->id;
        $cost->created_at = now();
        $cost->updated_at = now();
        $cost->save();

        $rc->transactions()->attach($cost->id, [
            'unique_id' => $rc->id.'-'.now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.recurring_costs.view', $rc->id));

        $response->assertStatus(200);

        $props = $response->original->getData()['page']['props'];
        $transactions = $props['transactions'] ?? [];

        $this->assertCount(1, $transactions, 'Recurring cost view should expose generated transactions.');
        $this->assertArrayHasKey('id', $transactions[0], 'Each transaction must include id.');
        $this->assertSame($cost->id, $transactions[0]['id']);
    }
}
