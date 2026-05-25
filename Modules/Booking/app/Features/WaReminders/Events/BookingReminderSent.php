<?php

namespace Modules\Booking\app\Features\WaReminders\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\WaReminders\Models\WaLog;

class BookingReminderSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $log;

    public function __construct(WaLog $log)
    {
        $this->log = $log;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->log->tenant_id . '.booking.' . $this->log->booking_id);
    }

    public function broadcastAs()
    {
        return 'reminder.sent';
    }
}
