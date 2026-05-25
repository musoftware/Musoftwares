<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SmsWeBusinessProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        if (!Str::startsWith($mobile, '20')) {
            return false;
        }

        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        $accountId = $credentials['account_id'] ?? '';
        $sender = $credentials['sender_id'] ?? '';

        $url = "https://weapi.connekio.com/sms/single";

        $response = Http::withHeaders([
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $password . ':' . $accountId),
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
        ])->post($url, [
            'account_id' => $accountId,
            'text' => $message,
            'msisdn' => $mobile,
            'sender' => $sender,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return isset($data['status']) && (bool)$data['status'];
        }

        return false;
    }
}
