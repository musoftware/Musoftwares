<?php

namespace App\Modules\BookingPriority\Services;

class BookingPrioritySupportService
{
    protected PriorityAssignmentEngine $assignmentEngine;
    protected VipCustomerManager $vipManager;
    protected PriorityQueueResolver $queueResolver;

    public function __construct(
        PriorityAssignmentEngine $assignmentEngine,
        VipCustomerManager $vipManager,
        PriorityQueueResolver $queueResolver
    ) {
        $this->assignmentEngine = $assignmentEngine;
        $this->vipManager = $vipManager;
        $this->queueResolver = $queueResolver;
    }

    public function escalateBooking(int $tenantId, int $bookingId, string $reason, ?int $userId = null): void
    {
        $this->assignmentEngine->escalate($tenantId, $bookingId, $reason, $userId);
        
        // Asynchronously rebalance queue
        // dispatch(new RebalancePriorityQueueJob($tenantId, $bookingId));
    }
}
