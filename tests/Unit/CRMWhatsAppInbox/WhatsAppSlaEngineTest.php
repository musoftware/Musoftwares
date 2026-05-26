<?php

namespace Tests\Unit\CRMWhatsAppInbox;

use App\Modules\CRMWhatsAppInbox\Services\WhatsAppSlaEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppConversation;
use Modules\CRM\Models\WhatsAppSlaPolicy;
use Tests\TestCase;

class WhatsAppSlaEngineTest extends TestCase
{
    use RefreshDatabase;

    protected WhatsAppSlaEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = app(WhatsAppSlaEngine::class);
    }

    public function test_apply_sla_sets_policy_and_due_date(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();

        WhatsAppSlaPolicy::factory()->default()->create([
            'workspace_id'        => $account->workspace_id,
            'first_response_time' => 15, // 15 minutes
        ]);

        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id' => $account->workspace_id,
            'account_id'   => $account->id,
        ]);

        $this->engine->applySla($conversation);

        $conversation->refresh();
        $this->assertNotNull($conversation->sla_policy_id);
        $this->assertNotNull($conversation->sla_due_at);
        $this->assertFalse($conversation->sla_breached);
    }

    public function test_record_first_response(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id'      => $account->workspace_id,
            'account_id'        => $account->id,
            'first_response_at' => null,
        ]);

        $this->engine->recordFirstResponse($conversation);

        $conversation->refresh();
        $this->assertNotNull($conversation->first_response_at);
    }

    public function test_record_first_response_does_not_overwrite(): void
    {
        $firstTime = now()->subMinutes(10);
        $account = WhatsAppAccount::factory()->connected()->create();
        $conversation = WhatsAppConversation::factory()->open()->create([
            'workspace_id'      => $account->workspace_id,
            'account_id'        => $account->id,
            'first_response_at' => $firstTime,
        ]);

        $this->engine->recordFirstResponse($conversation);

        $conversation->refresh();
        $this->assertEquals($firstTime->format('Y-m-d H:i:s'), $conversation->first_response_at->format('Y-m-d H:i:s'));
    }

    public function test_check_all_breaches_marks_overdue_conversations(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();
        $policy = WhatsAppSlaPolicy::factory()->create([
            'workspace_id'        => $account->workspace_id,
            'first_response_time' => 5,
        ]);

        // Create overdue conversation (SLA due 10 minutes ago, no response)
        WhatsAppConversation::factory()->open()->create([
            'workspace_id'      => $account->workspace_id,
            'account_id'        => $account->id,
            'sla_policy_id'     => $policy->id,
            'sla_due_at'        => now()->subMinutes(10),
            'sla_breached'      => false,
            'first_response_at' => null,
        ]);

        $breached = $this->engine->checkAllBreaches();

        $this->assertEquals(1, $breached);
    }

    public function test_compliance_stats_return_correct_data(): void
    {
        $account = WhatsAppAccount::factory()->connected()->create();

        $stats = $this->engine->getComplianceStats($account->workspace_id, 'month');

        $this->assertArrayHasKey('total_conversations', $stats);
        $this->assertArrayHasKey('sla_breached', $stats);
        $this->assertArrayHasKey('compliance_rate', $stats);
        $this->assertArrayHasKey('avg_first_response_min', $stats);
        $this->assertArrayHasKey('avg_resolution_min', $stats);
    }
}
