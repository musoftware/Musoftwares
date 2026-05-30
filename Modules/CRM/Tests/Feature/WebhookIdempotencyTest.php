<?php

namespace Modules\CRM\Tests\Feature;

use Modules\CRM\Tests\Support\BaseTenantTestCase;
use Modules\CRM\Tests\Support\QueueTestHelpers;
use Modules\CRM\Domains\Communication\Actions\ReceiveWhatsAppWebhookAction;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs\ProcessWhatsAppWebhookJob;

class WebhookIdempotencyTest extends BaseTenantTestCase
{
    use QueueTestHelpers;

    protected function setUp(): void
    {
        parent::setUp();
        Redis::flushall(); // Clear redis before test
    }

    public function test_identical_webhooks_are_deduplicated_by_redis_lock()
    {
        Queue::fake(); // We can also use fakeQueueAndAssertPushed but we want to assert the count is exactly 1

        $account = WhatsAppAccount::factory()->create(['workspace_id' => $this->workspace->id]);
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
        $this->simulateConcurrentWebhook(function () use ($action, $account, $payload) {
            $action->execute($account, $payload, 'message');
        }, 3);
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
