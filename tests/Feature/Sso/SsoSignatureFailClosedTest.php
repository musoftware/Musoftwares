<?php

namespace Tests\Feature\Sso;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SsoSignatureFailClosedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function headersFor(string $system, string $payload, string $secret): array
    {
        $timestamp = (string) now()->timestamp;

        return [
            'X-Sso-System' => $system,
            'X-Sso-Signature' => hash_hmac('sha256', $timestamp.'.'.$payload, $secret),
            'X-Sso-Timestamp' => $timestamp,
        ];
    }

    public function test_subscription_sync_rejects_unknown_system_signed_with_empty_secret(): void
    {
        $this->postJson('/api/sso/subscriptions/sync', ['module' => 'toolsys', 'user_ids' => [1]], $this->headersFor('unknown-system', 'subscriptions-sync', ''))
            ->assertStatus(401)
            ->assertJson(['error' => 'invalid_signature']);
    }

    public function test_subscription_sync_rejects_unsigned_request(): void
    {
        $this->postJson('/api/sso/subscriptions/sync', ['module' => 'toolsys', 'user_ids' => [1]])
            ->assertStatus(401)
            ->assertJson(['error' => 'missing_signature_headers']);
    }

    public function test_subscription_sync_rejects_known_system_when_secret_is_not_configured(): void
    {
        config(['services.toolsys.shared_secret' => null]);

        $this->postJson('/api/sso/subscriptions/sync', ['module' => 'toolsys', 'user_ids' => [1]], $this->headersFor('toolsys', 'subscriptions-sync', ''))
            ->assertStatus(401);
    }

    public function test_notify_and_exchange_rates_reject_unknown_system(): void
    {
        $this->postJson('/api/sso/notify', ['email' => 'a@b.com', 'title' => 't', 'message' => 'm', 'channels' => ['mail']], $this->headersFor('evil', 'sso-notify', ''))
            ->assertStatus(401);

        $this->postJson('/api/sso/exchange-rates/sync', ['module' => 'goldsaversys'], $this->headersFor('evil', 'exchange-rates-sync', ''))
            ->assertStatus(401);
    }

    public function test_subscription_sync_accepts_valid_signature(): void
    {
        config(['services.toolsys.shared_secret' => 'sync-secret']);

        $this->postJson('/api/sso/subscriptions/sync', ['module' => 'toolsys', 'user_ids' => [1]], $this->headersFor('toolsys', 'subscriptions-sync', 'sync-secret'))
            ->assertOk();
    }
}
