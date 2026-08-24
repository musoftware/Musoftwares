<?php

namespace Tests\Feature;

use App\Models\Currency;
use App\Models\PartnerClient;
use App\Models\PartnerUsageLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientPartnerGatewayTest extends TestCase
{
    use RefreshDatabase;

    private User $clientUser;
    private PartnerClient $partner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $usd = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );

        $this->clientUser = User::factory()->create([
            'email' => 'client@trenz.agency',
            'currency_id' => $usd->id,
            'onboarding_completed' => true,
        ]);
        $this->clientUser->assignRole('client');

        $this->partner = PartnerClient::createClient(
            'Trenz Agency',
            10.0,
            0.01,
            'PAYG_PER_MSG',
            $this->clientUser->id
        );
    }

    public function test_client_can_view_partner_gateway_dashboard()
    {
        $response = $this->actingAs($this->clientUser)->get(route('client.partner-gateway.index'));
        $response->assertStatus(200);
    }

    public function test_client_can_top_up_partner_balance_from_user_wallet()
    {
        $usd = Currency::where('currency', 'USD')->first();
        // Give user $100 in main wallet balance
        $this->clientUser->add_balance(100.0, 'Deposit', 'received', $usd->id);

        $response = $this->actingAs($this->clientUser)->post(route('client.partner-gateway.topup-wallet'), [
            'amount_usd' => 30.0,
        ]);

        $response->assertSessionHas('success');

        $this->partner->refresh();
        $this->assertEquals(40.0, (float)$this->partner->wallet_balance); // 10 initial + 30 topup

        $this->clientUser->refresh();
        $this->assertEquals(70.0, (float)$this->clientUser->user_balance); // 100 - 30

        $this->assertDatabaseHas('partner_usage_logs', [
            'partner_client_id' => $this->partner->id,
            'type' => 'TOP_UP',
            'amount' => 30.0,
        ]);
    }

    public function test_client_cannot_top_up_with_insufficient_wallet_balance()
    {
        $this->clientUser->update(['user_balance' => 5.0]);

        $response = $this->actingAs($this->clientUser)->post(route('client.partner-gateway.topup-wallet'), [
            'amount_usd' => 50.0,
        ]);

        $response->assertSessionHas('error');

        $this->partner->refresh();
        $this->assertEquals(10.0, (float)$this->partner->wallet_balance); // Unchanged
    }

    public function test_client_can_regenerate_their_own_secret()
    {
        $oldSecret = $this->partner->client_secret;

        $response = $this->actingAs($this->clientUser)->post(route('client.partner-gateway.regenerate-secret'));
        $response->assertSessionHas('success');

        $this->partner->refresh();
        $this->assertNotEquals($oldSecret, $this->partner->client_secret);
        $this->assertStringStartsWith('sk_live_', $this->partner->client_secret);
    }
}
