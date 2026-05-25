<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;

class MasrBokraProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        $sender = $credentials['sender_id'] ?? '';

        $url = "http://sms.masrbokra.com/sendsms.php";

        $response = Http::get($url, [
            'user' => $username,
            'password' => $password,
            'numbers' => $mobile,
            'sender' => $sender,
            'message' => $message,
            'lang' => 'en'
        ]);

        $d = explode(',', trim($response->body()));
        return (int)trim($d[0] ?? 0) === 1;
    }
}
