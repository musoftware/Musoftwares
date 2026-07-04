<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\CostTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminFinancialOperationsControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    private function createAdmin()
    {
        $admin = User::factory()->create(['onboarding_completed' => true]);
        $admin->assignRole('admin');
        return $admin;
    }

    private function createClient()
    {
        $client = User::factory()->create(['onboarding_completed' => true]);
        $client->assignRole('client');
        return $client;
    }

    public function test_admin_can_access_financial_operations_index()
    {
        $admin = $this->createAdmin();

        $response = $this->actingAs($admin)->get(route('admin.finance.index'));

        $response->assertSuccessful();
    }

    public function test_non_admin_cannot_access_financial_operations_index()
    {
        $client = $this->createClient();

        $response = $this->actingAs($client)->get(route('admin.finance.index'));

        $response->assertStatus(403);
    }

    public function test_admin_can_store_cost_transaction()
    {
        $admin = $this->createAdmin();
        $client = $this->createClient();

        $response = $this->actingAs($admin)->post(route('admin.finance.store'), [
            'title' => 'Test Expense',
            'amount' => 100,
            'type' => 'expense',
            'user_id' => $client->id,
            'currency_id' => 1,
            'status' => 'completed',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('cost_transactions', [
            'reason' => 'Test Expense',
            'amount' => 100,
            'user_id' => $client->id,
        ]);
    }

    public function test_admin_can_delete_cost_transaction()
    {
        $admin = $this->createAdmin();
        
        $cost = CostTransaction::forceCreate([
            'reason' => 'Test',
            'amount' => 100,
            'currency_id' => 1,
            'business_amount' => 100
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.finance.destroy', ['entry' => $cost->id, 'type' => 'expense']));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertSoftDeleted('cost_transactions', [
            'id' => $cost->id,
        ]);
    }
}
