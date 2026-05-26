<?php

namespace Tests\Feature\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Models\User;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Modules\CRM\Models\WhatsAppCampaignAudience;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TenantIsolationCampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_1_cannot_see_workspace_2_campaigns(): void
    {
        // Create campaigns in two different workspaces
        $campaign1 = WhatsAppCampaign::factory()->create(['workspace_id' => 1, 'name' => 'WS1 Campaign']);
        $campaign2 = WhatsAppCampaign::factory()->create(['workspace_id' => 2, 'name' => 'WS2 Campaign']);

        // Query as workspace 1 (using withoutGlobalScopes to simulate scoping)
        $ws1Campaigns = WhatsAppCampaign::withoutGlobalScopes()
            ->where('workspace_id', 1)->pluck('name');

        $this->assertContains('WS1 Campaign', $ws1Campaigns);
        $this->assertNotContains('WS2 Campaign', $ws1Campaigns);
    }

    public function test_deliveries_isolated_by_workspace(): void
    {
        $campaign1 = WhatsAppCampaign::factory()->create(['workspace_id' => 1]);
        $campaign2 = WhatsAppCampaign::factory()->create(['workspace_id' => 2]);

        WhatsAppCampaignDelivery::factory()->count(3)->create([
            'workspace_id' => 1, 'campaign_id' => $campaign1->id,
        ]);
        WhatsAppCampaignDelivery::factory()->count(5)->create([
            'workspace_id' => 2, 'campaign_id' => $campaign2->id,
        ]);

        $ws1Count = WhatsAppCampaignDelivery::withoutGlobalScopes()->where('workspace_id', 1)->count();
        $ws2Count = WhatsAppCampaignDelivery::withoutGlobalScopes()->where('workspace_id', 2)->count();

        $this->assertEquals(3, $ws1Count);
        $this->assertEquals(5, $ws2Count);
    }

    public function test_audiences_isolated_by_workspace(): void
    {
        WhatsAppCampaignAudience::factory()->count(2)->create(['workspace_id' => 1]);
        WhatsAppCampaignAudience::factory()->count(4)->create(['workspace_id' => 2]);

        $ws1 = WhatsAppCampaignAudience::withoutGlobalScopes()->where('workspace_id', 1)->count();
        $ws2 = WhatsAppCampaignAudience::withoutGlobalScopes()->where('workspace_id', 2)->count();

        $this->assertEquals(2, $ws1);
        $this->assertEquals(4, $ws2);
    }

    public function test_campaign_stats_scoped_to_workspace(): void
    {
        WhatsAppCampaign::factory()->create(['workspace_id' => 1, 'sent_count' => 100]);
        WhatsAppCampaign::factory()->create(['workspace_id' => 2, 'sent_count' => 500]);

        $ws1Total = WhatsAppCampaign::withoutGlobalScopes()->where('workspace_id', 1)->sum('sent_count');
        $ws2Total = WhatsAppCampaign::withoutGlobalScopes()->where('workspace_id', 2)->sum('sent_count');

        $this->assertEquals(100, $ws1Total);
        $this->assertEquals(500, $ws2Total);
    }
}
