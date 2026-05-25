<?php

namespace Modules\Booking\app\Features\Reminders\Listeners;

use Modules\Booking\app\Features\Reminders\Services\BookingWaReminderService;
use Modules\Booking\Events\BookingStatusChanged;

class ScheduleWaRemindersOnBooking
{
    protected $service;

    public function __construct(BookingWaReminderService $service)
    {
        $this->service = $service;
    }

    /**
     * Handle the event.
     */
    public function handle($event)
    {
        // Example: If a booking is confirmed, schedule the 'on_booking_confirmed' and 'before_X' reminders.
        // We assume $event has a $booking property.
        
        $booking = $event->booking ?? null;
        if (!$booking) return;

        // Ensure the feature is enabled for the tenant
        if (!feature('booking-wa-reminders')) {
            return;
        }

        // The specific event and status
        if ($event instanceof BookingStatusChanged && $event->status === 'confirmed') {
            $this->service->scheduleRemindersForBookingEvent($booking, 'on_booking_confirmed');
            
            // Also evaluate before_X triggers at confirmation
            $this->service->scheduleRemindersForBookingEvent($booking, 'before_24_hours');
            $this->service->scheduleRemindersForBookingEvent($booking, 'before_1_hour');
        }
    }
}
