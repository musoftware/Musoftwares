<?php

namespace Modules\Booking\Core;

class UsageManager
{
    /**
     * Check if the current tenant is within their usage limits for a specific resource type.
     * Examples: max_resources, max_monthly_bookings
     *
     * @param string $limitName
     * @param int $currentCount The current number of items already created
     * @return bool
     */
    public function canUse(string $limitName, int $currentCount = 0): bool
    {
        $tenantId = currentTenant();

        if (!$tenantId) {
            return false;
        }

        // Base limits for a standard subscription
        $baseLimits = [
            'max_resources' => 1,
            'max_monthly_bookings' => 100,
            'max_team_members' => 1,
        ];

        $limit = $baseLimits[$limitName] ?? 0;

        // Boost limit based on purchased add-ons
        // Example: if they bought "booking-extra-user" or "crm-extra-user", we might 
        // count how many of those add-ons they have. For now, we check if they have the feature.
        if ($limitName === 'max_team_members') {
            $extraUsersCount = \App\Models\TenantFeature::where('tenant_id', $tenantId)
                ->where('feature_key', 'crm-extra-user')
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->count();
                
            $limit += $extraUsersCount;
        }

        return $currentCount < $limit;
    }
}
