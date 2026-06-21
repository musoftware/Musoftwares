<?php

namespace Modules\Booking\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\Models\Booking;

/**
 * Fired when a booking is fully confirmed (free or after payment).
 *
 * Other modules (ERP, CRM, etc.) MUST listen to this event
 * instead of directly importing Booking models. This keeps
 * each module a standalone SaaS product.
 */
class BookingConfirmed
{
    use Dispatchable, SerializesModels;

    public Booking $booking;

    /** The host (service provider) user ID. */
    public int $hostUserId;

    /** Guest display name. */
    public string $guestName;

    /** Guest email. */
    public string $guestEmail;

    /** Guest phone (nullable). */
    public ?string $guestPhone;

    /** Currency ID used for this booking (nullable). */
    public ?int $currencyId;

    public function __construct(Booking $booking)
    {
        $this->booking     = $booking;
        $this->hostUserId  = $booking->eventType->user_id ?? 0;
        $this->guestName   = $booking->guest_name;
        $this->guestEmail  = $booking->guest_email;
        $this->guestPhone  = $booking->guest_phone;
        $this->currencyId  = $booking->currency_id;
    }
}
