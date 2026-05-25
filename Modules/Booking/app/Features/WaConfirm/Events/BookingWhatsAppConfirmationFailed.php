<?php

namespace Modules\Booking\app\Features\WaConfirm\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\WaConfirm\Models\BookingWaConfirmation;

class BookingWhatsAppConfirmationFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $confirmation;
    public $error;

    public function __construct(BookingWaConfirmation $confirmation, string $error)
    {
        $this->confirmation = $confirmation;
        $this->error = $error;
    }
}
