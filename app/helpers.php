<?php

use Modules\CRM\app\Core\FeatureManager;
use Modules\CRM\app\Core\LimitManager;

if (!function_exists('feature')) {
    /**
     * Check if the current CRM workspace has access to a specific feature flag.
     *
     * @param string $feature
     * @return bool
     */
    function feature(string $feature): bool
    {
        return app(FeatureManager::class)->has($feature);
    }
}

if (!function_exists('canUse')) {
    /**
     * Check if the current CRM workspace can perform an action based on their limits.
     *
     * @param string $limitKey
     * @param int $amount
     * @return bool
     */
    function canUse(string $limitKey, int $amount = 1): bool
    {
        return app(LimitManager::class)->canUse($limitKey, $amount);
    }
}

if (!function_exists('activity')) {
    /**
     * Get the ActivityLogger instance to log an event.
     *
     * @return \Modules\CRM\app\Core\ActivityLogger
     */
    function activity(): \Modules\CRM\app\Core\ActivityLogger
    {
        return app(\Modules\CRM\app\Core\ActivityLogger::class);
    }
}
