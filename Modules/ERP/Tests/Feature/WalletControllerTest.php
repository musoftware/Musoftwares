<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\ERP\Models\ClientWallet;
use Modules\ERP\Models\WalletTransaction as ClientWalletTransaction;
use Modules\ERP\Models\Client;
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
        
        $this->withoutMiddleware();
    }

    public function test_transactions_returns_empty_when_no_transactions_exist(): void
    {
        $user = User::factory()->create();

        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name' => 'Acme Corp',
            'status' => 'active',
        ]);

        session(['tenant_id' => $tenant->id]);

        \Illuminate\Support\Facades\DB::table('tenant_clients')->insert([
            'tenant_id' => $tenant->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'currency' => 'USD',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $client = Client::first();

        $wallet = ClientWallet::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'balance' => 0,
            'currency' => 'USD',
        ]);

        $this->assertEquals(0, ClientWalletTransaction::where('wallet_id', $wallet->id)->count());

        $response = $this
            ->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->getJson("/erp/clients/{$client->id}/wallet/transactions");

        if ($response->status() == 404 || $response->status() == 302) {
            $response = $this
                ->actingAs($user)
                ->withSession(['tenant_id' => $tenant->id])
                ->getJson("/clients/{$client->id}/wallet/transactions");
        }

        $response->assertStatus(200);

        $response->assertJsonPath('data', []);
        $response->assertJsonPath('total', 0);
    }

    public function test_show_wallet(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Acme Corp', 'status' => 'active']);
        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'test@example.com', 'currency' => 'USD']);
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->get("/erp/clients/{$client->id}/wallet");
        $response->assertStatus(200);
    }

    public function test_wallet_actions(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        session(['tenant_id' => $tenant->id]);

        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'client@test.com', 'currency' => 'USD']);

        $wallet = ClientWallet::create([
            'tenant_id' => $tenant->id,
            'client_id' => $client->id,
            'balance' => 0,
            'currency' => 'USD',
        ]);

        // Test Credit
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/credit", [
            'amount' => 500.00,
            'note' => 'Initial deposit',
        ]);
        $response->assertSessionHas('success');
        $wallet->refresh();
        $this->assertEquals(500.00, (float)$wallet->balance);

        // Test Lock Funds
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/lock", [
            'amount' => 100.00,
            'note' => 'Lock escrow',
        ]);
        $response->assertSessionHas('success');
        $wallet->refresh();
        $this->assertEquals(400.00, (float)$wallet->balance);
        $this->assertEquals(100.00, (float)$wallet->locked_balance);

        // Test Unlock Funds
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/unlock", [
            'amount' => 50.00,
            'note' => 'Release escrow',
        ]);
        $response->assertSessionHas('success');
        $wallet->refresh();
        $this->assertEquals(450.00, (float)$wallet->balance);
        $this->assertEquals(50.00, (float)$wallet->locked_balance);

        // Test Debit
        $response = $this->actingAs($user)->withSession(['tenant_id' => $tenant->id])->post("/erp/clients/{$client->id}/wallet/debit", [
            'amount' => 200.00,
            'note' => 'Service charge',
        ]);
        $response->assertSessionHas('success');
        $wallet->refresh();
        $this->assertEquals(250.00, (float)$wallet->balance);
    }

    public function test_user_cannot_modify_other_tenant_client_wallet(): void
    {
        // User 1 & Tenant 1
        $user1 = User::factory()->create();
        $tenant1 = Tenant::create(['user_id' => $user1->id, 'name' => 'Tenant 1', 'status' => 'active']);
        $client1 = Client::create(['tenant_id' => $tenant1->id, 'name' => 'Client 1', 'email' => 'client1@test.com', 'currency' => 'USD']);
        
        $wallet1 = ClientWallet::create([
            'tenant_id' => $tenant1->id,
            'client_id' => $client1->id,
            'balance' => 0.00,
            'currency' => 'USD',
        ]);

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
        $wallet1->refresh();
        $this->assertEquals(0.00, (float)$wallet1->balance);
    }
}
