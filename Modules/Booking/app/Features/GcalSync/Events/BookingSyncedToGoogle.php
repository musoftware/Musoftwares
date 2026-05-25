<?php

namespace Modules\Booking\app\Features\GcalSync\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\Models\Booking;
use Modules\Booking\app\Features\GcalSync\Models\GoogleCalendar;

class BookingSyncedToGoogle implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $booking;
    public $calendar;

    public function __construct(Booking $booking, GoogleCalendar $calendar)
    {
        $this->booking = $booking;
        $this->calendar = $calendar;
    }

    public function broadcastOn()
    {
        return new Channel('tenant.' . $this->booking->tenant_id . '.bookings');
    }

    public function broadcastAs()
    {
        return 'gcal.synced';
    }
}
