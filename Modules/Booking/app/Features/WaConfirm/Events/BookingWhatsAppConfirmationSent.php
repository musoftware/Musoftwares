<?php

namespace Modules\Booking\app\Features\WaConfirm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaConfirmation;

class BookingWhatsAppConfirmationSent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $confirmation;

    public function __construct(BookingWaConfirmation $confirmation)
    {
        $this->confirmation = $confirmation;
    }
}
