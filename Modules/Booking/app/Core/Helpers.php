<?php

use Modules\Booking\Core\TenantManager;
use Modules\Booking\Core\FeatureManager;
use Modules\Booking\Core\UsageManager;

if (! function_exists('currentTenant')) {
    /**
     * Get the current active tenant ID for the booking module.
     *
     * @return int|null
     */
    function currentTenant(): ?int
    {
        return app(TenantManager::class)->getCurrentTenantId();
    }
}

if (! function_exists('feature')) {
    /**
     * Check if a specific booking feature is enabled for the current tenant.
     *
     * @param string $featureName
     * @return bool
     */
    function feature(string $featureName): bool
    {
        return app(FeatureManager::class)->isEnabled($featureName);
    }
}

if (! function_exists('canUse')) {
    /**
     * Check if the current tenant is within the allowed usage limits.
     *
     * @param string $limitName
     * @return bool
     */
    function canUse(string $limitName): bool
    {
        return app(UsageManager::class)->canUse($limitName);
    }
}
