<?php

namespace App\Modules\BookingPriority\Services;

class PriorityQueueResolver
{
    public function rebalanceQueue(int $tenantId, int $branchId, string $date): void
    {
        // 1. Fetch all pending bookings for branch/date
        // 2. Fetch their associated priority_weight (from Assignments)
        // 3. Sort bookings by weight DESC, then created_at ASC
        // 4. Update order locally or fire PriorityQueueUpdated event for realtime UI update
    }
}
