<?php

namespace Modules\Booking\app\Features\PublicBooking\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\PublicBooking\Models\BookingPageSetting;

class BookingPageSettingsUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $settings;

    public function __construct(BookingPageSetting $settings)
    {
        $this->settings = $settings;
    }
}
