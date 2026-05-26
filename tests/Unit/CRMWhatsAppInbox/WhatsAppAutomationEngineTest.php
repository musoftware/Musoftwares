<?php

namespace Tests\Unit\CRMWhatsAppInbox;

use App\Modules\CRMWhatsAppInbox\Services\WhatsAppAutomationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppAutomationRule;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Tests\TestCase;

class WhatsAppAutomationEngineTest extends TestCase
{
    use RefreshDatabase;

    protected WhatsAppAutomationEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(WhatsAppAutomationEngine::class);
    }

    public function test_auto_reply_fires_on_first_message(): void
    {
        Queue::fake();

        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);
        $message = WhatsAppMessage::factory()->fromCustomer()->create([
            'workspace_id'    => $account->workspace_id,
            'conversation_id' => $conversation->id,
        ]);

        // Create auto-reply rule
        WhatsAppAutomationRule::factory()->create([
            'workspace_id'  => $account->workspace_id,
            'type'          => 'auto_reply',
            'trigger_event' => 'message.received',
            'conditions'    => [['field' => 'is_first_message', 'operator' => 'is_true', 'value' => true]],
            'actions'       => [['type' => 'send_reply', 'message' => 'Welcome! We will get back to you soon.']],
        ]);

        $this->engine->evaluate($conversation, 'message.received', $message);

        // The auto-reply should create a new message
        $this->assertDatabaseHas('crm_whatsapp_messages', [
            'conversation_id' => $conversation->id,
            'sender_type'     => 'agent',
            'body'            => 'Welcome! We will get back to you soon.',
        ]);
    }

    public function test_inactive_rules_are_skipped(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);

        WhatsAppAutomationRule::factory()->inactive()->create([
            'workspace_id'  => $account->workspace_id,
            'trigger_event' => 'message.received',
        ]);

        $this->engine->evaluate($conversation, 'message.received');

        // No new messages should be created
        $this->assertEquals(0, WhatsAppMessage::where('conversation_id', $conversation->id)->count());
    }

    public function test_set_priority_action_updates_conversation(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
            'priority'     => 'low',
        ]);
        $message = WhatsAppMessage::factory()->fromCustomer()->create([
            'workspace_id'    => $account->workspace_id,
            'conversation_id' => $conversation->id,
            'body'            => 'URGENT: I need help now!',
        ]);

        WhatsAppAutomationRule::factory()->create([
            'workspace_id'  => $account->workspace_id,
            'trigger_event' => 'message.received',
            'conditions'    => [['field' => 'message.body', 'operator' => 'contains', 'value' => 'URGENT']],
            'actions'       => [['type' => 'set_priority', 'priority' => 'urgent']],
        ]);

        $this->engine->evaluate($conversation, 'message.received', $message);

        $conversation->refresh();
        $this->assertEquals('urgent', $conversation->priority);
    }

    public function test_trigger_count_increments_on_match(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);

        $rule = WhatsAppAutomationRule::factory()->create([
            'workspace_id'  => $account->workspace_id,
            'trigger_event' => 'message.received',
            'conditions'    => [], // No conditions = always match
            'actions'       => [['type' => 'add_note', 'note' => 'Auto-processed']],
            'trigger_count' => 0,
        ]);

        $this->engine->evaluate($conversation, 'message.received');

        $rule->refresh();
        $this->assertEquals(1, $rule->trigger_count);
        $this->assertNotNull($rule->last_triggered_at);
    }
}
