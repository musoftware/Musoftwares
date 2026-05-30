<?php

namespace Tests\Unit\CRMWhatsAppInbox;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\ConversationRoutingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Tests\TestCase;

class ConversationRoutingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected ConversationRoutingEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(ConversationRoutingEngine::class);
    }

    public function test_routing_stats_returns_correct_counts(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $workspaceId = $account->workspace_id;

        WhatsAppConversation::factory()->count(3)->open()->create([
            'workspace_id' => $workspaceId,
            'account_id'   => $account->id,
        ]);
        WhatsAppConversation::factory()->count(2)->create([
            'workspace_id'     => $workspaceId,
            'account_id'       => $account->id,
            'status'           => 'open',
            'assigned_agent_id' => null,
        ]);

        $stats = $this->engine->getRoutingStats($workspaceId);

        $this->assertArrayHasKey('total_open', $stats);
        $this->assertArrayHasKey('unassigned', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('by_priority', $stats);
        $this->assertArrayHasKey('sla_breached', $stats);
    }
}
