<?php

namespace Modules\Booking\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\Models\Booking;

class BookingStatusChanged
{
    use Dispatchable, SerializesModels;

    public $booking;
    public $status;
    public $isRescheduled;

    /**
     * Create a new event instance.
     *
     * @param Booking $booking
     * @param string $status
     * @param boolean $isRescheduled
     */
    public function __construct(Booking $booking, $status, $isRescheduled = false)
    {
        $this->booking = $booking;
        $this->status = $status;
        $this->isRescheduled = $isRescheduled;
    }
}
