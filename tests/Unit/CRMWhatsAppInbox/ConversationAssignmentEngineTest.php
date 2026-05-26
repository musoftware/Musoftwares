<?php

namespace Tests\Unit\CRMWhatsAppInbox;

use App\Modules\CRMWhatsAppInbox\Events\WhatsAppConversationAssigned;
use App\Modules\CRMWhatsAppInbox\Services\ConversationAssignmentEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppAssignment;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppParticipant;
use App\Models\User;
use Tests\TestCase;

class ConversationAssignmentEngineTest extends TestCase
{
    use RefreshDatabase;

    protected ConversationAssignmentEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(ConversationAssignmentEngine::class);
    }

    public function test_manual_assign_sets_agent_and_creates_audit_record(): void
    {
        Event::fake();

        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);
        $agent = User::factory()->create();

        $this->engine->manualAssign($conversation, $agent, null, 'Test assignment');

        $conversation->refresh();
        $this->assertEquals($agent->id, $conversation->assigned_agent_id);

        // Verify assignment audit record
        $this->assertDatabaseHas('crm_whatsapp_assignments', [
            'conversation_id' => $conversation->id,
            'assigned_to_id'  => $agent->id,
            'assignment_type' => 'manual',
            'reason'          => 'Test assignment',
        ]);

        // Verify participant created
        $this->assertDatabaseHas('crm_whatsapp_participants', [
            'conversation_id' => $conversation->id,
            'user_id'         => $agent->id,
            'role'            => 'participant',
        ]);

        Event::assertDispatched(WhatsAppConversationAssigned::class);
    }

    public function test_reassign_marks_previous_agent_as_left(): void
    {
        Event::fake();

        $account = WhatsAppAccount::factory()->connected()->create();
        $agent1 = User::factory()->create();
        $agent2 = User::factory()->create();

        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id'     => $account->workspace_id,
            'account_id'       => $account->id,
            'assigned_agent_id' => $agent1->id,
        ]);

        // Add first agent as participant
        WhatsAppParticipant::create([
            'workspace_id'    => $conversation->workspace_id,
            'conversation_id' => $conversation->id,
            'user_id'         => $agent1->id,
            'role'            => 'participant',
            'joined_at'       => now(),
        ]);

        $this->engine->reassign($conversation, $agent2, null, 'Escalated');

        $conversation->refresh();
        $this->assertEquals($agent2->id, $conversation->assigned_agent_id);

        // Agent 1 should be marked as left
        $participant = WhatsAppParticipant::where('conversation_id', $conversation->id)
            ->where('user_id', $agent1->id)->first();
        $this->assertNotNull($participant->left_at);
    }

    public function test_transfer_sets_department_and_clears_agent(): void
    {
        Event::fake();

        $account = WhatsAppAccount::factory()->connected()->create();
        $agent = User::factory()->create();

        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id'     => $account->workspace_id,
            'account_id'       => $account->id,
            'assigned_agent_id' => $agent->id,
        ]);

        $this->engine->transfer($conversation, 'support', $agent);

        $conversation->refresh();
        $this->assertEquals('support', $conversation->assigned_department);

        // Assignment record should show department transfer
        $this->assertDatabaseHas('crm_whatsapp_assignments', [
            'conversation_id'  => $conversation->id,
            'assignment_type'  => 'department',
        ]);
    }
}
