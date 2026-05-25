<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;

class TwilioProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        $sid = $credentials['account_sid'] ?? '';
        $token = $credentials['auth_token'] ?? '';
        $from = $credentials['sender_id'] ?? '';

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $response = Http::withBasicAuth($sid, $token)
            ->asForm()
            ->post($url, [
                'To' => $mobile,
                'From' => $from,
                'Body' => $message,
            ]);

        return $response->successful();
    }
}
