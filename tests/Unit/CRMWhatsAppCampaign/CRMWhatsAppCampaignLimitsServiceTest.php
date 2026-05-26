<?php

namespace Tests\Unit\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Modules\CRMWhatsAppCampaigns\Services\CRMWhatsAppCampaignLimitsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CRMWhatsAppCampaignLimitsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CRMWhatsAppCampaignLimitsService $service;

    protected function setUp(): void { parent::setUp(); $this->service = new CRMWhatsAppCampaignLimitsService(); }

    public function test_can_use_within_limit(): void
    {
        $this->assertTrue($this->service->canUse(1, 'max_active_campaigns', 1));
    }

    public function test_increase_usage_tracks_amount(): void
    {
        $this->service->increaseUsage(1, 'monthly_whatsapp_campaign_messages', 10);
        $tracked = DB::table('tenant_usages')->where('tenant_id', 1)->where('usage_key', 'monthly_whatsapp_campaign_messages')->first();

        $this->assertNotNull($tracked);
    }

    public function test_remaining_usage_calculation(): void
    {
        $remaining = $this->service->getRemainingUsage(1, 'monthly_whatsapp_campaign_messages');
        $this->assertEquals(5000, $remaining); // default limit
    }

    public function test_usage_summary_returns_all_limits(): void
    {
        $summary = $this->service->getUsageSummary(1);

        $this->assertArrayHasKey('monthly_whatsapp_campaign_messages', $summary);
        $this->assertArrayHasKey('max_active_campaigns', $summary);
        $this->assertArrayHasKey('max_audience_segments', $summary);
    }
}
