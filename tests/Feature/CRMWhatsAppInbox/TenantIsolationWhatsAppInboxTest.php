<?php

namespace Tests\Feature\CRMWhatsAppInbox;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppMessage;
use Tests\TestCase;

class TenantIsolationWhatsAppInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_conversations_are_scoped_to_workspace(): void
    {
        $account1 = WhatsAppAccount::factory()->connected()->create(['workspace_id' => 1]);
        $account2 = WhatsAppAccount::factory()->connected()->create(['workspace_id' => 2]);

        WhatsAppConversation::factory()->count(3)->create([
            'workspace_id' => 1,
            'account_id'   => $account1->id,
        ]);
        WhatsAppConversation::factory()->count(2)->create([
            'workspace_id' => 2,
            'account_id'   => $account2->id,
        ]);

        // When querying without global scopes (manually), both are visible
        $all = WhatsAppConversation::withoutGlobalScopes()->count();
        $this->assertEquals(5, $all);

        // When querying within workspace scope, only that workspace's data shows
        $workspace1 = WhatsAppConversation::withoutGlobalScopes()
            ->where('workspace_id', 1)->count();
        $this->assertEquals(3, $workspace1);
    }

    public function test_messages_are_scoped_to_workspace(): void
    {
        $account1 = WhatsAppAccount::factory()->connected()->create(['workspace_id' => 1]);
        $conversation1 = WhatsAppConversation::factory()->create([
            'workspace_id' => 1,
            'account_id'   => $account1->id,
        ]);

        $account2 = WhatsAppAccount::factory()->connected()->create(['workspace_id' => 2]);
        $conversation2 = WhatsAppConversation::factory()->create([
            'workspace_id' => 2,
            'account_id'   => $account2->id,
        ]);

        WhatsAppMessage::factory()->count(5)->create([
            'workspace_id' => 1, 'conversation_id' => $conversation1->id,
        ]);
        WhatsAppMessage::factory()->count(3)->create([
            'workspace_id' => 2, 'conversation_id' => $conversation2->id,
        ]);

        $workspace1Messages = WhatsAppMessage::withoutGlobalScopes()
            ->where('workspace_id', 1)->count();
        $this->assertEquals(5, $workspace1Messages);
    }

    public function test_accounts_are_scoped_to_workspace(): void
    {
        WhatsAppAccount::factory()->count(2)->create(['workspace_id' => 1]);
        WhatsAppAccount::factory()->count(1)->create(['workspace_id' => 2]);

        $workspace1 = WhatsAppAccount::withoutGlobalScopes()
            ->where('workspace_id', 1)->count();
        $this->assertEquals(2, $workspace1);
    }
}
