<?php

namespace Modules\Booking\app\Features\GcalSync\Services;

use Modules\Booking\app\Features\GcalSync\Models\GoogleCalendar;
use Modules\Booking\Models\Booking;

class GoogleCalendarSyncService
{
    protected $tokenManager;

    public function __construct(GoogleTokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * Push a booking up to Google Calendar.
     */
    public function pushBooking(Booking $booking, GoogleCalendar $calendar)
    {
        $accessToken = $this->tokenManager->getValidAccessToken($calendar->account);

        // Uses a fake HTTP post for architectural demonstration.
        // In real app, you'd use \Illuminate\Support\Facades\Http or Google API PHP Client.

        $eventData = [
            'summary' => 'Booking: ' . ($booking->service->name ?? 'Service'),
            'description' => 'Customer: ' . ($booking->customer->name ?? 'Unknown'),
            'start' => [
                'dateTime' => $booking->starts_at->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ],
            'end' => [
                'dateTime' => $booking->ends_at->toRfc3339String(),
                'timeZone' => config('app.timezone'),
            ],
        ];

        // Call Google API...
        // $response = Http::withToken($accessToken)->post("https://www.googleapis.com/calendar/v3/calendars/{$calendar->calendar_id}/events", $eventData);

        // Assume response gives us the Google Event ID
        $googleEventId = 'mock_google_id_' . $booking->id; 

        return clone $booking; // Return successful booking
    }
}
