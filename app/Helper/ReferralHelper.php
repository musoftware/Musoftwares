<?php

namespace App\Helper;

use App\Models\UserReferral;
use GuzzleHttp\Client;

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

    public static function isProxy($ipAddress)
    {
        $client = new Client();
        $apiKey = 'MjA0NjQ6V3owSWpCdk1HZHkxWmdXcVN3ZVlyd0lUaGt6cGl2ZjA=';

        $response = $client->get("http://v2.api.iphub.info/ip/{$ipAddress}", [
            'headers' => [
                'X-Key' => $apiKey,
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        return $data['block'];
    }
}
