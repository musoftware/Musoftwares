<?php

namespace SmsPay;

use SmsPay\HttpClient\CurlClient;
use SmsPay\Resources\CheckoutSessions;

/**
 * SmsPay — the main entry point for the SDK.
 *
 * Initialise the client with your secret API key and (optionally) a
 * custom base URL pointing to your SmsPay gateway server.
 *
 * ```php
 * $smspay = new \SmsPay\SmsPay('sk_live_xxxx', [
 *     'base_url' => 'https://www.musoftwares.com',
 * ]);
 *
 * // Create a checkout session
 * $session = $smspay->checkoutSessions->create([
 *     'amount'      => 150.00,
 *     'currency'    => 'EGP',
 *     'success_url' => 'https://merchant.com/success?session_id={SESSION_ID}',
 *     'cancel_url'  => 'https://merchant.com/cancel',
 * ]);
 *
 * // Redirect the customer
 * header('Location: ' . $session->url);
 * ```
 *
 * @property-read CheckoutSessions $checkoutSessions
 */
class SmsPay
{
    /** @var string SDK version. */
    public const VERSION = '1.0.0';

    /** @var string Default gateway base URL. */
    private const DEFAULT_BASE_URL = 'https://www.musoftwares.com';

    /** @var string The secret API key. */
    private string $apiKey;

    /** @var CurlClient Shared HTTP client instance. */
    private CurlClient $httpClient;

    /** @var CheckoutSessions Checkout session resource. */
    public CheckoutSessions $checkoutSessions;

    /**
     * Create a new SmsPay client.
     *
     * @param string               $apiKey  Your secret API key (e.g. `sk_live_xxxx`).
     * @param array<string, mixed> $options Optional configuration:
     *   - `base_url` (string): Override the gateway URL.
     *   - `timeout`  (int):    cURL timeout in seconds (default 30).
     *
     * @throws \InvalidArgumentException If the API key is empty.
     */
    public function __construct(string $apiKey, array $options = [])
    {
        if (trim($apiKey) === '') {
            throw new \InvalidArgumentException(
                'An API key is required. Pass your secret key as the first argument: '
                . 'new \SmsPay\SmsPay(\'sk_live_xxxx\')'
            );
        }

        $this->apiKey = $apiKey;

        $baseUrl = $options['base_url'] ?? self::DEFAULT_BASE_URL;
        $timeout = (int) ($options['timeout'] ?? 30);

        $this->httpClient = new CurlClient($this->apiKey, $baseUrl, $timeout);

        // Initialise API resources.
        $this->checkoutSessions = new CheckoutSessions($this->httpClient);
    }

    /**
     * Retrieve the API key in use.
     *
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Retrieve the underlying HTTP client (useful for advanced usage).
     *
     * @return CurlClient
     */
    public function getHttpClient(): CurlClient
    {
        return $this->httpClient;
    }
}
