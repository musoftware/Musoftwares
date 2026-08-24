<?php

namespace Tests\Feature;

use App\Models\PartnerClient;
use App\Models\PartnerCreditLease;
use App\Models\PartnerUsageLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerGatewayTest extends TestCase
{
    use RefreshDatabase;

    private PartnerClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = PartnerClient::createClient(
            name: 'Test CRM Partner',
            initialBalance: 50.00,
            rate: 0.01
        );
    }

    private function getHmacHeaders(string $payload = '', ?int $timestamp = null, ?string $secret = null): array
    {
        $timestamp = $timestamp ?? now()->timestamp;
        $secret = $secret ?? $this->client->client_secret;
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", $secret);

        return [
            'x-partner-key' => $this->client->client_key,
            'x-partner-timestamp' => (string) $timestamp,
            'x-partner-signature' => $signature,
        ];
    }

    public function test_it_rejects_unauthenticated_requests()
    {
        $response = $this->getJson('/api/v1/partner/balance');
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_it_rejects_expired_timestamp()
    {
        $expiredTimestamp = now()->timestamp - 600; // 10 minutes ago
        $headers = $this->getHmacHeaders('', $expiredTimestamp);

        $response = $this->getJson('/api/v1/partner/balance', $headers);
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'Request timestamp expired or skewed beyond 300 seconds',
            ]);
    }

    public function test_it_rejects_tampered_payload_or_invalid_signature()
    {
        $headers = $this->getHmacHeaders('original_payload');

        $response = $this->postJson('/api/v1/partner/lease/acquire', ['requestedMessages' => 500], $headers);
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'Invalid HMAC signature',
            ]);
    }

    public function test_it_returns_partner_balance_info()
    {
        $headers = $this->getHmacHeaders('');

        $response = $this->getJson('/api/v1/partner/balance', $headers);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'clientName' => 'Test CRM Partner',
                'walletBalance' => 50.00,
                'costPerMessage' => 0.01,
                'currency' => 'USD',
                'isActive' => true,
            ]);
    }

    public function test_it_acquires_credit_lease_and_deducts_balance()
    {
        $payload = json_encode(['requestedMessages' => 500]);
        $headers = $this->getHmacHeaders($payload);

        $response = $this->postJson('/api/v1/partner/lease/acquire', ['requestedMessages' => 500], $headers);
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'grantedMessages' => 500,
                'costPerMessage' => 0.01,
                'totalReservedAmount' => 5.0,
                'remainingWalletBalance' => 45.0,
            ]);

        $this->assertDatabaseHas('partner_credit_leases', [
            'partner_client_id' => $this->client->id,
            'granted_messages' => 500,
            'reserved_amount' => 5.0000,
            'status' => 'ACTIVE',
        ]);

        $this->assertDatabaseHas('partner_usage_logs', [
            'partner_client_id' => $this->client->id,
            'type' => 'LEASE_RESERVE',
            'amount' => -5.0000,
        ]);
    }

    public function test_it_rejects_lease_if_insufficient_balance()
    {
        $this->client->update(['wallet_balance' => 1.00]);

        $payload = json_encode(['requestedMessages' => 500]); // Needs $5.00
        $headers = $this->getHmacHeaders($payload);

        $response = $this->postJson('/api/v1/partner/lease/acquire', ['requestedMessages' => 500], $headers);
        $response->assertStatus(402)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_it_settles_lease_with_partial_usage_and_refunds_unspent()
    {
        // 1. Acquire lease: 500 msgs -> $5.00 reserved, balance: $45.00
        $payloadAcquire = json_encode(['requestedMessages' => 500]);
        $headersAcquire = $this->getHmacHeaders($payloadAcquire);
        $resAcquire = $this->postJson('/api/v1/partner/lease/acquire', ['requestedMessages' => 500], $headersAcquire);
        $leaseId = $resAcquire->json('leaseId');

        // 2. Settle with 200 actual messages sent -> $2.00 charged, $3.00 refunded -> new balance $48.00
        $payloadSettle = json_encode([
            'leaseId' => $leaseId,
            'actualMessagesSent' => 200,
            'requestNewLease' => false,
        ]);
        $headersSettle = $this->getHmacHeaders($payloadSettle);

        $resSettle = $this->postJson('/api/v1/partner/lease/settle', [
            'leaseId' => $leaseId,
            'actualMessagesSent' => 200,
            'requestNewLease' => false,
        ], $headersSettle);

        $resSettle->assertStatus(200)
            ->assertJson([
                'success' => true,
                'settledMessages' => 200,
                'totalCharged' => 2.0,
                'refundedUnused' => 3.0,
                'newBalance' => 48.0,
            ]);

        $lease = PartnerCreditLease::where('lease_id', $leaseId)->first();
        $this->assertEquals('SETTLED', $lease->status);
        $this->assertEquals(200, $lease->settled_messages);
        $this->assertEquals(2.0, (float)$lease->final_charged_amount);

        $this->client->refresh();
        $this->assertEquals(48.0, (float)$this->client->wallet_balance);
    }

    public function test_it_prevents_duplicate_settlement_of_already_settled_lease()
    {
        // Acquire lease
        $payloadAcquire = json_encode(['requestedMessages' => 500]);
        $headersAcquire = $this->getHmacHeaders($payloadAcquire);
        $resAcquire = $this->postJson('/api/v1/partner/lease/acquire', ['requestedMessages' => 500], $headersAcquire);
        $leaseId = $resAcquire->json('leaseId');

        // First settle
        $payloadSettle = json_encode(['leaseId' => $leaseId, 'actualMessagesSent' => 100]);
        $headersSettle = $this->getHmacHeaders($payloadSettle);
        $this->postJson('/api/v1/partner/lease/settle', ['leaseId' => $leaseId, 'actualMessagesSent' => 100], $headersSettle)
            ->assertStatus(200);

        // Duplicate settle attempt
        $resDuplicate = $this->postJson('/api/v1/partner/lease/settle', ['leaseId' => $leaseId, 'actualMessagesSent' => 100], $headersSettle);
        $resDuplicate->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }
}
