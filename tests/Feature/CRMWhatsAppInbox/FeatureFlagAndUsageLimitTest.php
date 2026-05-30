<?php

namespace Tests\Feature\CRMWhatsAppInbox;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\CRMWhatsAppLimitsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\CRM\Models\WhatsAppAccount;
use Tests\TestCase;

class FeatureFlagAndUsageLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_limits_service_tracks_usage(): void
    {
        $service = app(CRMWhatsAppLimitsService::class);
        $workspaceId = 1;

        // Initialize usage record
        DB::table('tenant_usages')->insert([
            'tenant_id'       => $workspaceId,
            'usage_key'       => 'monthly_whatsapp_messages',
            'limit_amount'    => 100,
            'used_amount'     => 0,
            'reset_frequency' => 'monthly',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Check can use
        $this->assertTrue($service->canUse($workspaceId, 'monthly_whatsapp_messages'));

        // Increase usage
        $service->increaseUsage($workspaceId, 'monthly_whatsapp_messages', 50);

        $remaining = $service->getRemainingUsage($workspaceId, 'monthly_whatsapp_messages');
        $this->assertEquals(50, $remaining);
    }

    public function test_limits_service_blocks_when_exceeded(): void
    {
        $service = app(CRMWhatsAppLimitsService::class);
        $workspaceId = 1;

        DB::table('tenant_usages')->insert([
            'tenant_id'       => $workspaceId,
            'usage_key'       => 'monthly_whatsapp_messages',
            'limit_amount'    => 10,
            'used_amount'     => 10,
            'reset_frequency' => 'monthly',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $this->assertFalse($service->canUse($workspaceId, 'monthly_whatsapp_messages'));
    }

    public function test_usage_summary_returns_all_keys(): void
    {
        $service = app(CRMWhatsAppLimitsService::class);
        $summary = $service->getUsageSummary(1);

        $this->assertArrayHasKey('max_connected_whatsapp_accounts', $summary);
        $this->assertArrayHasKey('monthly_whatsapp_messages', $summary);
        $this->assertArrayHasKey('max_team_members', $summary);
        $this->assertArrayHasKey('max_active_conversations', $summary);
        $this->assertArrayHasKey('max_automation_rules', $summary);
    }

    public function test_monthly_reset_clears_usage(): void
    {
        $service = app(CRMWhatsAppLimitsService::class);
        $workspaceId = 1;

        DB::table('tenant_usages')->insert([
            'tenant_id'       => $workspaceId,
            'usage_key'       => 'monthly_whatsapp_messages',
            'limit_amount'    => 100,
            'used_amount'     => 75,
            'reset_frequency' => 'monthly',
            'last_reset_at'   => now()->subMonth(),
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $resetCount = $service->resetMonthlyUsage();
        $this->assertEquals(1, $resetCount);

        // Usage should be 0 now
        $used = DB::table('tenant_usages')
            ->where('tenant_id', $workspaceId)
            ->where('usage_key', 'monthly_whatsapp_messages')
            ->value('used_amount');
        $this->assertEquals(0, $used);
    }
}
