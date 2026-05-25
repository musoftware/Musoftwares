<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;

class PortalFutureSmsProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        $sender = $credentials['sender_id'] ?? '';

        $url = "http://api.future.com.eg/sendsms/plain";

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36'
        ])->get($url, [
            'user' => $username,
            'password' => $password,
            'GSM' => $mobile,
            'sender' => $sender,
            'DataCoding' => 8,
            'SMSText' => $message
        ]);

        return ((int)trim($response->body()) > 0);
    }
}
