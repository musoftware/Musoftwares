<?php

namespace Modules\CRM\Tests\Feature;

use Tests\TestCase;
use Modules\CRM\Domains\Communication\Actions\ReceiveWhatsAppWebhookAction;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs\ProcessWhatsAppWebhookJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WebhookIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall(); // Clear redis before test
    }

    public function test_identical_webhooks_are_deduplicated_by_redis_lock()
    {
        Queue::fake();

        $account = WhatsAppAccount::factory()->create();
        $action = app(ReceiveWhatsAppWebhookAction::class);

        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    ['id' => 'msg_123456']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Simulate two concurrent webhook requests with the same message ID
        $action->execute($account, $payload, 'message');
        $action->execute($account, $payload, 'message');
        $action->execute($account, $payload, 'message');

        // Assert only ONE job was actually dispatched
        Queue::assertPushed(ProcessWhatsAppWebhookJob::class, 1);
    }

    public function test_unique_webhooks_are_both_processed()
    {
        Queue::fake();

        $account = WhatsAppAccount::factory()->create();
        $action = app(ReceiveWhatsAppWebhookAction::class);

        $payload1 = [
            'entry' => [
                ['changes' => [['value' => ['messages' => [['id' => 'msg_111']]]]]]
            ]
        ];

        $payload2 = [
            'entry' => [
                ['changes' => [['value' => ['messages' => [['id' => 'msg_222']]]]]]
            ]
        ];

        $action->execute($account, $payload1, 'message');
        $action->execute($account, $payload2, 'message');

        Queue::assertPushed(ProcessWhatsAppWebhookJob::class, 2);
    }
}
