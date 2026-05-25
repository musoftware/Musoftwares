<?php

namespace Modules\Booking\Features\Scheduling;

class CapacityManager
{
    /**
     * Check if a specific slot for a resource/service still has capacity.
     * Useful for group sessions where capacity > 1.
     *
     * @param int $serviceId
     * @param int $resourceId
     * @param string $startAt
     * @param string $endAt
     * @param int $capacityRequired
     * @return bool
     */
    public function hasCapacity(int $serviceId, int $resourceId, string $startAt, string $endAt, int $capacityRequired = 1): bool
    {
        // TODO: Get service capacity limit
        // TODO: Count existing confirmed/pending reservations in this slot
        // TODO: Return if (existing + required <= capacity)
        
        return true;
    }
}
