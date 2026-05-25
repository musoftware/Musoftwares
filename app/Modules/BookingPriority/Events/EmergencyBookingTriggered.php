<?php

namespace App\Modules\BookingPriority\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyBookingTriggered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $tenantId;
    public int $bookingId;
    public string $reason;

    public function __construct(int $tenantId, int $bookingId, string $reason)
    {
        $this->tenantId = $tenantId;
        $this->bookingId = $bookingId;
        $this->reason = $reason;
    }
}
