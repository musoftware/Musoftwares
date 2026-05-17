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
}
