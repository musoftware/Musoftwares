<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;

class InfoBipProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        $sender = $credentials['sender_id'] ?? '';

        $url = "http://api.infobip.com/sendsms/plain";

        $response = Http::get($url, [
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
