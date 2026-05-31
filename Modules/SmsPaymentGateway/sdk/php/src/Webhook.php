<?php

namespace SmsPay;

use SmsPay\Exception\ApiException;

/**
 * Webhook signature verification for SmsPay events.
 *
 * SmsPay sends webhook events with two security headers:
 *  - `X-SmsPay-Signature`  — HMAC-SHA256 hex digest.
 *  - `X-SmsPay-Timestamp`  — Unix timestamp when the event was dispatched.
 *
 * The signed payload is `{timestamp}.{raw_body}`.
 *
 * Usage:
 * ```php
 * $payload   = file_get_contents('php://input');
 * $signature = $_SERVER['HTTP_X_SMSPAY_SIGNATURE'];
 * $timestamp = $_SERVER['HTTP_X_SMSPAY_TIMESTAMP'];
 *
 * $event = \SmsPay\Webhook::constructEvent($payload, $signature, $timestamp, 'whsec_xxxx');
 *
 * if ($event->type === 'checkout.session.completed') {
 *     $session = $event->data;
 *     // Fulfil the order …
 * }
 * ```
 */
class Webhook
{
    /**
     * Maximum allowed age (in seconds) of a webhook timestamp before it
     * is considered stale and rejected. Default: 5 minutes.
     */
    private const DEFAULT_TOLERANCE = 300;

    /**
     * Verify the webhook signature and construct the event object.
     *
     * @param string $payload       Raw request body (`file_get_contents('php://input')`).
     * @param string $signatureHeader Value of the `X-SmsPay-Signature` header.
     * @param string $timestampHeader Value of the `X-SmsPay-Timestamp` header.
     * @param string $secret        Your webhook signing secret (`whsec_xxxx`).
     * @param int    $tolerance     Maximum allowed timestamp age in seconds.
     * @return SmsPayObject Hydrated event object with `type` and `data` properties.
     *
     * @throws ApiException If the signature is invalid or the timestamp is stale.
     */
    public static function constructEvent(
        string $payload,
        string $signatureHeader,
        string $timestampHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE
    ): SmsPayObject {
        self::verifySignature($payload, $signatureHeader, $timestampHeader, $secret, $tolerance);

        $decoded = json_decode($payload, true);

        if (!is_array($decoded)) {
            throw new ApiException('Invalid webhook payload: unable to decode JSON.', null, $payload);
        }

        // Hydrate `data` into its own SmsPayObject for convenient property access.
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $decoded['data'] = SmsPayObject::fromArray($decoded['data']);
        }

        return SmsPayObject::fromArray($decoded);
    }

    /**
     * Verify the HMAC-SHA256 signature and timestamp freshness.
     *
     * @param string $payload
     * @param string $signatureHeader
     * @param string $timestampHeader
     * @param string $secret
     * @param int    $tolerance
     *
     * @throws ApiException
     */
    public static function verifySignature(
        string $payload,
        string $signatureHeader,
        string $timestampHeader,
        string $secret,
        int $tolerance = self::DEFAULT_TOLERANCE
    ): void {
        $timestamp = (int) $timestampHeader;

        // Guard against replay attacks.
        if ($tolerance > 0 && abs(time() - $timestamp) > $tolerance) {
            throw new ApiException(
                'Webhook timestamp is outside the tolerance window. '
                . 'The event may be stale or replayed.',
                null,
                null
            );
        }

        $signedPayload   = $timestamp . '.' . $payload;
        $expectedSignature = hash_hmac('sha256', $signedPayload, $secret);

        if (!hash_equals($expectedSignature, $signatureHeader)) {
            throw new ApiException(
                'Webhook signature verification failed. '
                . 'Ensure the signing secret matches your SmsPay dashboard configuration.',
                null,
                null
            );
        }
    }
}
