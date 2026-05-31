<?php

namespace SmsPay\Resources;

use SmsPay\HttpClient\CurlClient;
use SmsPay\SmsPayObject;

/**
 * Manage checkout sessions — the primary way to collect payments.
 *
 * A checkout session represents a single payment attempt. The merchant
 * creates a session, redirects the customer to the hosted payment page,
 * and listens for webhook events to confirm fulfilment.
 *
 * @see https://docs.musoftwares.com/smspay/checkout-sessions
 */
class CheckoutSessions
{
    /** @var CurlClient */
    private CurlClient $client;

    /**
     * @param CurlClient $client
     */
    public function __construct(CurlClient $client)
    {
        $this->client = $client;
    }

    /**
     * Create a new checkout session.
     *
     * Required parameters:
     *  - `amount`      (float)  — Amount to charge (e.g. 150.00).
     *  - `currency`    (string) — Three-letter ISO currency code (e.g. "EGP").
     *  - `success_url` (string) — URL to redirect after successful payment.
     *                             Use `{SESSION_ID}` as a placeholder.
     *  - `cancel_url`  (string) — URL to redirect if the customer cancels.
     *
     * Optional parameters:
     *  - `metadata`    (array)  — Arbitrary key-value pairs attached to the session.
     *  - `description` (string) — Human-readable payment description.
     *  - `customer_phone` (string) — Pre-fill the customer phone number.
     *
     * ```php
     * $session = $smspay->checkoutSessions->create([
     *     'amount'      => 150.00,
     *     'currency'    => 'EGP',
     *     'success_url' => 'https://merchant.com/success?session_id={SESSION_ID}',
     *     'cancel_url'  => 'https://merchant.com/cancel',
     *     'metadata'    => ['order_id' => '12345'],
     * ]);
     * ```
     *
     * @param array<string, mixed> $params Session creation parameters.
     * @return SmsPayObject The created session object.
     *
     * @throws \SmsPay\Exception\InvalidRequestException
     * @throws \SmsPay\Exception\AuthenticationException
     * @throws \SmsPay\Exception\ApiException
     */
    public function create(array $params): SmsPayObject
    {
        $response = $this->client->post('api/v1/checkout/sessions', $params);

        return SmsPayObject::fromArray($response['data'] ?? $response);
    }

    /**
     * Retrieve an existing checkout session by its ID.
     *
     * ```php
     * $session = $smspay->checkoutSessions->retrieve('cs_xxxx');
     * echo $session->status; // "open", "complete", "expired"
     * ```
     *
     * @param string $sessionId The checkout session identifier (e.g. `cs_xxxx`).
     * @return SmsPayObject The session object.
     *
     * @throws \SmsPay\Exception\InvalidRequestException
     * @throws \SmsPay\Exception\AuthenticationException
     * @throws \SmsPay\Exception\ApiException
     */
    public function retrieve(string $sessionId): SmsPayObject
    {
        $response = $this->client->get('api/v1/checkout/sessions/' . urlencode($sessionId));

        return SmsPayObject::fromArray($response['data'] ?? $response);
    }

    /**
     * Expire a checkout session so it can no longer be completed.
     *
     * Only sessions with status `open` can be expired. Once expired,
     * the customer will see an expiration notice if they visit the
     * payment page.
     *
     * ```php
     * $session = $smspay->checkoutSessions->expire('cs_xxxx');
     * echo $session->status; // "expired"
     * ```
     *
     * @param string $sessionId The checkout session identifier.
     * @return SmsPayObject The updated session object.
     *
     * @throws \SmsPay\Exception\InvalidRequestException
     * @throws \SmsPay\Exception\AuthenticationException
     * @throws \SmsPay\Exception\ApiException
     */
    public function expire(string $sessionId): SmsPayObject
    {
        $response = $this->client->post(
            'api/v1/checkout/sessions/' . urlencode($sessionId) . '/expire'
        );

        return SmsPayObject::fromArray($response['data'] ?? $response);
    }
}
