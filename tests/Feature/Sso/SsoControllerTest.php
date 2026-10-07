<?php

namespace Tests\Feature\Sso;

use App\Models\SsoToken;
use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsoControllerTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_SECRET = 'sso-test-secret';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.goldsaversys.shared_secret' => self::TEST_SECRET,
            'services.erp.shared_secret' => self::TEST_SECRET,
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function signedHeaders(string $token): array
    {
        $timestamp = (string) now()->timestamp;

        return [
            'X-Sso-Signature' => hash_hmac('sha256', $timestamp.'.'.$token, self::TEST_SECRET),
            'X-Sso-Timestamp' => $timestamp,
        ];
    }

    public function test_verify_rejects_unsigned_request_and_keeps_token(): void
    {
        $user = User::factory()->create();
        SsoToken::create([
            'user_id' => $user->id,
            'token' => 'unsigned-token-1',
            'target_system' => 'goldsaversys',
            'expires_at' => now()->addMinute(),
        ]);

        $this->postJson('/api/sso/verify', ['token' => 'unsigned-token-1'])
            ->assertStatus(401)
            ->assertJson(['error' => 'invalid_signature']);

        $this->assertNull(SsoToken::where('token', 'unsigned-token-1')->value('used_at'));
    }

    public function test_verify_rejects_system_without_configured_secret(): void
    {
        config(['services.erp.shared_secret' => null]);

        $user = User::factory()->create();
        SsoToken::create([
            'user_id' => $user->id,
            'token' => 'erp-no-secret',
            'target_system' => 'erp',
            'expires_at' => now()->addMinute(),
        ]);

        $this->withHeaders($this->signedHeaders('erp-no-secret'))
            ->postJson('/api/sso/verify', ['token' => 'erp-no-secret'])
            ->assertStatus(401);
    }

    public function test_guest_is_redirected_to_login_for_protected_systems(): void
    {
        $response = $this->get('/sso/erp');
        $response->assertRedirect('/login');
    }

    public function test_guest_accessing_toolsys_is_redirected_to_public_tools_page(): void
    {
        config(['services.toolsys.url' => 'https://tools.musoftwares.com']);

        $response = $this->get('/sso/toolsys');
        $response->assertRedirect('https://tools.musoftwares.com/tools');
    }

    public function test_authenticated_user_is_redirected_with_sso_token(): void
    {
        config(['services.goldsaversys.url' => 'https://gold.musoftwares.com']);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sso/goldsaversys');

        $this->assertDatabaseHas('sso_tokens', [
            'user_id' => $user->id,
            'target_system' => 'goldsaversys',
            'used_at' => null,
        ]);

        $token = SsoToken::where('user_id', $user->id)->first();
        $response->assertRedirect("https://gold.musoftwares.com/sso/callback?token={$token->token}");
    }

    public function test_redirect_handles_gold_aliases(): void
    {
        config(['services.goldsaversys.url' => 'https://gold.musoftwares.com']);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sso/gold-saver');
        $token = SsoToken::where('user_id', $user->id)->first();

        $response->assertRedirect("https://gold.musoftwares.com/sso/callback?token={$token->token}");

        $this->assertDatabaseHas('sso_tokens', [
            'user_id' => $user->id,
            'target_system' => 'goldsaversys',
        ]);
    }

    public function test_redirect_returns_404_for_unknown_system(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/sso/non-existent-system');
        $response->assertStatus(404);
    }

    public function test_verify_rejects_missing_token(): void
    {
        $response = $this->postJson('/api/sso/verify', []);
        $response->assertStatus(400);
        $response->assertJson(['error' => 'Token is missing']);
    }

    public function test_verify_rejects_expired_or_invalid_token(): void
    {
        $response = $this->postJson('/api/sso/verify', ['token' => 'invalid-token-123']);
        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid or expired token']);
    }

    public function test_verify_atomically_consumes_token_and_returns_payload(): void
    {
        $user = User::factory()->create([
            'name' => 'John Gold',
            'email' => 'john@gold.com',
        ]);

        $token = SsoToken::create([
            'user_id' => $user->id,
            'token' => 'secure-test-token-456',
            'target_system' => 'goldsaversys',
            'expires_at' => now()->addMinute(),
        ]);

        $response = $this->withHeaders($this->signedHeaders('secure-test-token-456'))->postJson('/api/sso/verify', [
            'token' => 'secure-test-token-456',
        ]);

        $response->assertOk();
        $response->assertJson([
            'user' => [
                'id' => $user->id,
                'name' => 'John Gold',
                'email' => 'john@gold.com',
            ],
            'system' => 'goldsaversys',
        ]);

        $token->refresh();
        $this->assertNotNull($token->used_at);

        // Attempting to reuse the token must immediately fail
        $secondResponse = $this->withHeaders($this->signedHeaders('secure-test-token-456'))->postJson('/api/sso/verify', [
            'token' => 'secure-test-token-456',
        ]);
        $secondResponse->assertStatus(401);
    }

    public function test_verify_does_not_leak_gold_subscriptions_to_erp(): void
    {
        $user = User::factory()->create();

        // User has active gold subscription, but NO erp subscription
        UserSubscription::create([
            'user_id' => $user->id,
            'object' => 'gold-saver-pro',
            'status' => 'active',
            'expires_at' => now()->addMonth(),
        ]);

        SsoToken::create([
            'user_id' => $user->id,
            'token' => 'erp-token-789',
            'target_system' => 'erp',
            'expires_at' => now()->addMinute(),
        ]);

        $response = $this->withHeaders($this->signedHeaders('erp-token-789'))->postJson('/api/sso/verify', [
            'token' => 'erp-token-789',
        ]);

        $response->assertOk();
        $response->assertJson([
            'system' => 'erp',
            'subscription' => null,
        ]);
    }

    public function test_verify_validates_hmac_signature_when_provided(): void
    {
        config(['services.goldsaversys.shared_secret' => 'super-secret-hmac']);

        $user = User::factory()->create();
        SsoToken::create([
            'user_id' => $user->id,
            'token' => 'hmac-token-111',
            'target_system' => 'goldsaversys',
            'expires_at' => now()->addMinute(),
        ]);

        // Wrong signature
        $badResponse = $this->withHeaders([
            'X-GoldSaver-Signature' => 'invalid-hmac',
            'X-GoldSaver-Timestamp' => (string) now()->timestamp,
        ])->postJson('/api/sso/verify', [
            'token' => 'hmac-token-111',
        ]);

        $badResponse->assertStatus(401);
        $badResponse->assertJson(['error' => 'invalid_signature']);

        // Since wrong signature failed verification, token was NOT consumed
        $token = SsoToken::where('token', 'hmac-token-111')->first();
        $this->assertNull($token->used_at);

        // Correct signature succeeds and consumes the token
        $timestamp = (string) now()->timestamp;
        $validSignature = hash_hmac('sha256', $timestamp.'.hmac-token-111', 'super-secret-hmac');

        $goodResponse = $this->withHeaders([
            'X-GoldSaver-Signature' => $validSignature,
            'X-GoldSaver-Timestamp' => $timestamp,
        ])->postJson('/api/sso/verify', [
            'token' => 'hmac-token-111',
        ]);

        $goodResponse->assertOk();

        $token->refresh();
        $this->assertNotNull($token->used_at);
    }
}
