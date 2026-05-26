<?php

namespace Tests\Feature\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignSequenceEngine;
use App\Modules\CRMWhatsAppCampaigns\Services\WhatsAppTemplateRenderer;
use App\Modules\CRMWhatsAppCampaigns\Jobs\ProcessSequenceStepJob;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Modules\CRM\Models\WhatsAppCampaignSequence;
use Modules\CRM\Models\WhatsAppCampaignSequenceStep;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

class CampaignSequenceFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
    }

    public function test_sequence_starts_with_first_step(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);
        $sequence = WhatsAppCampaignSequence::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id,
        ]);
        WhatsAppCampaignSequenceStep::factory()->create([
            'workspace_id' => 1, 'sequence_id' => $sequence->id,
            'step_order' => 1, 'action_type' => 'send_message',
        ]);
        $delivery = WhatsAppCampaignDelivery::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id, 'status' => 'pending',
        ]);

        $engine = new CampaignSequenceEngine(new WhatsAppTemplateRenderer());
        $engine->startSequence($campaign, $sequence);

        Queue::assertPushed(ProcessSequenceStepJob::class);
    }

    public function test_skip_step_if_replied(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);
        $sequence = WhatsAppCampaignSequence::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id,
        ]);
        $step1 = WhatsAppCampaignSequenceStep::factory()->create([
            'workspace_id' => 1, 'sequence_id' => $sequence->id,
            'step_order' => 1, 'action_type' => 'send_message', 'skip_if_replied' => true,
        ]);
        WhatsAppCampaignSequenceStep::factory()->create([
            'workspace_id' => 1, 'sequence_id' => $sequence->id,
            'step_order' => 2, 'action_type' => 'send_message',
        ]);

        $delivery = WhatsAppCampaignDelivery::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id,
            'status' => 'pending', 'has_replied' => true,
        ]);

        $engine = new CampaignSequenceEngine(new WhatsAppTemplateRenderer());
        $engine->executeStep($delivery, $step1);

        // Should advance to step 2, not send step 1
        Queue::assertPushed(ProcessSequenceStepJob::class);
    }

    public function test_exit_sequence_on_opt_out(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);
        $sequence = WhatsAppCampaignSequence::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id,
            'exit_conditions' => [['field' => 'opted_out']],
        ]);
        $step = WhatsAppCampaignSequenceStep::factory()->create([
            'workspace_id' => 1, 'sequence_id' => $sequence->id,
            'step_order' => 1, 'action_type' => 'send_message',
        ]);

        $delivery = WhatsAppCampaignDelivery::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id,
            'status' => 'pending', 'has_opted_out' => true,
        ]);

        $engine = new CampaignSequenceEngine(new WhatsAppTemplateRenderer());
        $engine->executeStep($delivery, $step);

        $this->assertEquals('skipped', $delivery->fresh()->status);
    }

    public function test_wait_step_dispatches_with_delay(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);
        $sequence = WhatsAppCampaignSequence::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id,
        ]);
        $step = WhatsAppCampaignSequenceStep::factory()->wait(60)->create([
            'workspace_id' => 1, 'sequence_id' => $sequence->id, 'step_order' => 1,
        ]);

        $delivery = WhatsAppCampaignDelivery::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $campaign->id, 'status' => 'pending',
        ]);

        $engine = new CampaignSequenceEngine(new WhatsAppTemplateRenderer());
        $engine->scheduleStep($delivery, $step);

        Queue::assertPushed(ProcessSequenceStepJob::class, function ($job) {
            return $job->delay !== null;
        });
    }
}
