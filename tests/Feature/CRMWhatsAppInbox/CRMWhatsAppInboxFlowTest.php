<?php

namespace Tests\Feature\CRMWhatsAppInbox;

use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Tests\TestCase;

class CRMWhatsAppInboxFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_inbound_message_flow(): void
    {
        Event::fake();
        Queue::fake();

        $account = WhatsAppAccount::factory()->connected()->create();

        // Simulate webhook payload
        $payload = [
            'from'       => '+201234567890',
            'push_name'  => 'Ahmed',
            'message_id' => 'wamid_flow_001',
            'type'       => 'text',
            'body'       => 'I want to buy your product',
        ];

        $service = app(\App\Modules\CRMWhatsAppInbox\Services\WhatsAppInboxService::class);
        $message = $service->processIncomingMessage($account, $payload);

        // Verify the full chain
        $this->assertNotNull($message->id);
        $this->assertNotNull($message->conversation_id);

        $conversation = $message->conversation;
        $this->assertEquals('open', $conversation->status);
        $this->assertEquals('+201234567890', $conversation->contact_phone);
        $this->assertEquals('Ahmed', $conversation->contact_name);
        $this->assertEquals(1, $conversation->unread_count);
        $this->assertNotNull($conversation->last_message_at);

        Event::assertDispatched(WhatsAppMessageReceived::class, function ($event) use ($message) {
            return $event->message->id === $message->id;
        });
    }

    public function test_second_message_reuses_existing_conversation(): void
    {
        Event::fake();

        $account = WhatsAppAccount::factory()->connected()->create();

        $service = app(\App\Modules\CRMWhatsAppInbox\Services\WhatsAppInboxService::class);

        // First message
        $msg1 = $service->processIncomingMessage($account, [
            'from' => '+201111111111', 'type' => 'text', 'body' => 'Hello',
        ]);

        // Second message from same number
        $msg2 = $service->processIncomingMessage($account, [
            'from' => '+201111111111', 'type' => 'text', 'body' => 'Any update?',
        ]);

        $this->assertEquals($msg1->conversation_id, $msg2->conversation_id);
    }
}
