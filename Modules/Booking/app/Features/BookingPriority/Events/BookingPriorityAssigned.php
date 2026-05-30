<?php

namespace Modules\Booking\app\Features\BookingPriority\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingPriorityAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public int $bookingId;
    public int $priorityLevelId;

    public function __construct(int $tenantId, int $bookingId, int $priorityLevelId)
    {
        $this->tenantId = $tenantId;
        $this->bookingId = $bookingId;
        $this->priorityLevelId = $priorityLevelId;
    }
}
