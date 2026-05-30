<?php

namespace Tests\Unit\CRMWhatsAppInbox;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Contracts\WhatsAppProviderInterface;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppInboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Tests\TestCase;

class WhatsAppInboxServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WhatsAppInboxService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WhatsAppInboxService::class);
    }

    public function test_process_incoming_message_creates_conversation_and_message(): void
    {
        Event::fake();

        $account = WhatsAppAccount::factory()->connected()->create();

        $payload = [
            'from'       => '+201234567890',
            'push_name'  => 'John Doe',
            'message_id' => 'wamid_123',
            'type'       => 'text',
            'body'       => 'Hello, I need help!',
        ];

        $message = $this->service->processIncomingMessage($account, $payload);

        $this->assertInstanceOf(WhatsAppMessage::class, $message);
        $this->assertEquals('customer', $message->sender_type);
        $this->assertEquals('text', $message->type);
        $this->assertEquals('Hello, I need help!', $message->body);
        $this->assertEquals('delivered', $message->delivery_status);

        // Verify conversation was created
        $this->assertDatabaseHas('crm_whatsapp_conversations', [
            'workspace_id'  => $account->workspace_id,
            'account_id'    => $account->id,
            'contact_phone' => '+201234567890',
            'contact_name'  => 'John Doe',
            'status'        => 'open',
        ]);

        Event::assertDispatched(WhatsAppMessageReceived::class);
    }

    public function test_resolve_conversation_returns_existing_open_conversation(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $existing = WhatsAppConversation::factory()->open()->create([
            'workspace_id'  => $account->workspace_id,
            'account_id'    => $account->id,
            'contact_phone' => '+201111111111',
        ]);

        $resolved = $this->service->resolveConversation($account, '+201111111111');

        $this->assertEquals($existing->id, $resolved->id);
    }

    public function test_resolve_conversation_creates_new_for_unknown_contact(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();

        $conversation = $this->service->resolveConversation($account, '+209999999999', 'New User');

        $this->assertNotNull($conversation->id);
        $this->assertEquals('+209999999999', $conversation->contact_phone);
        $this->assertEquals('New User', $conversation->contact_name);
        $this->assertEquals('open', $conversation->status);
    }

    public function test_resolve_conversation_reopens_resolved_conversation_on_new_message(): void
    {
        Event::fake();

        $account = WhatsAppAccount::factory()->connected()->create();
        WhatsAppConversation::factory()->resolved()->create([
            'workspace_id'  => $account->workspace_id,
            'account_id'    => $account->id,
            'contact_phone' => '+201111111111',
        ]);

        $message = $this->service->processIncomingMessage($account, [
            'from'       => '+201111111111',
            'type'       => 'text',
            'body'       => 'Hi again',
            'message_id' => 'wamid_456',
        ]);

        $conversation = $message->conversation->fresh();
        // A new conversation should be created since the old one was resolved
        $this->assertEquals('open', $conversation->status);
    }

    public function test_get_inbox_returns_filtered_conversations(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $workspaceId = $account->workspace_id;

        WhatsAppConversation::factory()->count(3)->open()->create([
            'workspace_id' => $workspaceId,
            'account_id'   => $account->id,
        ]);
        WhatsAppConversation::factory()->count(2)->resolved()->create([
            'workspace_id' => $workspaceId,
            'account_id'   => $account->id,
        ]);

        $result = $this->service->getInbox($workspaceId, ['status' => 'open']);

        $this->assertEquals(3, $result->total());
    }

    public function test_match_lead_links_conversation_to_existing_lead(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->create([
            'workspace_id'  => $account->workspace_id,
            'account_id'    => $account->id,
            'contact_phone' => '+201234567890',
        ]);

        // Create a lead with matching phone
        \Modules\CRM\Models\Lead::factory()->create([
            'workspace_id' => $account->workspace_id,
            'phone'        => '+201234567890',
        ]);

        $this->service->matchLeadOrCustomer($conversation, '+201234567890');
        $conversation->refresh();

        $this->assertNotNull($conversation->lead_id);
        $this->assertEquals('lead', $conversation->type);
    }
}
