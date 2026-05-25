<?php

namespace Modules\Booking\app\Features\GcalSync\Services;

use Modules\Booking\app\Features\GcalSync\Models\GoogleCalendar;

class CalendarAvailabilityImporter
{
    protected $tokenManager;

    public function __construct(GoogleTokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * Pull external events and map them to "Busy Slots" 
     * to prevent public booking conflicts.
     */
    public function importBusySlots(GoogleCalendar $calendar)
    {
        $accessToken = $this->tokenManager->getValidAccessToken($calendar->account);

        // Call Google API: GET /events?timeMin=...
        
        // Foreach event, store a "BusySlot" in the system associated with the Resource.
        // This is queried by BookingAvailabilityResolver
        return true;
    }
}
