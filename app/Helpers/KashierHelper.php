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

    private static function prepareEgpConversion(float &$amount, string &$currency, array &$metaData): void
    {
        $originalCurrency = strtoupper($currency);
        
        if ($originalCurrency !== 'EGP') {
            $currencyModel = \App\Models\Currency::where('currency', $originalCurrency)->first();
            $egpModel = \App\Models\Currency::where('currency', 'EGP')->first();
            
            if ($currencyModel && $egpModel) {
                // Keep track of original values
                $metaData['original_amount'] = $amount;
                $metaData['original_currency'] = $originalCurrency;

                // Convert amount to EGP using today's rate
                $amount = \App\Models\CurrenciesExchange::RateToday($amount, $currencyModel->id, $egpModel->id);
            }
            $currency = 'EGP';
        }
    }

    /**
     * Parses the webhook amount safely into the user's currency.
     * Uses the original_amount if available, otherwise converts from EGP.
     */
    public static function getWebhookAmountInUserCurrency(float $webhookAmount, array $metadata, \App\Models\User $user): float
    {
        if (isset($metadata['original_amount'])) {
            return floatval($metadata['original_amount']);
        }

        $egpCurrency = \App\Models\Currency::where('currency', 'EGP')->first();
        if ($egpCurrency && $user->currency != $egpCurrency->id) {
            return \App\Models\CurrenciesExchange::RateToday($webhookAmount, $egpCurrency->id, $user->currency);
        }

        return $webhookAmount;
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

        $metaDataArray = [
            'user_id' => $userId,
            'source' => 'balance-recharge',
        ];
        self::prepareEgpConversion($amount, $currency, $metaDataArray);

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
            'CustomerReference' => 'user_' . $userId,
            'metaData' => json_encode($metaDataArray),
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

        $metaDataArray = [
            'user_id' => $userId,
            'source' => 'points-purchase',
            'package_id' => $packageId,
            'points' => $points,
        ];
        self::prepareEgpConversion($amount, $currency, $metaDataArray);

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
            'CustomerReference' => 'user_' . $userId,
            'metaData' => json_encode($metaDataArray),
        ];

        return 'https://payments.kashier.io/?' . http_build_query($params);
    }

    public static function buildSubscriptionPaymentUrl(
        float $amount,
        int $userId,
        string $userName,
        string $userEmail,
        ?int $planId = null,
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

        $metaDataArray = [
            'user_id' => $userId,
            'source' => 'subscription-purchase',
            'plan_id' => $planId,
            'billing_cycle' => $billingCycle,
            'days' => $days,
            'items' => $items,
            'is_new_system' => $isNewSystem,
        ];
        self::prepareEgpConversion($amount, $currency, $metaDataArray);

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
            'CustomerReference' => 'user_' . $userId,
            'metaData' => json_encode($metaDataArray),
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
        $webhookUrl = urlencode(route('booking.webhook'));

        $metaDataArray = [
            'user_id' => $userId,
            'source' => 'booking-purchase',
            'booking_id' => $bookingId,
        ];
        self::prepareEgpConversion($amount, $currency, $metaDataArray);

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
            'CustomerReference' => 'user_' . $userId,
            'metaData' => json_encode($metaDataArray),
        ];

        return 'https://payments.kashier.io/?' . http_build_query($params);
    }

    public static function buildInvoiceGuestPaymentUrl(
        float $amount,
        int $invoiceId,
        int $userId,
        string $guestName,
        string $guestEmail,
        string $currency = 'EGP'
    ): string {
        $orderId = 'inv_' . $invoiceId . '_' . uniqid();
        $merchantId = config('services.kashier.merchant_id', 'MID-12345');
        $mode = config('services.kashier.mode', 'live');
        $successUrl = urlencode(route('guest.invoices.payment.success'));
        $failureUrl = urlencode(route('guest.invoices.payment.failure'));
        $webhookUrl = urlencode(route('guest.invoices.payment.webhook'));

        $metaDataArray = [
            'user_id' => $userId,
            'source' => 'guest-invoice-payment',
            'invoice_id' => $invoiceId,
        ];
        self::prepareEgpConversion($amount, $currency, $metaDataArray);

        $hash = self::generateHash($merchantId, $orderId, $amount, $currency, 'user_' . $userId);

        $customer = [
            'firstName' => $guestName,
            'email' => $guestEmail,
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
            'CustomerReference' => 'user_' . $userId,
            'metaData' => json_encode($metaDataArray),
        ];

        return 'https://payments.kashier.io/?' . http_build_query($params);
    }

    public static function buildUserInvoicePaymentUrl(
        float $amount,
        int $invoiceId,
        int $userId,
        string $userName,
        string $userEmail,
        string $currency = 'EGP'
    ): string {
        $orderId = 'u_inv_' . $invoiceId . '_' . uniqid();
        $merchantId = config('services.kashier.merchant_id', 'MID-12345');
        $mode = config('services.kashier.mode', 'live');
        $successUrl = urlencode(route('billing.invoices.payment.success'));
        $failureUrl = urlencode(route('billing.invoices.payment.failure'));
        $webhookUrl = urlencode(route('billing.invoices.payment.webhook'));

        $metaDataArray = [
            'user_id' => $userId,
            'source' => 'user-invoice-payment',
            'invoice_id' => $invoiceId,
        ];
        self::prepareEgpConversion($amount, $currency, $metaDataArray);

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
            'CustomerReference' => 'user_' . $userId,
            'metaData' => json_encode($metaDataArray),
        ];

        return 'https://payments.kashier.io/?' . http_build_query($params);
    }

    public static function buildPaymentLinkUrl(
        float $amount,
        int $paymentLinkId,
        int $userId,
        string $guestName,
        string $guestEmail,
        string $currency = 'EGP'
    ): string {
        $orderId = 'plnk_' . $paymentLinkId . '_' . uniqid();
        $merchantId = config('services.kashier.merchant_id', 'MID-12345');
        $mode = config('services.kashier.mode', 'live');
        $successUrl = urlencode(route('guest.payment-links.success'));
        $failureUrl = urlencode(route('guest.payment-links.failure'));
        $webhookUrl = urlencode(route('webhook'));

        $metaDataArray = [
            'user_id' => $userId,
            'source' => 'payment-link',
            'payment_link_id' => $paymentLinkId,
        ];
        self::prepareEgpConversion($amount, $currency, $metaDataArray);

        $hash = self::generateHash($merchantId, $orderId, $amount, $currency, 'user_' . $userId);

        $customer = [
            'firstName' => $guestName,
            'email' => $guestEmail,
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
            'CustomerReference' => 'user_' . $userId,
            'metaData' => json_encode($metaDataArray),
        ];

        return 'https://payments.kashier.io/?' . http_build_query($params);
    }

    public static function validatePayload($rawPayload = null, $kashierSignature = null): bool
    {
        $paymentApiKey = config('services.kashier.secret_key');
        
        if ($rawPayload === null) {
            if (!request()->isMethod('POST')) {
                return false;
            }
            $rawPayload = request()->getContent();
            $kashierSignature = request()->header('x-kashier-signature');
        }

        $json_data = is_array($rawPayload) ? $rawPayload : json_decode($rawPayload, true);
        if (!$json_data || !isset($json_data['data'])) {
            return false;
        }
        $data_obj = $json_data['data'];
        if (!isset($data_obj['signatureKeys']) || !is_array($data_obj['signatureKeys'])) {
            return false;
        }
        sort($data_obj['signatureKeys']);

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
}
