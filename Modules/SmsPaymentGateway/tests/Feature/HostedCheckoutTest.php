<?php

namespace Modules\SmsPaymentGateway\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\SmsPaymentGateway\Models\SmsGatewayCheckoutSession;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;

class HostedCheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $session;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $this->session = SmsGatewayCheckoutSession::create([
            'user_id' => $this->user->id,
            'session_id' => 'cs_test_' . \Illuminate\Support\Str::random(24),
            'amount' => 350.00,
            'currency_id' => 1,
            'status' => 'open',
            'is_test' => true,
            'success_url' => 'https://example.com/success',
            'expires_at' => now()->addMinutes(30)
        ]);
    }

    public function test_can_view_hosted_checkout_page_for_open_session()
    {
        $response = $this->get('/pay/' . $this->session->session_id);
        
        $response->assertStatus(200)
                 ->assertViewIs('sms-payment-gateway::checkout.hosted')
                 ->assertSee('350.00');
    }

    public function test_checkout_page_auto_expires_past_due_sessions()
    {
        // Force expire the session in the past
        $this->session->update(['expires_at' => now()->subMinutes(10)]);
        
        $response = $this->get('/pay/' . $this->session->session_id);
        
        $response->assertStatus(200)
                 ->assertViewIs('sms-payment-gateway::checkout.hosted')
                 ->assertSee(__('sms_gateway.session_expired_title'));
                 
        $this->assertEquals('expired', $this->session->fresh()->status);
    }

    public function test_can_verify_payment_and_complete_session()
    {
        // Create an unmatched transaction that matches the session amount
        $transaction = SmsPaymentGatewayTransaction::factory()->create([
            'user_id' => $this->user->id,
            'sender' => 'VodafoneCash',
            'amount' => 350.00,
            'reference_number' => 'REF123456789',
            'status' => 'unmatched',
            'sms_message' => 'تم استلام مبلغ',
        ]);

        $response = $this->postJson('/pay/' . $this->session->session_id . '/verify', [
            'transaction_reference' => 'REF123456789',
            'payment_method' => 'vodafone_cash'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'paid' => true,
                 ]);

        $this->assertEquals('complete', $this->session->fresh()->status);
        $this->assertEquals('matched', $transaction->fresh()->status);
    }

    public function test_cannot_verify_payment_with_wrong_reference()
    {
        $response = $this->postJson('/pay/' . $this->session->session_id . '/verify', [
            'transaction_reference' => 'WRONG_REF',
            'payment_method' => 'vodafone_cash'
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'paid' => false,
                 ]);

        $this->assertEquals('open', $this->session->fresh()->status);
    }
}
