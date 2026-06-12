<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserIntegration;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class GoogleCalendarIntegrationController extends Controller
{
    /**
     * Redirect the user to the Google authentication page for calendar permissions.
     */
    public function connect()
    {
        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/calendar.events',
                'https://www.googleapis.com/auth/calendar.readonly',
            ])
            ->with(['access_type' => 'offline', 'prompt' => 'consent select_account'])
            ->redirectUrl(config('services.google_calendar.redirect'))
            ->redirect();
    }

    /**
     * Obtain the user information from Google and save the tokens.
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')
                ->stateless()
                ->redirectUrl(config('services.google_calendar.redirect'))
                ->user();
            
            $user = auth()->user();

            UserIntegration::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'provider' => 'google_calendar',
                ],
                [
                    'provider_id' => $googleUser->getId(),
                    'access_token' => $googleUser->token,
                    // Refresh token might be null if not provided (e.g., prompt not consent)
                    // We only overwrite if it is present.
                    'refresh_token' => $googleUser->refreshToken ?? $user->integrations()->where('provider', 'google_calendar')->value('refresh_token'),
                    'expires_at' => now()->addSeconds($googleUser->expiresIn ?? 3600),
                    'scopes' => $googleUser->user['scope'] ?? null,
                ]
            );

            return redirect()->route('admin.settings.index')->with('success', __('admin.google_calendar_connected'));
            
        } catch (\Exception $e) {
            return redirect()->route('admin.settings.index')->with('error', __('admin.google_calendar_connection_failed', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Disconnect the Google Calendar integration.
     */
    public function disconnect()
    {
        auth()->user()->integrations()->where('provider', 'google_calendar')->delete();

        return redirect()->back()->with('success', __('admin.google_calendar_disconnected'));
    }
}
