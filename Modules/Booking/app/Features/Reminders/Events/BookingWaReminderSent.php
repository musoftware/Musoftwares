<?php

namespace Modules\Booking\app\Features\Reminders\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\Reminders\Models\BookingWaReminder;

class BookingWaReminderSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reminder;

    public function __construct(BookingWaReminder $reminder)
    {
        $this->reminder = $reminder;
    }
}
