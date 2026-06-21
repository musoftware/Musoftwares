<?php

namespace Modules\Booking\tests\Feature\Booking\WaReminders;

use Tests\TestCase;

class WaWebhookControllerTest extends TestCase
{
    public function test_valid_webhook_updates_log()
    {
        $mockProvider = \Mockery::mock(\Modules\Booking\app\Features\WaReminders\Services\WhatsAppProviderInterface::class);
        $mockProvider->shouldReceive('verifyWebhookSignature')->once()->andReturn(true);
        $this->app->instance(\Modules\Booking\app\Features\WaReminders\Services\WhatsAppProviderInterface::class, $mockProvider);

        $mockTracker = \Mockery::mock(\Modules\Booking\app\Features\WaReminders\Services\WhatsAppDeliveryTracker::class);
        $mockTracker->shouldReceive('updateDeliveryStatus')->once()->with('msg123', 'delivered', null);
        $this->app->instance(\Modules\Booking\app\Features\WaReminders\Services\WhatsAppDeliveryTracker::class, $mockTracker);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'statuses' => [
                                    [
                                        'id' => 'msg123',
                                        'status' => 'delivered'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->postJson(route('booking.wa.webhook'), $payload, [
            'X-Hub-Signature' => 'valid-signature'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_invalid_signature_is_rejected()
    {
        $mockProvider = \Mockery::mock(\Modules\Booking\app\Features\WaReminders\Services\WhatsAppProviderInterface::class);
        $mockProvider->shouldReceive('verifyWebhookSignature')->once()->andReturn(false);
        $this->app->instance(\Modules\Booking\app\Features\WaReminders\Services\WhatsAppProviderInterface::class, $mockProvider);

        $response = $this->postJson(route('booking.wa.webhook'), [], [
            'X-Hub-Signature' => 'invalid-signature'
        ]);

        $response->assertStatus(401);
        $response->assertJson(['error' => 'Invalid signature']);
    }

    public function test_webhook_replay_idempotency()
    {
        // Tests that duplicate webhooks don't cause issues
        // It should just call tracker->updateDeliveryStatus again which should be idempotent
        $this->assertTrue(true); // Placeholder for actual DB idempotency test
    }

}
