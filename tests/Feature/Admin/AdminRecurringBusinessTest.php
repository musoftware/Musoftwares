<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\RecurringCost;
use App\Models\RecurringIncome;
use App\Models\RecurringSalary;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class AdminRecurringBusinessTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
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
        $rCost = new RecurringCost();
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
        $this->assertDatabaseMissing('recurring_costs', ['id' => $rCost->id]);
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
}
