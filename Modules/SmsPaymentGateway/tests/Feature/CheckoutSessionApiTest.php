<?php

namespace Modules\SmsPaymentGateway\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use Modules\SmsPaymentGateway\Models\SmsGatewayApiKey;
use Modules\SmsPaymentGateway\Models\SmsGatewayCheckoutSession;

class CheckoutSessionApiTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $apiKey;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        // Generate test API Key
        $this->apiKey = SmsGatewayApiKey::generateKeyPair($this->user->id, 'Test Key', true);
        
        \App\Models\Currency::firstOrCreate(['currency' => 'EGP'], ['name' => 'Egyptian Pound', 'symbol' => 'EGP']);
    }

    public function test_can_create_checkout_session_with_valid_api_key()
    {
        $payload = [
            'amount' => 150.50,
            'currency' => 'EGP',
            'success_url' => 'https://example.com/success',
            'cancel_url' => 'https://example.com/cancel',
            'customer_name' => 'Ahmed Mohamed',
            'customer_email' => 'ahmed@example.com',
        ];

        $response = $this->postJson('/api/v1/sms-gateway/checkout/sessions', $payload, [
            'Authorization' => 'Bearer ' . $this->apiKey['secret_key']
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'id',
                     'object',
                     'amount',
                     'currency',
                     'status',
                     'url',
                     'expires_at'
                 ]);
                 
        $this->assertEquals(150.50, $response->json('amount'));
        $this->assertEquals('open', $response->json('status'));
        $this->assertTrue(str_starts_with($response->json('id'), 'cs_test_'));
    }

    public function test_cannot_create_session_with_invalid_api_key()
    {
        $response = $this->postJson('/api/v1/sms-gateway/checkout/sessions', [
            'amount' => 100,
            'currency' => 'EGP'
        ], [
            'Authorization' => 'Bearer sk_test_invalid12345'
        ]);

        $response->assertStatus(401);
    }

    public function test_can_retrieve_checkout_session()
    {
        // First create a session
        $createResponse = $this->postJson('/api/v1/sms-gateway/checkout/sessions', [
            'amount' => 500,
            'currency' => 'EGP',
            'success_url' => 'https://example.com/success'
        ], [
            'Authorization' => 'Bearer ' . $this->apiKey['secret_key']
        ]);

        $sessionId = $createResponse->json('id');

        // Then retrieve it
        $retrieveResponse = $this->getJson("/api/v1/sms-gateway/checkout/sessions/{$sessionId}", [
            'Authorization' => 'Bearer ' . $this->apiKey['secret_key']
        ]);

        $retrieveResponse->assertStatus(200)
                         ->assertJsonFragment([
                             'id' => $sessionId,
                             'amount' => 500,
                             'status' => 'open'
                         ]);
    }

    public function test_can_expire_checkout_session()
    {
        $createResponse = $this->postJson('/api/v1/sms-gateway/checkout/sessions', [
            'amount' => 200,
            'currency' => 'EGP',
            'success_url' => 'https://example.com/success'
        ], [
            'Authorization' => 'Bearer ' . $this->apiKey['secret_key']
        ]);

        $sessionId = $createResponse->json('id');

        $expireResponse = $this->postJson("/api/v1/sms-gateway/checkout/sessions/{$sessionId}/expire", [], [
            'Authorization' => 'Bearer ' . $this->apiKey['secret_key']
        ]);

        $expireResponse->assertStatus(200)
                       ->assertJsonFragment([
                           'id' => $sessionId,
                           'status' => 'expired'
                       ]);
                       
        $this->assertEquals('expired', SmsGatewayCheckoutSession::where('session_id', $sessionId)->first()->status);
    }
}
