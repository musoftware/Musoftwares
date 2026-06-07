<?php

namespace Modules\Booking\app\Features\GcalSync\Services;

use Laravel\Socialite\Facades\Socialite;
use Modules\Booking\app\Features\GcalSync\Models\GoogleAccount;
use Illuminate\Support\Carbon;

class GoogleOAuthService
{
    public function getAuthUrl()
    {
        return Socialite::driver('google')
            ->scopes([\Google_Service_Calendar::CALENDAR, \Google_Service_Calendar::CALENDAR_EVENTS])
            ->with(['access_type' => 'offline', 'prompt' => 'consent'])
            ->stateless()
            ->redirect()
            ->getTargetUrl();
    }

    public function handleCallback($request)
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        // Save the tokens into the tenant's account
        return GoogleAccount::updateOrCreate(
            [
                'tenant_id' => (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()),
                'google_id' => $googleUser->getId()
            ],
            [
                'email' => $googleUser->getEmail(),
                'access_token' => $googleUser->token,
                'refresh_token' => $googleUser->refreshToken ?? '', // Only sent on first consent
                'expires_at' => Carbon::now()->addSeconds($googleUser->expiresIn),
                'user_id' => auth()->id()
            ]
        );
    }
}
