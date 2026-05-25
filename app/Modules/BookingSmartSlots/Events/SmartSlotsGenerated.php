<?php

namespace App\Modules\BookingSmartSlots\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SmartSlotsGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public int $branchId;
    public array $slots;

    public function __construct(int $tenantId, int $branchId, array $slots)
    {
        $this->tenantId = $tenantId;
        $this->branchId = $branchId;
        $this->slots = $slots;
    }
}
