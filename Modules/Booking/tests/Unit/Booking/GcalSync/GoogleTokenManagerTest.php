<?php

namespace Modules\Booking\tests\Unit\Booking\GcalSync;

use Tests\TestCase;
use Modules\Booking\app\Features\GcalSync\Services\GoogleTokenManager;
use Modules\Booking\app\Features\GcalSync\Models\GoogleAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class GoogleTokenManagerTest extends TestCase
{
    public function test_it_returns_access_token_if_valid()
    {
        $account = new GoogleAccount([
            'access_token' => 'valid_token_123',
            'expires_at' => Carbon::now()->addHour()
        ]);

        $manager = new GoogleTokenManager();
        $this->assertEquals('valid_token_123', $manager->getValidAccessToken($account));
    }

    public function test_it_refreshes_token_if_expired()
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'new_fresh_token',
                'expires_in' => 3600
            ], 200)
        ]);

        $account = new GoogleAccount([
            'access_token' => 'old_expired',
            'refresh_token' => 'refresh_123',
            'expires_at' => Carbon::now()->subHour() // Expired!
        ]);

        $manager = new GoogleTokenManager();
        $token = $manager->getValidAccessToken($account);

        $this->assertEquals('new_fresh_token', $token);
    }
}
