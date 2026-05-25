<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;

class SmsSmartEgyptProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        $sender = $credentials['sender_id'] ?? '';

        $url = "https://smssmartegypt.com/sms/api/json/";

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ])->post($url, [
            'username' => $username,
            'password' => $password,
            'sendername' => $sender,
            'mobiles' => $mobile,
            'message' => $message,
        ]);

        return ((int)trim($response->body()) > 0);
    }
}
