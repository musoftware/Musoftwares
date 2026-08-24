<?php

namespace Tests\Feature;

use App\Models\PartnerClient;
use App\Models\PartnerUsageLog;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPartnerGatewayTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create([
            'email' => 'admin@musoftwares.com',
            'onboarding_completed' => true,
        ]);
        $this->admin->assignRole('admin');

        $this->clientUser = User::factory()->create([
            'email' => 'client@trenz.agency',
            'onboarding_completed' => true,
        ]);
        $this->clientUser->assignRole('client');
    }

    public function test_admin_can_view_partner_gateway_index()
    {
        PartnerClient::createClient('Trenz Agency', 100.0, 0.01, 'PAYG_PER_MSG', $this->clientUser->id);

        $response = $this->actingAs($this->admin)->get(route('admin.partner-gateway.index'));
        $response->assertStatus(200);
    }

    public function test_admin_can_activate_new_partner_for_user()
    {
        $response = $this->actingAs($this->admin)->post(route('admin.partner-gateway.store'), [
            'user_id' => $this->clientUser->id,
            'client_name' => 'Trenz Agency Production',
            'cost_per_message' => 0.0125,
            'initial_balance' => 50.0,
            'pricing_model' => 'PAYG_PER_MSG',
            'low_balance_threshold' => 15.0,
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('partner_clients', [
            'user_id' => $this->clientUser->id,
            'client_name' => 'Trenz Agency Production',
            'cost_per_message' => 0.0125,
            'wallet_balance' => 50.0,
        ]);

        $this->assertDatabaseHas('partner_usage_logs', [
            'type' => 'TOP_UP',
            'amount' => 50.0,
        ]);
    }

    public function test_admin_can_update_partner_settings()
    {
        $partner = PartnerClient::createClient('Old Name', 10.0, 0.01, 'PAYG_PER_MSG', $this->clientUser->id);

        $response = $this->actingAs($this->admin)->put(route('admin.partner-gateway.update', $partner->id), [
            'client_name' => 'New Name Updated',
            'cost_per_message' => 0.0200,
            'pricing_model' => 'SUBSCRIPTION',
            'low_balance_threshold' => 20.0,
            'is_active' => false,
        ]);

        $response->assertSessionHas('success');
        $partner->refresh();
        $this->assertEquals('New Name Updated', $partner->client_name);
        $this->assertEquals(0.0200, (float)$partner->cost_per_message);
        $this->assertFalse($partner->is_active);
    }

    public function test_admin_can_adjust_partner_balance()
    {
        $partner = PartnerClient::createClient('Trenz', 50.0, 0.01, 'PAYG_PER_MSG', $this->clientUser->id);

        $response = $this->actingAs($this->admin)->post(route('admin.partner-gateway.adjust-balance', $partner->id), [
            'amount' => 25.0,
            'reason' => 'Complimentary promotional credit',
        ]);

        $response->assertSessionHas('success');
        $partner->refresh();
        $this->assertEquals(75.0, (float)$partner->wallet_balance);

        $this->assertDatabaseHas('partner_usage_logs', [
            'partner_client_id' => $partner->id,
            'type' => 'ADJUSTMENT',
            'amount' => 25.0,
        ]);
    }

    public function test_admin_can_regenerate_partner_secret()
    {
        $partner = PartnerClient::createClient('Trenz', 50.0, 0.01, 'PAYG_PER_MSG', $this->clientUser->id);
        $oldSecret = $partner->client_secret;

        $response = $this->actingAs($this->admin)->post(route('admin.partner-gateway.regenerate-secret', $partner->id));
        $response->assertSessionHas('success');

        $partner->refresh();
        $this->assertNotEquals($oldSecret, $partner->client_secret);
        $this->assertStringStartsWith('sk_live_', $partner->client_secret);
    }
}
