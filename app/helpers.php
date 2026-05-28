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

if (!function_exists('psychological_price')) {
    /**
     * Apply psychological pricing rounding.
     * Example: 22 -> 19.99, 24 -> 29.99
     */
    function psychological_price(float $price): float
    {
        if ($price <= 0) return 0;
        
        $tens = floor($price / 10) * 10;
        $remainder = $price - $tens;
        
        // Handle small prices < 10
        if ($tens == 0 && $remainder < 4) {
            return max(0, round($price) - 0.01);
        }

        if ($remainder < 4) {
            return $tens - 0.01;
        } else {
            return $tens + 10 - 0.01;
        }
    }
}
