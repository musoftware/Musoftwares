<?php

namespace Tests\Unit\CRMWhatsAppInbox;

use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageSent;
use App\Modules\CRMWhatsAppInbox\Exceptions\UsageLimitExceededException;
use App\Modules\CRMWhatsAppInbox\Services\MessageDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use App\Models\User;
use Tests\TestCase;

class MessageDeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MessageDeliveryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MessageDeliveryService::class);
    }

    public function test_send_text_creates_message_and_dispatches_job(): void
    {
        Queue::fake();

        $account = WhatsAppAccount::factory()->connected()->create();
        $agent = User::factory()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);

        $message = $this->service->sendText($conversation, 'Hello there!', $agent->id);

        $this->assertInstanceOf(WhatsAppMessage::class, $message);
        $this->assertEquals('agent', $message->sender_type);
        $this->assertEquals($agent->id, $message->sender_id);
        $this->assertEquals('text', $message->type);
        $this->assertEquals('Hello there!', $message->body);
        $this->assertEquals('pending', $message->delivery_status);

        Queue::assertPushed(\App\Modules\CRMWhatsAppInbox\Jobs\SendWhatsAppMessageJob::class);
    }

    public function test_add_internal_note_is_not_sent_via_whatsapp(): void
    {
        Queue::fake();

        $account = WhatsAppAccount::factory()->connected()->create();
        $agent = User::factory()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);

        $note = $this->service->addInternalNote($conversation, 'Customer seems interested', $agent->id);

        $this->assertTrue($note->is_internal_note);
        $this->assertEquals('delivered', $note->delivery_status);

        // Internal notes should NOT be sent via WhatsApp
        Queue::assertNotPushed(\App\Modules\CRMWhatsAppInbox\Jobs\SendWhatsAppMessageJob::class);
    }

    public function test_mark_as_sent_updates_status_and_fires_event(): void
    {
        Event::fake();

        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);
        $message = WhatsAppMessage::factory()->create([
            'workspace_id'    => $account->workspace_id,
            'conversation_id' => $conversation->id,
            'delivery_status' => 'pending',
        ]);

        $this->service->markAsSent($message, 'wamid_789');

        $message->refresh();
        $this->assertEquals('sent', $message->delivery_status);
        $this->assertEquals('wamid_789', $message->whatsapp_message_id);
        $this->assertNotNull($message->sent_at);

        Event::assertDispatched(WhatsAppMessageSent::class);
    }

    public function test_mark_as_failed_updates_status_and_fires_event(): void
    {
        Event::fake();

        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);
        $message = WhatsAppMessage::factory()->create([
            'workspace_id'    => $account->workspace_id,
            'conversation_id' => $conversation->id,
            'delivery_status' => 'pending',
        ]);

        $this->service->markAsFailed($message, 'Connection timeout');

        $message->refresh();
        $this->assertEquals('failed', $message->delivery_status);
        $this->assertEquals('Connection timeout', $message->failed_reason);

        Event::assertDispatched(\App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageFailed::class);
    }
}
