<?php

namespace Modules\Booking\app\Features\CustomDomains\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\CustomDomains\Models\BookingCustomDomain;

class BookingCustomDomainFailed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $customDomain;

    public function __construct(BookingCustomDomain $customDomain)
    {
        $this->customDomain = $customDomain;
    }
}
