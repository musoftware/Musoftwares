<?php

namespace Modules\Booking\app\Features\GcalSync\Services;

use Modules\Booking\app\Features\GcalSync\Models\GoogleCalendar;
use Modules\Booking\Models\Booking;

use Illuminate\Support\Facades\Http;

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
    public function pushBooking(Booking $booking, GoogleCalendar $calendar): string
    {
        $accessToken = $this->tokenManager->getValidAccessToken($calendar->account);

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

        $response = Http::withToken($accessToken)
            ->post("https://www.googleapis.com/calendar/v3/calendars/{$calendar->calendar_id}/events", $eventData);

        if ($response->failed()) {
            throw new \Exception('Google Calendar API Error: ' . $response->body());
        }

        $data = $response->json();
        return $data['id'] ?? ('fallback_id_' . $booking->id);
    }
}
