<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\CostTransaction;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Database\Seeders\RolesAndPermissionsSeeder;

class FinancialOperationsTest extends TestCase
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

    public function test_admin_can_view_finance_index(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.finance.index'));
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_finance_index(): void
    {
        $response = $this->actingAs($this->clientUser)->get(route('admin.finance.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_store_finance_entry(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.finance.store'), [
            'title' => 'Server Cost',
            'amount' => 150,
            'type' => 'expense',
            'category_id' => 'server',
            'currency_id' => 1,
            'status' => 'completed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cost_transactions', [
            'reason' => 'Server Cost',
            'amount' => 150,
        ]);
    }

    public function test_admin_can_update_finance_entry(): void
    {
        $cost = new CostTransaction();
        $cost->reason = 'server';
        $cost->amount = 100;
        $cost->currency = 1;
        $cost->status = 'pending';
        $cost->business_amount = 100;
        $cost->save();

        $response = $this->actingAs($this->admin)->put(route('admin.finance.update', $cost->id), [
            'title' => 'Updated Server Cost',
            'amount' => 200,
            'type' => 'expense',
            'currency_id' => 1,
            'status' => 'completed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cost_transactions', [
            'id' => $cost->id,
            'reason' => 'Updated Server Cost',
            'amount' => 200,
            'status' => 'completed'
        ]);
    }

    public function test_admin_can_delete_finance_entry(): void
    {
        $cost = new CostTransaction();
        $cost->reason = 'server';
        $cost->amount = 100;
        $cost->currency = 1;
        $cost->status = 'pending';
        $cost->business_amount = 100;
        $cost->save();

        $response = $this->actingAs($this->admin)->delete(route('admin.finance.destroy', $cost->id) . '?type=expense');

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('cost_transactions', ['id' => $cost->id, 'deleted_at' => null]);
    }

    public function test_admin_can_mark_finance_entry_as_paid(): void
    {
        $cost = new CostTransaction();
        $cost->reason = 'server';
        $cost->amount = 100;
        $cost->currency = 1;
        $cost->status = 'pending';
        $cost->business_amount = 100;
        $cost->save();

        $response = $this->actingAs($this->admin)->post(route('admin.finance.mark-paid', $cost->id) . '?type=expense');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cost_transactions', [
            'id' => $cost->id,
            'status' => 'completed'
        ]);
    }
}
