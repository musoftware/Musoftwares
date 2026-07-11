<?php

namespace App\Services\Integrations;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleCalendarService extends BaseService
{
    /**
     * Create an event in the user's Google Calendar.
     *
     * @return string|null The Google Event ID
     */
    public function createEvent(User $user, string $title, string $description, \DateTimeInterface $start, \DateTimeInterface $end): ?string
    {
        $token = $this->getValidAccessToken($user);
        if (! $token) {
            return null;
        }

        $response = Http::withToken($token)
            ->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                'summary' => $title,
                'description' => $description,
                'start' => [
                    'dateTime' => $start->format(\DateTime::RFC3339),
                ],
                'end' => [
                    'dateTime' => $end->format(\DateTime::RFC3339),
                ],
            ]);

        if ($response->successful()) {
            return $response->json('id');
        }

        Log::error('Google Calendar Create Event Failed', ['response' => $response->body()]);

        return null;
    }

    /**
     * Update an event in the user's Google Calendar.
     */
    public function updateEvent(User $user, string $eventId, string $title, string $description, \DateTimeInterface $start, \DateTimeInterface $end): bool
    {
        $token = $this->getValidAccessToken($user);
        if (! $token) {
            return false;
        }

        $response = Http::withToken($token)
            ->put("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$eventId}", [
                'summary' => $title,
                'description' => $description,
                'start' => [
                    'dateTime' => $start->format(\DateTime::RFC3339),
                ],
                'end' => [
                    'dateTime' => $end->format(\DateTime::RFC3339),
                ],
            ]);

        if ($response->successful()) {
            return true;
        }

        Log::error('Google Calendar Update Event Failed', ['response' => $response->body()]);

        return false;
    }

    /**
     * Delete an event from the user's Google Calendar.
     */
    public function deleteEvent(User $user, string $eventId): bool
    {
        $token = $this->getValidAccessToken($user);
        if (! $token) {
            return false;
        }

        $response = Http::withToken($token)
            ->delete("https://www.googleapis.com/calendar/v3/calendars/primary/events/{$eventId}");

        if ($response->successful()) {
            return true;
        }

        Log::error('Google Calendar Delete Event Failed', ['response' => $response->body()]);

        return false;
    }

    /**
     * Get a valid access token for the user, refreshing if necessary.
     */
    protected function getValidAccessToken(User $user): ?string
    {
        $integration = $user->integrations()->where('provider', 'google_calendar')->first();

        if (! $integration) {
            return null;
        }

        if ($integration->expires_at && $integration->expires_at->isPast()) {
            return $this->refreshToken($integration);
        }

        return $integration->access_token;
    }

    /**
     * Refresh the Google OAuth access token.
     */
    protected function refreshToken($integration): ?string
    {
        if (! $integration->refresh_token) {
            return null;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google_calendar.client_id'),
            'client_secret' => config('services.google_calendar.client_secret'),
            'refresh_token' => $integration->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $integration->update([
                'access_token' => $data['access_token'],
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
            ]);

            return $data['access_token'];
        }

        Log::error('Google Calendar Token Refresh Failed', ['response' => $response->body()]);

        return null;
    }
}
