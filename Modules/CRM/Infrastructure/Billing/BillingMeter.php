<?php

namespace Modules\CRM\Infrastructure\Billing;

use Illuminate\Support\Facades\Redis;
use Modules\CRM\Infrastructure\Context\TenantContext;

class BillingMeter
{
    /**
     * Record usage for a specific metered feature.
     * Uses Redis INCR for extreme speed and thread safety, preventing DB locks during high throughput webhooks.
     * 
     * @param TenantContext $context
     * @param string $featureKey The metered feature (e.g. 'whatsapp_messages')
     * @param int $amount Amount to increment
     */
    public static function record(TenantContext $context, string $featureKey, int $amount = 1): void
    {
        $tenantId = $context->getTenantId();
        
        if (!$tenantId) {
            // Cannot meter without a tenant
            return;
        }

        $redisKey = "metering:tenant:{$tenantId}:{$featureKey}";
        
        // Increment the usage. This is atomic and O(1) in Redis.
        Redis::incrby($redisKey, $amount);

        // Track that this key has been modified so the sync command knows what to flush
        Redis::sadd('metering:dirty_keys', $redisKey);
    }
}
