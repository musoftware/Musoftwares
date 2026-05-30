<?php

namespace Modules\CRM\Tests\Support;

use App\Models\UserSubscription;

trait BillingTestFixtures
{
    /**
     * Grant a specific capability/addon entitlement to the current test workspace's owner.
     *
     * @param string $capability e.g., 'crm-wa-campaigns'
     * @param array $limits Optional limits configuration
     */
    protected function grantEntitlement(string $capability, array $limits = []): void
    {
        // Assuming $this->adminUser is available via BaseTenantTestCase
        UserSubscription::factory()->create([
            'user_id' => $this->adminUser->id,
            'object' => $capability,
            'status' => 'active',
            'expires_at' => now()->addDays(30),
            'meta' => $limits,
        ]);
        
        // Flush any resolved entitlements cached in the engine
        app(\Modules\CRM\Infrastructure\Capabilities\EntitlementEngine::class)->flush();
    }

    /**
     * Revoke a specific entitlement to simulate expiration or downgrade.
     */
    protected function revokeEntitlement(string $capability): void
    {
        UserSubscription::where('user_id', $this->adminUser->id)
            ->where('object', $capability)
            ->update(['status' => 'expired', 'expires_at' => now()->subDay()]);
            
        app(\Modules\CRM\Infrastructure\Capabilities\EntitlementEngine::class)->flush();
    }

    /**
     * Simulate reaching an overage/quota limit for a metered capability.
     */
    protected function simulateQuotaExceeded(string $meterKey, int $limit): void
    {
        // Force the Redis meter to the absolute limit
        $tenantId = $this->workspace->id;
        $redisKey = "metering:tenant:{$tenantId}:{$meterKey}";
        
        \Illuminate\Support\Facades\Redis::set($redisKey, $limit);
    }
}
