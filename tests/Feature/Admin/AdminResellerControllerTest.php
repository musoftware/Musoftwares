<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Modules\Tools\Models\ToolReseller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminResellerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create(['onboarding_completed' => true]);
        $this->clientUser->assignRole('client');

        $this->currency = \App\Models\Currency::first();
        if (!$this->currency) {
            $this->currency = \App\Models\Currency::create([
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 1,
            ]);
        }
    }

    public function test_admin_can_view_resellers_index()
    {
        $response = $this->actingAs($this->admin)->get('/admin/resellers');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_resellers_index()
    {
        $response = $this->actingAs($this->clientUser)->get('/admin/resellers');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_create_page()
    {
        $response = $this->actingAs($this->admin)->get('/admin/resellers/create');
        $response->assertStatus(200);
    }

    public function test_admin_can_store_reseller()
    {
        $response = $this->actingAs($this->admin)->post('/admin/resellers', [
            'user_id' => $this->clientUser->id,
            'name' => 'Reseller Name',
            'status' => 'active',
            'credit_balance' => 100,
            'currency_id' => $this->currency->id
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tool_resellers', [
            'user_id' => $this->clientUser->id,
            'name' => 'Reseller Name'
        ]);
    }

    public function test_admin_can_view_reseller_show()
    {
        $reseller = ToolReseller::create([
            'user_id' => $this->clientUser->id,
            'name' => 'Test Reseller',
            'token' => 'random_token_123',
            'credit_balance' => 100,
            'status' => 'active',
            'currency_id' => $this->currency->id
        ]);

        $response = $this->actingAs($this->admin)->get("/admin/resellers/{$reseller->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_update_reseller()
    {
        $reseller = ToolReseller::create([
            'user_id' => $this->clientUser->id,
            'name' => 'Test Reseller',
            'token' => 'random_token_123',
            'credit_balance' => 100,
            'status' => 'active',
            'currency_id' => $this->currency->id
        ]);

        $response = $this->actingAs($this->admin)->put("/admin/resellers/{$reseller->id}", [
            'name' => 'Updated Reseller',
            'status' => 'inactive',
            'currency_id' => $this->currency->id
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tool_resellers', [
            'id' => $reseller->id,
            'name' => 'Updated Reseller',
            'status' => 'inactive'
        ]);
    }

    public function test_admin_can_delete_reseller()
    {
        $reseller = ToolReseller::create([
            'user_id' => $this->clientUser->id,
            'name' => 'Test Reseller',
            'token' => 'random_token_123',
            'credit_balance' => 100,
            'status' => 'active',
            'currency_id' => $this->currency->id
        ]);

        $response = $this->actingAs($this->admin)->delete("/admin/resellers/{$reseller->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('tool_resellers', [
            'id' => $reseller->id,
            'status' => 'inactive'
        ]);
    }

    public function test_admin_can_adjust_balance()
    {
        $reseller = ToolReseller::create([
            'user_id' => $this->clientUser->id,
            'name' => 'Test Reseller',
            'token' => 'random_token_123',
            'credit_balance' => 100,
            'status' => 'active',
            'currency_id' => $this->currency->id
        ]);

        $response = $this->actingAs($this->admin)->post("/admin/resellers/{$reseller->id}/balance", [
            'amount' => 50,
            'type' => 'top_up',
            'note' => 'Add balance'
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
        // Balance depends on service logic, but we test the endpoint response
    }

    public function test_admin_can_search_users()
    {
        $response = $this->actingAs($this->admin)->get('/admin/resellers/search-users?q=john');
        $response->assertStatus(200);
    }
}
