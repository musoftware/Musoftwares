<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;

class WhySmsProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        $apiKey = $credentials['username'] ?? ''; // Using username as bearer token in legacy code
        $sender = $credentials['sender_id'] ?? '';

        $url = "https://bulk.whysms.com/api/v3/sms/send";

        $response = Http::withToken($apiKey)->post($url, [
            'recipient' => $mobile,
            'sender_id' => $sender,
            'type' => 'plain',
            'message' => $message,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return isset($data['status']) && $data['status'] === 'success';
        }

        return false;
    }
}
