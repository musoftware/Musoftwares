<?php

namespace Modules\SmsPaymentGateway\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Illuminate\Support\Facades\Schema;

class SmsIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_ingest_we_pay_sms_successfully()
    {
        $this->withoutExceptionHandling();

        $user = User::factory()->create();
        
        $device = SmsPaymentGatewayDevice::factory()->create([
            'user_id' => $user->id,
            'device_name' => 'Test Device',
            'device_token' => 'test_device_token_123',
            'status' => 'connected',
        ]);

        \Modules\SmsPaymentGateway\Models\SmsPaymentGatewaySetting::create([
            'user_id' => $user->id,
            'tenant_id' => $device->tenant_id,
            'whitelist_senders' => ['WE Pay', 'VF-Cash'],
        ]);

        $payload = [
            'sender' => 'WE Pay',
            'message' => 'تم استلام مبلغ 1500 EGP من رقم 01015218548',
            'timestamp' => 1717258380614,
            'device_token' => 'test_device_token_123'
        ];

        $response = $this->postJson('/api/v1/sms-payment-gateway/sms', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'transaction_detected' => true,
        ]);

        $this->assertDatabaseHas('sms_payment_gateway_transactions', [
            'device_id' => $device->id,
            'amount' => '1500',
            'sender' => 'WE Pay',
        ]);
    }
}
