<?php

namespace App\Modules\BookingSmartSlots\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DynamicAvailabilityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public int $branchId;

    public function __construct(int $tenantId, int $branchId)
    {
        $this->tenantId = $tenantId;
        $this->branchId = $branchId;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->tenantId . '.branch.' . $this->branchId . '.availability');
    }
}
