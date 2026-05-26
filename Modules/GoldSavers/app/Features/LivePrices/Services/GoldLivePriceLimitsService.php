<?php

namespace Modules\GoldSavers\app\Features\LivePrices\Services;

use App\Services\MeteredBillingService;

/**
 * Limits service for gold.live_prices feature.
 * Gate: feature('gold.live_prices') + metered usage limits.
 */
class GoldLivePriceLimitsService
{
    public function __construct(
        protected MeteredBillingService $billing
    ) {}

    // ─── Feature Gate ────────────────────────────────────────────────────────────

    /**
     * Check if the tenant has access to the gold.live_prices feature.
     */
    public function canUse(int $tenantId): bool
    {
        return feature('gold.live_prices', $tenantId);
    }

    // ─── Metered Usage Limits ───────────────────────────────────────────────────

    /**
     * Can the tenant trigger a manual price refresh?
     */
    public function canRefresh(): bool
    {
        return $this->billing->canUse('max_price_refreshes');
    }

    /**
     * Can the tenant open another realtime WS subscription?
     */
    public function canSubscribeRealtime(): bool
    {
        return $this->billing->canUse('max_realtime_subscriptions');
    }

    /**
     * Can the tenant make a historical data request?
     */
    public function canRequestHistorical(): bool
    {
        return $this->billing->canUse('max_historical_requests');
    }

    /**
     * Can the tenant create another watchlist?
     */
    public function canCreateWatchlist(): bool
    {
        return $this->billing->canUse('max_watchlists');
    }

    // ─── Usage Tracking ─────────────────────────────────────────────────────────

    public function increaseUsage(string $key): void
    {
        $this->billing->incrementUsage($key);
    }

    public function getRemainingUsage(string $key): ?int
    {
        // Reach into the TenantUsage model to compute remaining
        $tenantId = $this->resolveTenantId();
        $usage    = \App\Models\TenantUsage::where('tenant_id', $tenantId)
                        ->where('usage_key', $key)
                        ->first();

        if (!$usage) return null;
        if ($usage->isUnlimited()) return PHP_INT_MAX;

        return max(0, $usage->limit_amount - $usage->used_amount);
    }

    // ─── Internal ───────────────────────────────────────────────────────────────

    protected function resolveTenantId(): int
    {
        if (app()->bound('currentTenant') && app('currentTenant')) {
            return app('currentTenant')->id;
        }
        if (auth()->check() && auth()->user()->tenant_id) {
            return auth()->user()->tenant_id;
        }
        throw new \RuntimeException('Cannot resolve tenant for GoldLivePriceLimitsService.');
    }
}
