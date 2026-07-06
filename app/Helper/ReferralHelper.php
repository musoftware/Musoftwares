<?php

namespace App\Helper;

use App\Models\UserReferral;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class ReferralHelper
{

    public static function hasReferral($request)
    {
        return $request->session()->get('referral') != null;
    }

    public static function Ref($request)
    {
        return $request->session()->get('referral');
    }

    public static function CheckRef($referral)
    {
        return UserReferral::resolveRef($referral) !== null;
    }

    public static function GetRef($referral_key)
    {
        return UserReferral::resolveRef($referral_key);
    }

    public static function IncViewRef($referral)
    {
        UserReferral::IncViewRef($referral);
    }


    public static function setRef($string, $request)
    {
        $request->session()->put('referral', $string);
    }

    /**
     * Best-effort proxy/VPN detection via iphub.info.
     *
     * Reads IPHUB_API_KEY from config/services.php (which in turn reads
     * env('IPHUB_API_KEY')). When the key is missing the function returns
     * null so callers can decide to skip the check instead of failing.
     *
     * Never hardcode API keys in source — they belong in the environment.
     */
    public static function isProxy($ipAddress): ?bool
    {
        $apiKey = config('services.iphub.key');
        if (empty($apiKey) || empty($ipAddress)) {
            return null;
        }

        try {
            $client = new Client(['timeout' => 3]);
            $response = $client->get("http://v2.api.iphub.info/ip/{$ipAddress}", [
                'headers' => [
                    'X-Key' => $apiKey,
                ],
            ]);

            $data = json_decode((string) $response->getBody(), true);

            return $data['block'] ?? null;
        } catch (\Throwable $e) {
            Log::warning('iphub lookup failed', [
                'ip' => $ipAddress,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}