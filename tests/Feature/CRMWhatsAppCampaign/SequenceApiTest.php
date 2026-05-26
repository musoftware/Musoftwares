<?php

namespace Tests\Feature\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Models\User;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignSequence;
use Modules\CRM\Models\WhatsAppCampaignSequenceStep;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SequenceApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected WhatsAppCampaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->campaign = WhatsAppCampaign::factory()->create(['workspace_id' => 1]);
        session(['crm_workspace_id' => 1]);
    }

    public function test_can_list_campaign_sequences(): void
    {
        WhatsAppCampaignSequence::factory()->count(2)->create([
            'workspace_id' => 1, 'campaign_id' => $this->campaign->id,
        ]);

        $response = $this->actingAs($this->user)->getJson(
            route('crm.whatsapp-campaigns.sequences.index', $this->campaign->id)
        );

        $response->assertOk();
        $response->assertJsonCount(2);
    }

    public function test_can_create_sequence(): void
    {
        $response = $this->actingAs($this->user)->postJson(
            route('crm.whatsapp-campaigns.sequences.store', $this->campaign->id),
            ['name' => 'Welcome Flow', 'is_active' => true]
        );

        $response->assertCreated();
        $this->assertDatabaseHas('crm_wa_campaign_sequences', ['name' => 'Welcome Flow']);
    }

    public function test_can_toggle_sequence(): void
    {
        $sequence = WhatsAppCampaignSequence::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $this->campaign->id, 'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            route('crm.whatsapp-campaigns.sequences.toggle', $sequence->id)
        );

        $response->assertOk();
        $this->assertDatabaseHas('crm_wa_campaign_sequences', ['id' => $sequence->id, 'is_active' => false]);
    }

    public function test_can_add_step_to_sequence(): void
    {
        $sequence = WhatsAppCampaignSequence::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $this->campaign->id,
        ]);

        $response = $this->actingAs($this->user)->postJson(
            route('crm.whatsapp-campaigns.sequences.steps.store', $sequence->id),
            [
                'step_order'   => 1,
                'action_type'  => 'send_message',
                'message_body' => 'Hello {{customer_name}}!',
                'message_type' => 'text',
                'delay_minutes' => 0,
            ]
        );

        $response->assertCreated();
    }

    public function test_can_reorder_steps(): void
    {
        $sequence = WhatsAppCampaignSequence::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $this->campaign->id,
        ]);

        $step1 = WhatsAppCampaignSequenceStep::factory()->create(['sequence_id' => $sequence->id, 'step_order' => 1, 'workspace_id' => 1]);
        $step2 = WhatsAppCampaignSequenceStep::factory()->create(['sequence_id' => $sequence->id, 'step_order' => 2, 'workspace_id' => 1]);

        $response = $this->actingAs($this->user)->postJson(
            route('crm.whatsapp-campaigns.sequences.reorder', $sequence->id),
            ['steps' => [['id' => $step1->id, 'step_order' => 2], ['id' => $step2->id, 'step_order' => 1]]]
        );

        $response->assertOk();
        $this->assertDatabaseHas('crm_wa_campaign_sequence_steps', ['id' => $step1->id, 'step_order' => 2]);
    }

    public function test_can_delete_step(): void
    {
        $sequence = WhatsAppCampaignSequence::factory()->create([
            'workspace_id' => 1, 'campaign_id' => $this->campaign->id,
        ]);
        $step = WhatsAppCampaignSequenceStep::factory()->create(['sequence_id' => $sequence->id, 'workspace_id' => 1]);

        $response = $this->actingAs($this->user)->deleteJson(
            route('crm.whatsapp-campaigns.sequences.steps.destroy', $step->id)
        );

        $response->assertNoContent();
    }
}
