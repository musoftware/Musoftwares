<?php

namespace Modules\Booking\app\Features\QueueManagement\Services;

use Exception;

class BookingQueueLimitsService
{
    public function canUse(string $limitKey): bool
    {
        return app(\App\Services\MeteredBillingService::class)->canUse($limitKey);
    }

    /**
     * Increase usage for a specific limit.
     */
    public function increaseUsage(string $limitKey, int $amount = 1): void
    {
        app(\App\Services\MeteredBillingService::class)->incrementUsage($limitKey, $amount);
    }

    /**
     * Helper to assert usage.
     * @throws Exception if limit reached.
     */
    public function enforce(string $limitKey): void
    {
        app(\App\Services\MeteredBillingService::class)->enforce($limitKey);
    }
}
