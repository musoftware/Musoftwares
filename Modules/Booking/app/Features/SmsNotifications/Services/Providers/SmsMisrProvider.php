<?php

namespace Modules\Booking\app\Features\SmsNotifications\Services\Providers;

use Illuminate\Support\Facades\Http;

class SmsMisrProvider implements SmsProviderInterface
{
    public function send(string $mobile, string $message, array $credentials): bool
    {
        // Based on legacy SmsHelper.php logic
        $username = $credentials['username'] ?? '';
        $password = $credentials['password'] ?? '';
        $sender = $credentials['sender_id'] ?? '';

        $message_uni = $this->uniMessage($message);
        
        $url = 'https://smsmisr.com/api/SMS/?';
        $body = "environment=1&username={$username}&password={$password}&sender={$sender}&mobile={$mobile}&message={$message_uni}&language=3";

        $response = Http::asForm()->post($url, [
            'environment' => 1,
            'username' => $username,
            'password' => $password,
            'sender' => $sender,
            'mobile' => $mobile,
            'message' => $message_uni,
            'language' => 3
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return isset($data['code']) && (int)trim($data['code']) === 1901;
        }

        return false;
    }

    private function uniMessage($message)
    {
        // Keeping it simple for the interface implementation
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
