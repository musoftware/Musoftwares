<?php

namespace Modules\Booking\Core;

class FeatureManager
{
    /**
     * Check if a specific booking feature is enabled for the current tenant.
     *
     * @param string $featureName
     * @return bool
     */
    public function isEnabled(string $featureName): bool
    {
        $tenantId = currentTenant();

        if (!$tenantId) {
            return false;
        }

        // 1. Check if it's explicitly purchased as an add-on
        $feature = \App\Models\TenantFeature::where('tenant_id', $tenantId)
            ->where('feature_key', $featureName)
            ->first();

        if ($feature && $feature->isActive()) {
            return true;
        }

        // 2. Fallback to config defaults for free/base capabilities
        $configValue = config('booking.features.' . $featureName);

        return $configValue ?? false;
    }
}
