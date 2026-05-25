<?php

namespace Modules\Booking\app\Features\WaReminders\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\WaReminders\Models\WaSchedule;

class BookingReminderScheduled implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $schedule;

    public function __construct(WaSchedule $schedule)
    {
        $this->schedule = $schedule;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->schedule->tenant_id . '.booking.' . $this->schedule->booking_id);
    }

    public function broadcastAs()
    {
        return 'reminder.scheduled';
    }
}
