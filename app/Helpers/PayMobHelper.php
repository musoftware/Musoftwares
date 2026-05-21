<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;
use Exception;

class PayMobHelper
{
    // Define PayMob API endpoints
    private const AUTH_URL = 'https://accept.paymob.com/api/auth/tokens';
    private const PAYMENT_URL_CARD = 'https://accept.paymob.com/api/ecommerce/payment-links';
    private const PAYMENT_URL_WALLET = 'https://accept.paymob.com/v1/intention/';
    private const PUBLIC_PAYMENT_URL_BY_WALLET = 'https://accept.paymob.com/unifiedcheckout/?publicKey=:public_key&clientSecret=:client_key';
    private const PAYMENT_VERIFY_URL = 'https://accept.paymob.com/api/acceptance/transactions/';

    /**
     * Generate an authentication token from PayMob.
     *
     * @param string $apiKey
     * @return string
     * @throws Exception
     */
    public static function generateAuthToken(string $apiKey): string
    {
        $response = Http::asJson()->post(self::AUTH_URL, [
            "api_key" => $apiKey,
        ]);

        if ($response->failed()) {
            throw new Exception("Failed to generate auth token: " . $response->body());
        }

        return $response->json('token');
    }

    /**
     * Create an order on PayMob.
     *
     * @param string $authToken
     * @param float $amount
     * @param string $currency
     * @param string $merchantOrderId
     * @param string $email
     * @param string $mobile
     * @param string $firstName
     * @param string $lastName
     * @return string
     * @throws Exception
     */
    public static function createOrder(
        string $authToken,
        float $amount,
        string $currency,
        string $merchantOrderId,
        string $email,
        string $mobile,
        string $firstName,
        string $lastName
    ): string {
        $response = Http::asJson()->withHeaders([
            'Authorization' => 'Bearer ' . $authToken,
        ])->post(self::PAYMENT_URL_CARD, [
            "delivery_needed" => false,
            "amount_cents" => intval(round($amount) * 100),
            "currency" => $currency,
            "merchant_order_id" => 'ORDER-MUSOFTWARE-' . $merchantOrderId,
            "is_live" => 'true',
            'payment_methods' => [2702783],
            "items" => [
                [
                    "name" => "Balance #" . (round($amount) * 100),
                    "amount_cents" => intval(round($amount) * 100),
                    "quantity" => 1,
                    "description" => "Charge Balance with " . (round($amount) * 100),
                ],
            ],
            "shipping_data" => self::getBillingData($email, $mobile, $firstName, $lastName),
        ]);

        if ($response->failed() || empty($response->json('id'))) {
            throw new Exception("Failed to create order: " . $response->body());
        }

        return $response->json('id');
    }

    /**
     * Generate payment keys for a specific order.
     *
     * @param string $authToken
     * @param float $amount
     * @param string $currency
     * @param string $orderId
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @param string $mobile
     * @param int $integrationId
     * @return array
     * @throws Exception
     */
    public static function createPaymentKeys(
        string $authToken,
        float $amount,
        string $currency,
        string $orderId,
        string $email,
        string $firstName,
        string $lastName,
        string $mobile,
        int $integrationId
    ): array {
        $response = Http::asJson()->withHeaders([
            'Authorization' => 'Bearer ' . $authToken,
        ])->post(self::PAYMENT_URL_CARD, [
            "amount_cents" => (round($amount) * 100) . "",
            "expiration" => 3600,
            "order_id" => $orderId,
            "billing_data" => self::getBillingData($email, $mobile, $firstName, $lastName),
            "currency" => $currency,
            "integration_id" => $integrationId,
            "lock_order_when_paid" => true,
        ]);

        if ($response->failed()) {
            throw new Exception("Failed to create payment keys: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Process payment using a mobile wallet.
     *
     * @param string $mobile
     * @param string $paymentToken
     * @return array
     * @throws Exception
     */
    public static function payWithWallet(string $mobile, string $paymentToken): array
    {
        $response = Http::asJson()->post(self::PAYMENT_URL_WALLET, [
            "source" => [
                'identifier' => $mobile,
                'subtype' => 'WALLET',
            ],
            "payment_token" => $paymentToken,
        ]);

        if ($response->failed()) {
            throw new Exception("Failed to process wallet payment: " . $response->body());
        }

        return $response->json();
    }

    /**
     * Get billing/shipping data structure.
     *
     * @param string $email
     * @param string $mobile
     * @param string $firstName
     * @param string $lastName
     * @return array
     */
    private static function getBillingData(
        string $email,
        string $mobile,
        string $firstName,
        string $lastName
    ): array {
        return [
            "email" => $email,
            "phone_number" => $mobile,
            "first_name" => $firstName,
            "last_name" => $lastName,
            "street" => "NA",
            "postal_code" => "NA",
            "city" => "NA",
            "country" => "NA",
            "state" => "NA",
            "shipping_method" => "PKG",
            "building" => "NA",
            "apartment" => "NA",
            "floor" => "NA",
        ];
    }
}
