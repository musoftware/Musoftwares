<?php

namespace Tests\Unit\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Modules\CRMWhatsAppCampaigns\Services\CampaignAnalyticsAggregator;
use Modules\CRM\Models\WhatsAppCampaign;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampaignAnalyticsAggregatorTest extends TestCase
{
    use RefreshDatabase;

    protected CampaignAnalyticsAggregator $aggregator;

    protected function setUp(): void { parent::setUp(); $this->aggregator = app(CampaignAnalyticsAggregator::class); }

    public function test_aggregates_delivery_stats(): void
    {
        $campaign = WhatsAppCampaign::factory()->running()->create(['workspace_id' => 1]);
        WhatsAppCampaignDelivery::factory()->sent()->count(5)->create(['workspace_id' => 1, 'campaign_id' => $campaign->id]);
        WhatsAppCampaignDelivery::factory()->failed()->count(2)->create(['workspace_id' => 1, 'campaign_id' => $campaign->id]);

        $this->aggregator->aggregate($campaign);
        $campaign->refresh();

        $this->assertEquals(5, $campaign->sent_count);
        $this->assertEquals(2, $campaign->failed_count);
    }

    public function test_campaign_detail_returns_rates(): void
    {
        $campaign = WhatsAppCampaign::factory()->create([
            'workspace_id' => 1, 'sent_count' => 100, 'delivered_count' => 90,
            'read_count' => 50, 'replied_count' => 10, 'total_recipients' => 100,
        ]);

        $detail = $this->aggregator->getCampaignDetail($campaign);

        $this->assertEquals(90.0, $detail['delivery_rate']);
        $this->assertArrayHasKey('hourly_trend', $detail);
        $this->assertArrayHasKey('status_breakdown', $detail);
    }

    public function test_overview_returns_period_summary(): void
    {
        WhatsAppCampaign::factory()->count(3)->create(['workspace_id' => 1, 'sent_count' => 100]);
        $overview = $this->aggregator->getOverview(1, 'month');

        $this->assertEquals(3, $overview['total_campaigns']);
        $this->assertEquals(300, $overview['total_sent']);
    }
}
