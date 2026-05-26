<?php

namespace Tests\Feature\CRMWhatsAppInbox;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use App\Models\User;
use Tests\TestCase;

class ConversationApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WhatsAppAccount $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->connected()->create();

        // Simulate session context for workspace
        session(['crm_workspace_id' => $this->account->workspace_id]);
    }

    public function test_conversation_show_returns_messages(): void
    {
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $this->account->workspace_id,
            'account_id'   => $this->account->id,
        ]);

        \Modules\CRM\Models\WhatsAppMessage::factory()->count(5)->create([
            'workspace_id'    => $this->account->workspace_id,
            'conversation_id' => $conversation->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/crm/whatsapp/conversations/{$conversation->id}");

        $response->assertOk();
        $response->assertJsonStructure([
            'conversation' => ['id', 'uuid', 'contact_phone', 'status'],
            'messages',
        ]);
    }

    public function test_assign_conversation_to_agent(): void
    {
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $this->account->workspace_id,
            'account_id'   => $this->account->id,
        ]);
        $agent = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/crm/whatsapp/conversations/{$conversation->id}/assign", [
                'agent_id' => $agent->id,
                'reason'   => 'Best fit for this customer',
            ]);

        $response->assertOk();
        $conversation->refresh();
        $this->assertEquals($agent->id, $conversation->assigned_agent_id);
    }

    public function test_resolve_conversation(): void
    {
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $this->account->workspace_id,
            'account_id'   => $this->account->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/crm/whatsapp/conversations/{$conversation->id}/resolve");

        $response->assertOk();
        $conversation->refresh();
        $this->assertEquals('resolved', $conversation->status);
    }

    public function test_toggle_pin_conversation(): void
    {
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $this->account->workspace_id,
            'account_id'   => $this->account->id,
            'is_pinned'    => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/crm/whatsapp/conversations/{$conversation->id}/pin");

        $response->assertOk();
        $response->assertJson(['is_pinned' => true]);
    }
}
