<?php

namespace App\Helpers;

class KashierHelper
{
    public static function generateHash($merchantId, $orderId, $amount, $currency, $customerReference)
    {
        $secretKey = config('services.kashier.secret_key');
        $stringToHash = $merchantId.'.'.$orderId.'.'.$amount.'.'.$currency;
        $path = '/?payment='.$stringToHash;
        if ($customerReference) {
            $path .= '.'.$customerReference;
        }

        return hash_hmac('sha256', $path, $secretKey, false);
    }

    public static function validatePayload($rawPayload = null, $kashierSignature = null): bool
    {
        $paymentApiKey = config('services.kashier.secret_key');

        if ($rawPayload === null) {
            if (! request()->isMethod('POST')) {
                return false;
            }
            $rawPayload = request()->getContent();
            $kashierSignature = request()->header('x-kashier-signature');
        }

        $json_data = is_array($rawPayload) ? $rawPayload : json_decode($rawPayload, true);
        if (! $json_data || ! isset($json_data['data'])) {
            return false;
        }
        $data_obj = $json_data['data'];
        if (! isset($data_obj['signatureKeys']) || ! is_array($data_obj['signatureKeys'])) {
            return false;
        }
        sort($data_obj['signatureKeys']);

        $data = [];
        foreach ($data_obj['signatureKeys'] as $key) {
            if (isset($data_obj[$key])) {
                $data[$key] = $data_obj[$key];
            }
        }
        $queryString = http_build_query($data, '', '&', PHP_QUERY_RFC3986);
        $signature = hash_hmac('sha256', $queryString, $paymentApiKey, false);

        return hash_equals($signature, (string) $kashierSignature);
    }
}
