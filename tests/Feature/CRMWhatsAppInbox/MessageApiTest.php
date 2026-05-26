<?php

namespace Tests\Feature\CRMWhatsAppInbox;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use App\Models\User;
use Tests\TestCase;

class MessageApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WhatsAppAccount $account;
    protected WhatsAppConversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->account = WhatsAppAccount::factory()->connected()->create();
        $this->conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $this->account->workspace_id,
            'account_id'   => $this->account->id,
        ]);
        session(['crm_workspace_id' => $this->account->workspace_id]);
    }

    public function test_send_text_message(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user)
            ->postJson("/crm/whatsapp/conversations/{$this->conversation->id}/messages", [
                'body' => 'Thank you for your interest!',
                'type' => 'text',
            ]);

        $response->assertCreated();
        $response->assertJsonStructure(['message' => ['id', 'uuid', 'body', 'delivery_status']]);

        $this->assertDatabaseHas('crm_whatsapp_messages', [
            'conversation_id' => $this->conversation->id,
            'body'            => 'Thank you for your interest!',
            'sender_type'     => 'agent',
        ]);
    }

    public function test_add_internal_note(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/crm/whatsapp/conversations/{$this->conversation->id}/notes", [
                'body' => 'Customer seems very interested in premium plan',
            ]);

        $response->assertCreated();

        $this->assertDatabaseHas('crm_whatsapp_messages', [
            'conversation_id'  => $this->conversation->id,
            'is_internal_note' => true,
        ]);
    }

    public function test_toggle_star_message(): void
    {
        $message = WhatsAppMessage::factory()->create([
            'workspace_id'    => $this->account->workspace_id,
            'conversation_id' => $this->conversation->id,
            'is_starred'      => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/crm/whatsapp/messages/{$message->id}/star");

        $response->assertOk();
        $response->assertJson(['is_starred' => true]);
    }

    public function test_get_messages_list(): void
    {
        WhatsAppMessage::factory()->count(10)->create([
            'workspace_id'    => $this->account->workspace_id,
            'conversation_id' => $this->conversation->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/crm/whatsapp/conversations/{$this->conversation->id}/messages");

        $response->assertOk();
    }
}
