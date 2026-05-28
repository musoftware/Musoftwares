<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\ERP\Models\WalletTransaction as ClientWalletTransaction;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Tests\TestCase;

class WalletControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', [
            '--path' => [
                'database/migrations',
                'Modules/Core/Database/Migrations',
                'Modules/ERP/Database/Migrations',
            ]
        ]);
        
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
        $this->withoutMiddleware();
    }


    public function test_show_wallet(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'test@example.com', 'currency_id' => 1]);
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->get("/erp/clients/{$client->id}/wallet");
        $response->assertStatus(200);
    }

    public function test_wallet_actions(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        session(['tenant_id' => $tenant->id]);

        $client = TenantClient::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'client@test.com', 'currency_id' => 1]);

        // Test Credit
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/credit", [
            'amount' => 500.00,
            'note' => 'Initial deposit',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals(500.00, (float)$client->balance());

        // Test Locked Balance by creating a sent invoice
        $invoice = \Modules\ERP\Models\Invoice::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'invoice_number' => 'INV-2026-001',
            'status' => 'sent',
            'amount' => 100.00,
            'paid_amount' => 0.00,
            'currency_id' => 1,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
        ]);

        $this->assertEquals(100.00, (float)$client->lockedBalance());

        // Test Debit
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/debit", [
            'amount' => 200.00,
            'note' => 'Service charge',
        ]);
        $response->assertSessionHas('success');
        $this->assertEquals(300.00, (float)$client->balance());
    }

    public function test_user_cannot_modify_other_tenant_client_wallet(): void
    {
        // User 1 & Tenant 1
        $user1 = User::factory()->create();
        $tenant1 = Tenant::create(['user_id' => $user1->id, 'name' => 'Tenant 1', 'status' => 'active']);
        $client1 = TenantClient::create(['tenant_id' => $tenant1->id, 'name' => 'Client 1', 'email' => 'client1@test.com', 'currency_id' => 1]);

        // User 2 & Tenant 2
        $user2 = User::factory()->create();
        $tenant2 = Tenant::create(['user_id' => $user2->id, 'name' => 'Tenant 2', 'status' => 'active']);
        
        // Setup session for User 2
        session(['tenant_id' => $tenant2->id]);

        $response = $this->actingAs($user2)->withSession(['tenant_id' => $tenant2->id])->postJson("/erp/clients/{$client1->id}/wallet/credit", [
            'amount' => 500.00,
            'note' => 'Hacker deposit',
        ]);
        
        // Should be not found (404) due to tenant isolation boundary checks
        $response->assertStatus(404);
        
        // Verify balance did not change
        $this->assertEquals(0.00, (float)$client1->balance());
    }
}
