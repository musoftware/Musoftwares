<?php

namespace Modules\SmsPaymentGateway\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use App\Models\PaymentOrder;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayOrderLink;
use Modules\SmsPaymentGateway\Services\RealtimePaymentMatchingEngine;
use Illuminate\Support\Facades\Event;
use App\Models\User;

class RealtimePaymentMatchingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected RealtimePaymentMatchingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new RealtimePaymentMatchingEngine();
        Event::fake(); // Prevent actual webhooks and events from firing
    }

    /**
     * @test
     */
    public function it_matches_transaction_to_order_by_phone_number()
    {
        $user = User::factory()->create();

        // 1. Create an Order
        $order = PaymentOrder::create([
            'user_id' => $user->id,
            'mobile_number' => '01012345678',
            'amount' => 488.00,
            'status' => 'pending',
            'payment_link_id' => 1
        ]);

        // 2. Create an OrderLink (User waiting for payment from this specific phone)
        SmsPaymentGatewayOrderLink::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'phone_number' => '01092270741',
            'status' => 'pending'
        ]);

        // 3. Create incoming transaction
        $transaction = SmsPaymentGatewayTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 488.00,
            'phone_number' => '01092270741',
            'status' => 'pending'
        ]);

        // 4. Run the Engine
        $matchedTx = $this->engine->manualMatch($order, '01092270741');

        $this->assertNotNull($matchedTx);

        // 5. Assertions
        $this->assertDatabaseHas('sms_payment_gateway_transactions', [
            'id' => $transaction->id,
            'status' => 'matched'
        ]);

        $this->assertDatabaseHas('payment_orders', [
            'id' => $order->id,
            'transaction_id' => $transaction->id,
            'status' => 'verified'
        ]);


        Event::assertDispatched('SmsPaymentGateway.OrderPaid');
    }

    /**
     * @test
     */
    public function it_matches_transaction_to_order_by_exact_reference()
    {
        $user = User::factory()->create();

        // 1. Create an Order with expected reference
        $order = PaymentOrder::create([
            'user_id' => $user->id,
            'mobile_number' => '01012345678',
            'amount' => 3000.00,
            'status' => 'pending',
            'payment_link_id' => 1,
            'expected_reference' => '015847083619' // Real reference from logs
        ]);

        // 3. Create incoming transaction
        $transaction = SmsPaymentGatewayTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 3000.00,
            'reference_number' => '015847083619',
            'status' => 'pending'
        ]);

        // 4. Run the Engine
        $matchedTx = $this->engine->manualMatch($order, '015847083619');

        $this->assertNotNull($matchedTx);

        $this->assertDatabaseHas('payment_orders', [
            'id' => $order->id,
            'transaction_id' => $transaction->id,
            'status' => 'verified'
        ]);
    }

    /**
     * @test
     */
    public function it_ignores_transaction_if_amount_varies_too_much()
    {
        $user = User::factory()->create();

        $order = PaymentOrder::create([
            'user_id' => $user->id,
            'mobile_number' => '01012345678',
            'amount' => 500.00, // Expected 500
            'status' => 'pending',
            'payment_link_id' => 1
        ]);

        SmsPaymentGatewayOrderLink::create([
            'user_id' => $user->id,
            'order_id' => $order->id,
            'phone_number' => '01012345678',
            'status' => 'pending'
        ]);

        $transaction = SmsPaymentGatewayTransaction::factory()->create([
            'user_id' => $user->id,
            'amount' => 400.00, // Received 400
            'phone_number' => '01012345678',
            'status' => 'pending'
        ]);

        $matchedTx = $this->engine->manualMatch($order, '01012345678');

        $this->assertNull($matchedTx); // Should not match due to amount mismatch

        $this->assertDatabaseHas('payment_orders', [
            'id' => $order->id,
            'status' => 'pending' // Remains unpaid
        ]);
    }
}
