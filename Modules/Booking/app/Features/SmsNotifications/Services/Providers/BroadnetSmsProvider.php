<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;

class BroadnetSmsProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        $sender = $credentials['sender_id'] ?? '';

        $message_uni = $this->uniMessage($message);
        
        $url = "https://gwe2s.broadnet.me:8443/websmpp/websms";

        $response = Http::withHeaders([
            'User-Agent' => 'Mozilla/5.0'
        ])->get($url, [
            'user' => $username,
            'pass' => $password,
            'sid' => $sender,
            'mno' => $mobile,
            'type' => 2,
            'text' => $message_uni,
            'resp' => 'true',
            'respformat' => 'json'
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return isset($data['Response'][0]) && is_numeric($data['Response'][0]);
        }

        return false;
    }

    private function uniMessage($message)
    {
        $uni = '';
        $chars = preg_split('//u', $message, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($chars as $char) {
            $uni .= str_pad(dechex($this->uniord($char)), 4, "0", STR_PAD_LEFT);
        }
        return strtoupper($uni);
    }

    private function uniord($c)
    {
        if (ord($c[0]) >= 0 && ord($c[0]) <= 127) return ord($c[0]);
        if (ord($c[0]) >= 192 && ord($c[0]) <= 223) return (ord($c[0]) - 192) * 64 + (ord($c[1]) - 128);
        return 0; // Simplified
    }
}
