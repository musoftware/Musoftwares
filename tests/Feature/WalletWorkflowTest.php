<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Client;
use Modules\ERP\Models\ClientWallet;
use Tests\TestCase;

class WalletWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Tenant $tenant;
    protected Client $client;
    protected ClientWallet $wallet;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->user->assignRole('client');

        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'Agency Pro',
            'status' => 'active',
        ]);

        session(['tenant_id' => $this->tenant->id]);

        $this->client = Client::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'email' => $this->user->email,
            'phone' => '+15559876543',
            'currency' => 'USD',
            'address' => '456 West Ave',
        ]);

        $this->wallet = ClientWallet::create([
            'tenant_id' => $this->tenant->id,
            'client_id' => $this->client->id,
            'balance' => 1000.00,
            'currency' => 'USD',
            'locked_balance' => 100.00,
        ]);
    }

    public function test_can_view_wallet_details(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->get(route('erp.wallet.show', $this->client->id));

        $response->assertStatus(200);
    }

    public function test_can_manually_credit_wallet(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.wallet.credit', $this->client->id), [
                'amount' => 250.00,
                'note' => 'Bonus payout credit',
            ]);

        $response->assertStatus(302);
        $this->assertEquals(1250.00, $this->wallet->fresh()->balance);
        $this->assertDatabaseHas('client_wallet_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'manual_credit',
            'amount' => 250.00,
            'note' => 'Credit: Bonus payout credit',
        ]);
    }

    public function test_can_manually_debit_wallet(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.wallet.debit', $this->client->id), [
                'amount' => 400.00,
                'note' => 'Hosting services deduction',
            ]);

        $response->assertStatus(302);
        $this->assertEquals(600.00, $this->wallet->fresh()->balance);
        $this->assertDatabaseHas('client_wallet_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'manual_debit',
            'amount' => 400.00,
            'note' => 'Debit: Hosting services deduction',
        ]);
    }

    public function test_can_lock_wallet_funds(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.wallet.lock', $this->client->id), [
                'amount' => 200.00,
                'note' => 'Escrow reservation',
            ]);

        $response->assertStatus(302);
        $this->assertEquals(800.00, $this->wallet->fresh()->balance);
        $this->assertEquals(300.00, $this->wallet->fresh()->locked_balance);
        $this->assertDatabaseHas('client_wallet_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'manual_debit',
            'amount' => 200.00,
            'note' => 'Funds locked: Escrow reservation',
        ]);
    }

    public function test_can_unlock_wallet_funds(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['tenant_id' => $this->tenant->id])
            ->post(route('erp.wallet.unlock', $this->client->id), [
                'amount' => 50.00,
                'note' => 'Release partial reservation',
            ]);

        $response->assertStatus(302);
        $this->assertEquals(1050.00, $this->wallet->fresh()->balance);
        $this->assertEquals(50.00, $this->wallet->fresh()->locked_balance);
        $this->assertDatabaseHas('client_wallet_transactions', [
            'wallet_id' => $this->wallet->id,
            'type' => 'manual_credit',
            'amount' => 50.00,
            'note' => 'Funds unlocked: Release partial reservation',
        ]);
    }
}
