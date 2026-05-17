<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Modules\Core\Models\Wallet;
use Modules\Core\Models\WalletTransaction;
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

        $wallet = Wallet::create([
            'owner_type' => Client::class,
            'owner_id' => $client->id,
            'context' => 'client',
            'balance' => 0,
            'currency' => 'USD',
        ]);

        $this->assertEquals(0, WalletTransaction::where('wallet_id', $wallet->id)->count());

        $response = $this
            ->actingAs($user)
            ->getJson("/erp/clients/{$client->id}/wallet/transactions");

        if ($response->status() == 404) {
            $response = $this
                ->actingAs($user)
                ->getJson("/clients/{$client->id}/wallet/transactions");
        }

        $response->assertStatus(200);

        $response->assertJsonPath('data', []);
        $response->assertJsonPath('total', 0);
    }

    public function test_show_wallet(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get("/erp/clients/3/wallet");
        $response->assertStatus(200);
    }

    public function test_wallet_actions(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create(['user_id' => $user->id, 'name' => 'Test Tenant', 'status' => 'active']);
        session(['tenant_id' => $tenant->id]);

        $client = Client::create(['tenant_id' => $tenant->id, 'name' => 'Test Client', 'email' => 'client@test.com', 'currency' => 'USD']);

        // Test Credit
        $response = $this->actingAs($user)->post("/erp/clients/{$client->id}/wallet/credit", [
            'amount' => 500.00,
            'note' => 'Initial deposit',
        ]);
        $response->assertSessionHas('success');
        $wallet = Wallet::where('owner_type', Client::class)->where('owner_id', $client->id)->first();
        $this->assertEquals(500.00, $wallet->balance);

        // Test Lock Funds
        $response = $this->actingAs($user)->post("/erp/clients/{$client->id}/wallet/lock", [
            'amount' => 100.00,
            'note' => 'Lock escrow',
        ]);
        $response->assertSessionHas('success');
        $wallet->refresh();
        $this->assertEquals(400.00, $wallet->balance);
        $this->assertEquals(100.00, $wallet->locked_balance);

        // Test Unlock Funds
        $response = $this->actingAs($user)->post("/erp/clients/{$client->id}/wallet/unlock", [
            'amount' => 50.00,
            'note' => 'Release escrow',
        ]);
        $response->assertSessionHas('success');
        $wallet->refresh();
        $this->assertEquals(450.00, $wallet->balance);
        $this->assertEquals(50.00, $wallet->locked_balance);

        // Test Debit
        $response = $this->actingAs($user)->post("/erp/clients/{$client->id}/wallet/debit", [
            'amount' => 200.00,
            'note' => 'Service charge',
        ]);
        $response->assertSessionHas('success');
        $wallet->refresh();
        $this->assertEquals(250.00, $wallet->balance);
    }
}
