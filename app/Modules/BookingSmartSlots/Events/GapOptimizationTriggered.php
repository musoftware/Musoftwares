<?php

namespace App\Modules\BookingSmartSlots\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GapOptimizationTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public int $branchId;
    public string $date;

    public function __construct(int $tenantId, int $branchId, string $date)
    {
        $this->tenantId = $tenantId;
        $this->branchId = $branchId;
        $this->date = $date;
    }
}
