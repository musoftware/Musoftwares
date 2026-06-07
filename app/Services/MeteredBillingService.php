<?php

namespace App\Services;

use App\Models\TenantUsage;
use App\Exceptions\SaaSLimitExceededException;
use App\Events\SaaSLimitApproaching;
use App\Events\SaaSLimitReached;
use Illuminate\Support\Facades\DB;

class MeteredBillingService
{
    /**
     * Resolves the current tenant ID.
     */
    protected function getTenantId(): int
    {
        if (app()->bound('currentTenant') && app('currentTenant')) {
            return app('currentTenant')->id;
        }

        if (auth()->check()) {
            return auth()->id();
        }

        if (app()->environment('testing')) {
            return 1;
        }

        throw new \Exception("Cannot resolve tenant for SaaS metering.");
    }

    /**
     * Finds or creates a usage tracker for the tenant.
     * In a full implementation, the limit_amount would be pulled dynamically from saas.php 
     * based on the tenant's active subscriptions. For now, we allow dynamic seeding.
     */
    protected function getUsage(string $usageKey, ?int $defaultLimit = null, string $resetFrequency = 'monthly'): TenantUsage
    {
        $tenantId = $this->getTenantId();

        return TenantUsage::firstOrCreate(
            ['tenant_id' => $tenantId, 'usage_key' => $usageKey],
            [
                'used_amount' => 0,
                'limit_amount' => $defaultLimit,
                'reset_frequency' => $resetFrequency,
            ]
        );
    }

    /**
     * Checks if the tenant can consume $amount of $usageKey.
     */
    public function canUse(string $usageKey, int $amount = 1): bool
    {
        $usage = $this->getUsage($usageKey);
        
        return $usage->hasAvailable($amount);
    }

    /**
     * Enforces the limit. Throws an exception if exceeded.
     */
    public function enforce(string $usageKey, int $amount = 1): void
    {
        if (!$this->canUse($usageKey, $amount)) {
            $usage = $this->getUsage($usageKey);
            event(new SaaSLimitReached($usage));
            throw new SaaSLimitExceededException($usageKey);
        }
    }

    /**
     * Increments the usage by $amount.
     */
    public function incrementUsage(string $usageKey, int $amount = 1): TenantUsage
    {
        // Enforce before incrementing
        $this->enforce($usageKey, $amount);

        $tenantId = $this->getTenantId();

        // Atomic update to prevent race conditions
        DB::table('tenant_usages')
            ->where('tenant_id', $tenantId)
            ->where('usage_key', $usageKey)
            ->increment('used_amount', $amount);

        $usage = TenantUsage::where('tenant_id', $tenantId)
            ->where('usage_key', $usageKey)
            ->first();

        // Check if we hit the 80% threshold for warnings
        if (!$usage->isUnlimited()) {
            $percentage = $usage->getPercentageUsed();
            if ($percentage >= 80 && $percentage < 100) {
                // If it wasn't already >= 80 before this increment, we fire it.
                // A more robust system tracks if the notification was already sent this cycle.
                event(new SaaSLimitApproaching($usage, $percentage));
            } elseif ($percentage >= 100) {
                event(new SaaSLimitReached($usage));
            }
        }

        return $usage;
    }
}
