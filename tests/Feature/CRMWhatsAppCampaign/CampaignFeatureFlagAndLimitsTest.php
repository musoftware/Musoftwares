<?php

namespace Tests\Feature\CRMWhatsAppCampaign;

use Tests\TestCase;
use App\Modules\CRMWhatsAppCampaigns\Services\CRMWhatsAppCampaignLimitsService;
use App\Modules\CRMWhatsAppCampaigns\Services\WhatsAppCampaignService;
use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\DB;

class CampaignFeatureFlagAndLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); Event::fake(); Queue::fake(); }

    public function test_default_limits_applied(): void
    {
        $service = new CRMWhatsAppCampaignLimitsService();

        $this->assertTrue($service->canUse(1, 'max_active_campaigns', 1));
        $this->assertTrue($service->canUse(1, 'monthly_whatsapp_campaign_messages', 1));
    }

    public function test_limit_enforced_when_exceeded(): void
    {
        $service = new CRMWhatsAppCampaignLimitsService();

        // Set a custom low limit
        DB::table('tenant_usages')->insert([
            'tenant_id' => 1, 'usage_key' => 'monthly_whatsapp_campaign_messages',
            'used_amount' => 5000, 'limit_amount' => 5000,
            'reset_frequency' => 'monthly', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertFalse($service->canUse(1, 'monthly_whatsapp_campaign_messages', 1));
    }

    public function test_unlimited_when_limit_is_negative_one(): void
    {
        $service = new CRMWhatsAppCampaignLimitsService();

        DB::table('tenant_usages')->insert([
            'tenant_id' => 1, 'usage_key' => 'max_active_campaigns',
            'used_amount' => 0, 'limit_amount' => -1,
            'reset_frequency' => 'never', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertTrue($service->canUse(1, 'max_active_campaigns', 999));
    }

    public function test_usage_summary_includes_all_keys(): void
    {
        $service = new CRMWhatsAppCampaignLimitsService();
        $summary = $service->getUsageSummary(1);

        $this->assertArrayHasKey('monthly_whatsapp_campaign_messages', $summary);
        $this->assertArrayHasKey('max_active_campaigns', $summary);
        $this->assertArrayHasKey('max_audience_segments', $summary);
        $this->assertArrayHasKey('max_campaign_sequences', $summary);
        $this->assertArrayHasKey('max_connected_whatsapp_accounts', $summary);

        foreach ($summary as $key => $data) {
            $this->assertArrayHasKey('limit', $data);
            $this->assertArrayHasKey('used', $data);
            $this->assertArrayHasKey('remaining', $data);
            $this->assertArrayHasKey('percentage', $data);
        }
    }

    public function test_remaining_usage_accurate(): void
    {
        $service = new CRMWhatsAppCampaignLimitsService();

        $service->increaseUsage(1, 'monthly_whatsapp_campaign_messages', 100);
        $remaining = $service->getRemainingUsage(1, 'monthly_whatsapp_campaign_messages');

        $this->assertEquals(4900, $remaining); // 5000 default - 100
    }

    public function test_monthly_usage_can_be_reset(): void
    {
        $service = new CRMWhatsAppCampaignLimitsService();

        DB::table('tenant_usages')->insert([
            'tenant_id' => 1, 'usage_key' => 'monthly_whatsapp_campaign_messages',
            'used_amount' => 3000, 'limit_amount' => 5000,
            'reset_frequency' => 'monthly', 'last_reset_at' => now()->subMonths(2),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $resetCount = $service->resetMonthlyUsage();
        $this->assertGreaterThanOrEqual(1, $resetCount);
    }
}
