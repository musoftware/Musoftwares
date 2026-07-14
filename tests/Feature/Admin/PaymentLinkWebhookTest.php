<?php

namespace Tests\Feature\Admin;

use App\Helpers\KashierHelper;
use App\Models\PaymentLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentLinkWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_controller_dispatches_job_for_payment_link_source(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $user = User::factory()->create();
        $link = PaymentLink::factory()->create(['user_id' => $user->id, 'amount' => 100]);

        $payload = [
            'data' => [
                'status' => 'SUCCESS',
                'transactionId' => 'trx_xyz',
                'amount' => 100,
                'metaData' => json_encode([
                    'source' => 'payment-link',
                    'payment_link_id' => $link->id,
                ]),
            ],
        ];

        $response = $this->call(
            'POST',
            route('guest.payment-links.webhook'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response->assertStatus(202);
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\ProcessWebhookJob::class);
    }

    public function test_webhook_ignores_non_payment_link_source(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $payload = [
            'data' => [
                'status' => 'SUCCESS',
                'transactionId' => 'trx_xyz',
                'amount' => 100,
                'metaData' => json_encode(['source' => 'subscription-purchase']),
            ],
        ];

        $response = $this->call(
            'POST',
            route('guest.payment-links.webhook'),
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ignored']);
        \Illuminate\Support\Facades\Queue::assertNothingPushed();
    }

    public function test_handle_payment_link_marks_paid(): void
    {
        $user = User::factory()->create();
        $link = PaymentLink::factory()->create(['user_id' => $user->id, 'amount' => 100]);

        $job = new \App\Jobs\ProcessWebhookJob(new \App\Models\IncomingWebhook([
            'source' => 'kashier',
            'payload' => ['data' => ['status' => 'SUCCESS', 'transactionId' => 'trx_1']],
            'headers' => [],
        ]));

        $reflection = new \ReflectionMethod($job, 'handlePaymentLink');
        $reflection->setAccessible(true);
        $reflection->invoke($job, 'trx_1', 100, ['payment_link_id' => $link->id]);

        $link->refresh();
        $this->assertSame(PaymentLink::STATUS_PAID, $link->status);
        $this->assertSame(PaymentLink::METHOD_KASHIER, $link->paid_method);
        $this->assertSame('trx_1', $link->paid_transaction_id);
    }

    public function test_handle_payment_link_is_idempotent_for_double_webhook(): void
    {
        $user = User::factory()->create();
        $link = PaymentLink::factory()->create(['user_id' => $user->id, 'amount' => 100]);

        $webhook = new \App\Models\IncomingWebhook([
            'source' => 'kashier',
            'payload' => ['data' => ['status' => 'SUCCESS', 'transactionId' => 'trx_1']],
            'headers' => [],
        ]);

        $job = new \App\Jobs\ProcessWebhookJob($webhook);
        $reflection = new \ReflectionMethod($job, 'handlePaymentLink');
        $reflection->setAccessible(true);

        $reflection->invoke($job, 'trx_1', 100, ['payment_link_id' => $link->id]);
        $firstPaidAt = $link->fresh()->paid_at;

        $reflection->invoke($job, 'trx_1', 100, ['payment_link_id' => $link->id]);

        $this->assertEquals($firstPaidAt, $link->fresh()->paid_at);
        $this->assertSame('trx_1', $link->fresh()->paid_transaction_id);
    }

    public function test_handle_payment_link_rejects_amount_mismatch(): void
    {
        $user = User::factory()->create();
        $link = PaymentLink::factory()->create(['user_id' => $user->id, 'amount' => 100]);

        $job = new \App\Jobs\ProcessWebhookJob(new \App\Models\IncomingWebhook([
            'source' => 'kashier',
            'payload' => ['data' => ['status' => 'SUCCESS', 'transactionId' => 'trx_1']],
            'headers' => [],
        ]));

        $reflection = new \ReflectionMethod($job, 'handlePaymentLink');
        $reflection->setAccessible(true);

        $this->expectException(\Exception::class);
        $reflection->invoke($job, 'trx_1', 99, ['payment_link_id' => $link->id]);

        $link->refresh();
        $this->assertSame(PaymentLink::STATUS_PENDING, $link->status);
    }
}