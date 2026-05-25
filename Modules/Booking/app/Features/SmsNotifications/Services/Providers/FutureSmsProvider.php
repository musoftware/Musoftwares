<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;

class FutureSmsProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        $username = $credentials['username'] ?? '';
        $sender = $credentials['sender_id'] ?? '';

        $url = "https://sms.future.com.eg/sms/api";

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.108 Safari/537.36'
        ])->get($url, [
            'action' => 'send-sms',
            'api_key' => $username,
            'to' => $mobile,
            'from' => $sender,
            'sms' => $message,
            'unicode' => 1
        ]);

        return strpos($response->body(), '"code":"ok"') !== false;
    }
}
