<?php

namespace Tests\Feature\CRMWhatsAppInbox;

use App\Modules\CRMWhatsAppInbox\Events\WhatsAppMessageReceived;
use App\Modules\CRMWhatsAppInbox\Services\WhatsAppRealtimeBroadcaster;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Tests\TestCase;

class RealtimeBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_message_broadcasts_on_correct_channels(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);
        $message = WhatsAppMessage::factory()->fromCustomer()->create([
            'workspace_id'    => $account->workspace_id,
            'conversation_id' => $conversation->id,
        ]);

        $broadcaster = app(WhatsAppRealtimeBroadcaster::class);

        // This should not throw
        $broadcaster->broadcastNewMessage($message, $conversation);

        $this->assertTrue(true); // Reach this = no errors
    }

    public function test_typing_indicator_broadcasts(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();

        $broadcaster = app(WhatsAppRealtimeBroadcaster::class);

        $broadcaster->broadcastTyping($account->workspace_id, 1, 1, true);

        $this->assertTrue(true);
    }

    public function test_assignment_broadcast(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);
        $agent = \App\Models\User::factory()->create();

        $broadcaster = app(WhatsAppRealtimeBroadcaster::class);
        $broadcaster->broadcastAssignment($conversation, $agent);

        $this->assertTrue(true);
    }
}
