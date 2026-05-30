<?php

namespace Modules\Booking\app\Features\BookingPriority\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PriorityQueueUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public int $branchId;
    public array $newOrder;

    public function __construct(int $tenantId, int $branchId, array $newOrder)
    {
        $this->tenantId = $tenantId;
        $this->branchId = $branchId;
        $this->newOrder = $newOrder;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->tenantId . '.branch.' . $this->branchId . '.queue');
    }
}
