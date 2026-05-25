<?php

namespace Modules\Booking\app\Features\Reminders\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\Reminders\Models\BookingWaReminder;

class BookingWaReminderFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $reminder;
    public $error;

    public function __construct(BookingWaReminder $reminder, string $error)
    {
        $this->reminder = $reminder;
        $this->error = $error;
    }
}
