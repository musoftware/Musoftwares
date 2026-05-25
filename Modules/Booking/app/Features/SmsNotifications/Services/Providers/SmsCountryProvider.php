<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;

class SmsCountryProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        $sender = $credentials['sender_id'] ?? '';

        $message_uni = $this->uniMessage($message);
        
        $url = "http://api.smscountry.com/SMSCwebservice_bulk.aspx";

        $response = Http::get($url, [
            'User' => $username,
            'passwd' => $password,
            'mobilenumber' => $mobile,
            'message' => $message_uni,
            'sid' => $sender,
            'Mtype' => 'OL'
        ]);

        return trim($response->body()) === 'SMS message(s) sent';
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
        return 0; // Simplified for interface
    }
}
