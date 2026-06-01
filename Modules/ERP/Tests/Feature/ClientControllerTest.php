<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Tests\TestCase;

class ClientControllerTest extends TestCase
{
    use DatabaseTransactions;
    protected function setUp(): void
    {
        parent::setUp();

        // Removed migrate:fresh
        
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
    }

    public function test_client_show_page_loads_successfully_without_authorization_errors(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        
        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'Euro Client',
            'email' => 'euro@example.com',
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get("/erp/clients/{$client->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('ERP/Clients/Show')
            ->has('client')
        );
    }

    public function test_client_creation_successfully(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active', 'base_currency_id' => 1]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post('/erp/clients', [
                'name' => 'New Client',
                'email' => 'new@client.com',
                'phone' => '123456789',
                'currency_id' => 1,
            ]);

        $response->assertRedirect(route('erp.dashboard', ['section' => 'clients']));
        
        $this->assertDatabaseHas('erp_tenant_clients', [
            'name' => 'New Client',
            'email' => 'new@client.com',
        ]);
    }

    public function test_client_update_successfully(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        
        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'Old Name',
            'email' => 'old@example.com',
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->put("/erp/clients/{$client->id}", [
                'name' => 'Updated Name',
                'email' => 'updated@example.com',
                'phone' => '987654321',
                'currency_id' => 1,
            ]);

        $response->assertRedirect(route('erp.dashboard', ['section' => 'clients']));
        
        $client->refresh();
        $this->assertEquals('Updated Name', $client->name);
        $this->assertEquals('updated@example.com', $client->email);
    }

    public function test_client_delete_successfully(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        
        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'To Delete',
            'email' => 'delete@example.com',
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->delete("/erp/clients/{$client->id}");

        $response->assertRedirect();
        
        $this->assertNull(TenantClient::find($client->id));
    }

    public function test_client_update_status_successfully(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        
        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'Lead Client',
            'email' => 'lead@example.com',
            'status' => 'lead',
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->put("/erp/clients/{$client->id}/status", [
                'status' => 'active',
            ]);

        // Note: Check ClientController -> updateStatus route (might not exist in web.php if it's handled differently, but just testing standard flow)
        $client->refresh();
        $this->assertEquals('active', $client->status);
    }
}
