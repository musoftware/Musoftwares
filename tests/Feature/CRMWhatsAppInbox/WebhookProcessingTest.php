<?php

namespace Tests\Feature\CRMWhatsAppInbox;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\CRM\Models\WhatsAppAccount;
use Tests\TestCase;

class WebhookProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_accepts_valid_payload(): void
    {
        Queue::fake();

        $account = WhatsAppAccount::factory()->connected()->create();

        $response = $this->postJson("/crm/whatsapp/webhook/{$account->id}", [
            'from'       => '+201234567890',
            'type'       => 'message',
            'body'       => 'Hello from WhatsApp!',
            'message_id' => 'wamid_webhook_001',
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'queued']);

        Queue::assertPushed(\App\Modules\CRMWhatsAppInbox\Jobs\ProcessWhatsAppWebhookJob::class);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create([
            'provider_config' => ['webhook_secret' => 'my_secret_key'],
        ]);

        $response = $this->postJson("/crm/whatsapp/webhook/{$account->id}", [
            'type' => 'message',
            'body' => 'Test',
        ], [
            'X-Webhook-Signature' => 'invalid_signature',
        ]);

        $response->assertForbidden();
    }

    public function test_webhook_returns_404_for_nonexistent_account(): void
    {
        $response = $this->postJson('/crm/whatsapp/webhook/99999', [
            'type' => 'message',
        ]);

        $response->assertNotFound();
    }

    public function test_webhook_handles_status_update_payload(): void
    {
        Queue::fake();

        $account = WhatsAppAccount::factory()->connected()->create();

        $response = $this->postJson("/crm/whatsapp/webhook/{$account->id}", [
            'type'       => 'status',
            'message_id' => 'wamid_status_001',
            'status'     => 'delivered',
        ]);

        $response->assertOk();

        Queue::assertPushed(\App\Modules\CRMWhatsAppInbox\Jobs\ProcessWhatsAppWebhookJob::class, function ($job) {
            return $job->eventType === 'status_update';
        });
    }
}
