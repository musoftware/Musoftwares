<?php

namespace Tests\Feature\Security;

use App\Models\NotificationCampaign;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SmsPaymentGateway\Models\SmsGatewayApiKey;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayWallet;
use Tests\TestCase;

class PublicEndpointHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function makeCampaign(?string $targetUrl): NotificationCampaign
    {
        return NotificationCampaign::create([
            'title' => 'Promo',
            'body' => 'Body',
            'target_url' => $targetUrl,
            'status' => 'sent',
            'audience_type' => 'global',
        ]);
    }

    // ── Campaign tracking open redirect ───────────────────────────────────

    public function test_tracking_refuses_external_redirect_not_stored_on_campaign(): void
    {
        $campaign = $this->makeCampaign('https://partner.example.com/offer');

        $this->get('/track/campaign/'.$campaign->id.'?redirect='.urlencode('https://evil.example.net/phish'))
            ->assertRedirect(url('/'));
    }

    public function test_tracking_allows_stored_campaign_url_and_same_host(): void
    {
        $campaign = $this->makeCampaign('https://partner.example.com/offer');

        $this->get('/track/campaign/'.$campaign->id.'?redirect='.urlencode('https://partner.example.com/offer'))
            ->assertRedirect('https://partner.example.com/offer');

        $this->get('/track/campaign/'.$campaign->id.'?redirect='.urlencode(url('/pricing')))
            ->assertRedirect(url('/pricing'));

        $this->get('/track/campaign/'.$campaign->id.'?redirect='.urlencode('javascript:alert(1)'))
            ->assertRedirect(url('/'));
    }

    // ── SSRF on public website tools ──────────────────────────────────────

    public function test_public_inspect_tools_refuse_internal_addresses(): void
    {
        foreach (['inspect-website', 'inspect-speed-loss', 'inspect-payment-gateway', 'inspect-pixel', 'inspect-competitor'] as $endpoint) {
            $this->postJson('/tools/'.$endpoint, ['url' => 'http://169.254.169.254/latest/meta-data/'])
                ->assertStatus(422)
                ->assertJson(['success' => false]);
        }

        $this->postJson('/tools/inspect-website', ['url' => 'file:///etc/passwd'])->assertStatus(422);
        $this->postJson('/tools/inspect-pixel', ['url' => 'localhost:6379'])->assertStatus(422);
    }

    // ── Impersonation ─────────────────────────────────────────────────────

    public function test_impersonation_requires_post_and_regenerates_session(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = User::factory()->create(['onboarding_completed' => true, 'email_verified_at' => now()]);
        $admin->assignRole('admin');
        $client = User::factory()->create(['onboarding_completed' => true, 'email_verified_at' => now()]);
        $client->assignRole('client');

        $this->actingAs($admin)->get('/admin/erp/'.$client->id.'/impersonate')->assertStatus(405);

        $this->startSession();
        $before = session()->getId();

        $this->actingAs($admin)->post('/admin/erp/'.$client->id.'/impersonate')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($client);
        $this->assertNotSame($before, session()->getId());
        $this->assertSame($admin->id, session('impersonator_id'));

        $this->get('/admin/stop-impersonate')->assertStatus(405);

        $impersonatedSession = session()->getId();
        $this->post('/admin/stop-impersonate')->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertNotSame($impersonatedSession, session()->getId());
    }

    // ── Wallet transfer recipient search ──────────────────────────────────

    public function test_transfer_recipient_search_requires_exact_email(): void
    {
        $sender = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);
        User::factory()->create(['name' => 'Target Person', 'email' => 'Target.Person@example.com']);

        $this->actingAs($sender)
            ->getJson('/financial/transfer-api/search-users?q=target')
            ->assertOk()
            ->assertExactJson(['users' => []]);

        $this->actingAs($sender)
            ->getJson('/financial/transfer-api/search-users?q=example.com')
            ->assertOk()
            ->assertExactJson(['users' => []]);

        $this->actingAs($sender)
            ->getJson('/financial/transfer-api/search-users?q='.urlencode('target.person@EXAMPLE.com'))
            ->assertOk()
            ->assertExactJson(['users' => [['name' => 'Target Person', 'email' => 'Target.Person@example.com']]]);
    }

    public function test_transfer_page_loads_without_controller_middleware_error(): void
    {
        $user = User::factory()->create(['email_verified_at' => now(), 'onboarding_completed' => true]);

        $this->actingAs($user)->get('/financial/transfer/history')->assertOk();
    }

    // ── SMS gateway wallet endpoints ──────────────────────────────────────

    public function test_sms_wallet_endpoints_require_api_key_and_are_scoped_to_merchant(): void
    {
        $merchant = User::factory()->create();
        $otherMerchant = User::factory()->create();
        $keys = SmsGatewayApiKey::generateKeyPair($merchant->id, 'Test', true);

        $ownWallet = SmsPaymentGatewayWallet::create(['user_id' => $merchant->id, 'payment_type' => 'Wallet', 'phone_number' => '01000000001', 'is_active' => true]);
        $foreignWallet = SmsPaymentGatewayWallet::create(['user_id' => $otherMerchant->id, 'payment_type' => 'Wallet', 'phone_number' => '01000000002', 'is_active' => true]);

        $this->getJson('/api/v1/sms-payment-gateway/get-random-wallet')->assertStatus(401);
        $this->postJson('/api/v1/sms-payment-gateway/verify-payment', ['phone_number' => '01000000002'])->assertStatus(401);

        $auth = ['Authorization' => 'Bearer '.$keys['publishable_key']];

        for ($i = 0; $i < 5; $i++) {
            $this->getJson('/api/v1/sms-payment-gateway/get-random-wallet', $auth)
                ->assertOk()
                ->assertJsonPath('data.wallet_id', $ownWallet->id);
        }

        $this->postJson('/api/v1/sms-payment-gateway/verify-payment', ['phone_number' => '01000000002', 'wallet_id' => $foreignWallet->id], $auth)
            ->assertStatus(404);
    }
}
