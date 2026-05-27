<?php

namespace App\Helpers;

class KashierHelper
{
    public static function generateHash($merchantId, $orderId, $amount, $currency, $customerReference)
    {
        $secretKey = config('services.kashier.secret_key');
        $stringToHash = $merchantId . "." . $orderId . "." . $amount . "." . $currency;
        $path = "/?payment=" . $stringToHash;
        if ($customerReference) {
            $path .= "." . $customerReference;
        }
        return hash_hmac('sha256', $path, $secretKey, false);
    }

    public static function buildBalancePaymentUrl(
        float $amount,
        int $userId,
        string $userName,
        string $userEmail,
        string $currency = 'EGP',
        ?string $orderIdPrefix = 'deposit_'
    ): string {
        $orderId = $orderIdPrefix . uniqid() . '-' . $userId;
        $merchantId = config('services.kashier.merchant_id', 'MID-12345');
        $mode = config('services.kashier.mode', 'live');
        $successUrl = urlencode(route('financial.add-balance.success'));
        $failureUrl = urlencode(route('financial.add-balance.failure'));
        $webhookUrl = urlencode(route('financial.add-balance.webhook'));

        $hash = self::generateHash($merchantId, $orderId, $amount, $currency, 'user_' . $userId);

        $customer = [
            'firstName' => $userName,
            'email' => $userEmail,
            'reference' => 'user_' . $userId,
        ];

        $params = [
            'merchantId' => $merchantId,
            'orderId' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'hash' => $hash,
            'mode' => $mode,
            'merchantRedirect' => $successUrl,
            'serverWebhook' => $webhookUrl,
            'failureRedirect' => $failureUrl,
            'redirectMethod' => 'get',
            'type' => 'external',
            'brandColor' => '#4f46e5',
            'display' => app()->getLocale() ?: 'en',
            'manualCapture' => 'false',
            'customer' => json_encode($customer),
            'saveCard' => 'optional',
            'interactionSource' => 'Ecommerce',
            'enable3DS' => 'true',
            'allowedMethods' => 'card,wallet',
            'CustomerReference' => $userId,
            'metaData' => json_encode([
                'user_id' => $userId,
                'source' => 'balance-recharge',
            ]),
        ];

        return 'https://payments.kashier.io/?' . http_build_query($params);
    }

    public static function buildPointPurchasePaymentUrl(
        float $amount,
        int $userId,
        string $userName,
        string $userEmail,
        ?int $packageId,
        int $points,
        string $currency = 'EGP'
    ): string {
        $orderId = 'points_' . uniqid() . '-' . $userId;
        $merchantId = config('services.kashier.merchant_id', 'MID-12345');
        $mode = config('services.kashier.mode', 'live');
        $successUrl = urlencode(route('freelance.point-purchases.success'));
        $failureUrl = urlencode(route('freelance.point-purchases.failure'));
        $webhookUrl = urlencode(route('freelance.point-purchases.webhook'));

        $hash = self::generateHash($merchantId, $orderId, $amount, $currency, 'user_' . $userId);

        $customer = [
            'firstName' => $userName,
            'email' => $userEmail,
            'reference' => 'user_' . $userId,
        ];

        $params = [
            'merchantId' => $merchantId,
            'orderId' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'hash' => $hash,
            'mode' => $mode,
            'merchantRedirect' => $successUrl,
            'serverWebhook' => $webhookUrl,
            'failureRedirect' => $failureUrl,
            'redirectMethod' => 'get',
            'type' => 'external',
            'brandColor' => '#4f46e5',
            'display' => app()->getLocale() ?: 'en',
            'manualCapture' => 'false',
            'customer' => json_encode($customer),
            'saveCard' => 'optional',
            'interactionSource' => 'Ecommerce',
            'enable3DS' => 'true',
            'allowedMethods' => 'card,wallet',
            'CustomerReference' => $userId,
            'metaData' => json_encode([
                'user_id' => $userId,
                'source' => 'points-purchase',
                'package_id' => $packageId,
                'points' => $points,
            ]),
        ];

        return 'https://payments.kashier.io/?' . http_build_query($params);
    }

    public static function buildSubscriptionPaymentUrl(
        float $amount,
        int $userId,
        string $userName,
        string $userEmail,
        int $planId,
        string $currency = 'USD',
        string $billingCycle = '1_year',
        int $days = 365,
        array $items = [],
        bool $isNewSystem = false
    ): string {
        $orderId = 'sub_' . uniqid() . '-' . $userId;
        $merchantId = config('services.kashier.merchant_id', 'MID-12345');
        $mode = config('services.kashier.mode', 'live');
        $successUrl = urlencode(route('subscriptions.kashier.success'));
        $failureUrl = urlencode(route('subscriptions.kashier.failure'));
        $webhookUrl = urlencode(route('subscriptions.kashier.webhook'));

        $hash = self::generateHash($merchantId, $orderId, $amount, $currency, 'user_' . $userId);

        $customer = [
            'firstName' => $userName,
            'email' => $userEmail,
            'reference' => 'user_' . $userId,
        ];

        $params = [
            'merchantId' => $merchantId,
            'orderId' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'hash' => $hash,
            'mode' => $mode,
            'merchantRedirect' => $successUrl,
            'serverWebhook' => $webhookUrl,
            'failureRedirect' => $failureUrl,
            'redirectMethod' => 'get',
            'type' => 'external',
            'brandColor' => '#4f46e5',
            'display' => app()->getLocale() ?: 'en',
            'manualCapture' => 'false',
            'customer' => json_encode($customer),
            'saveCard' => 'optional',
            'interactionSource' => 'Ecommerce',
            'enable3DS' => 'true',
            'allowedMethods' => 'card,wallet',
            'CustomerReference' => $userId,
            'metaData' => json_encode([
                'user_id' => $userId,
                'source' => 'subscription-purchase',
                'plan_id' => $planId,
                'billing_cycle' => $billingCycle,
                'days' => $days,
                'items' => $items,
                'is_new_system' => $isNewSystem,
            ]),
        ];

        return 'https://payments.kashier.io/?' . http_build_query($params);
    }

    public static function buildBookingPaymentUrl(
        float $amount,
        int $userId,
        string $userName,
        string $userEmail,
        int $bookingId,
        string $currency = 'USD'
    ): string {
        $orderId = 'book_' . uniqid() . '-' . $userId;
        $merchantId = config('services.kashier.merchant_id', 'MID-12345');
        $mode = config('services.kashier.mode', 'live');
        $successUrl = urlencode(route('booking.success', $bookingId));
        // Using the same checkout page for failure so user can retry
        $failureUrl = urlencode(route('booking.checkout', $bookingId));
        $webhookUrl = urlencode(route('booking.webhook.kashier'));

        $hash = self::generateHash($merchantId, $orderId, $amount, $currency, 'user_' . $userId);

        $customer = [
            'firstName' => $userName,
            'email' => $userEmail,
            'reference' => 'user_' . $userId,
        ];

        $params = [
            'merchantId' => $merchantId,
            'orderId' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
            'hash' => $hash,
            'mode' => $mode,
            'merchantRedirect' => $successUrl,
            'serverWebhook' => $webhookUrl,
            'failureRedirect' => $failureUrl,
            'redirectMethod' => 'get',
            'type' => 'external',
            'brandColor' => '#4f46e5',
            'display' => app()->getLocale() ?: 'en',
            'manualCapture' => 'false',
            'customer' => json_encode($customer),
            'saveCard' => 'optional',
            'interactionSource' => 'Ecommerce',
            'enable3DS' => 'true',
            'allowedMethods' => 'card,wallet',
            'CustomerReference' => $userId,
            'metaData' => json_encode([
                'user_id' => $userId,
                'source' => 'booking-purchase',
                'booking_id' => $bookingId,
            ]),
        ];

        return 'https://payments.kashier.io/?' . http_build_query($params);
    }

    public static function validatePayload(): bool
    {
        $paymentApiKey = config('services.kashier.secret_key');
        if (request()->isMethod('POST')) {
            $raw_payload = request()->getContent();
            $json_data = json_decode($raw_payload, true);
            if (!$json_data || !isset($json_data['data'])) {
                return false;
            }
            $data_obj = $json_data['data'];
            if (!isset($data_obj['signatureKeys']) || !is_array($data_obj['signatureKeys'])) {
                return false;
            }
            sort($data_obj['signatureKeys']);

            $kashierSignature = request()->header('x-kashier-signature');
            $data = [];
            foreach ($data_obj['signatureKeys'] as $key) {
                if (isset($data_obj[$key])) {
                    $data[$key] = $data_obj[$key];
                }
            }
            $queryString = http_build_query($data, "", '&', PHP_QUERY_RFC3986);
            $signature = hash_hmac('sha256', $queryString, $paymentApiKey, false);
            return hash_equals($signature, (string) $kashierSignature);
        }
        return false;
    }
}
