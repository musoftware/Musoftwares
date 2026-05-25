<?php

namespace Modules\Booking\app\Features\GcalSync\Services;

use Modules\Booking\app\Features\GcalSync\Models\GoogleAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;

class GoogleTokenManager
{
    public function getValidAccessToken(GoogleAccount $account)
    {
        // If token is expired or expiring very soon, refresh it
        if ($account->expires_at && $account->expires_at->subMinutes(5)->isPast()) {
            return $this->refreshToken($account);
        }

        return $account->access_token;
    }

    public function refreshToken(GoogleAccount $account)
    {
        if (!$account->refresh_token) {
            throw new \Exception('No refresh token available to renew access token.');
        }

        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'refresh_token' => $account->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if ($response->failed()) {
            throw new \Exception('Failed to refresh Google token.');
        }

        $data = $response->json();

        $account->update([
            'access_token' => $data['access_token'],
            'expires_at' => Carbon::now()->addSeconds($data['expires_in']),
        ]);

        return $data['access_token'];
    }
}
