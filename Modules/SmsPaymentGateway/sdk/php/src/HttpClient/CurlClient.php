<?php

namespace SmsPay\HttpClient;

use SmsPay\Exception\ApiException;
use SmsPay\Exception\AuthenticationException;
use SmsPay\Exception\InvalidRequestException;

/**
 * Minimal HTTP client built entirely on native PHP cURL.
 *
 * No external dependencies — only the `ext-curl` extension is required.
 */
class CurlClient
{
    /** @var string */
    private string $apiKey;

    /** @var string */
    private string $baseUrl;

    /** @var int Connection + execution timeout in seconds. */
    private int $timeout;

    /**
     * @param string $apiKey  Bearer token for API authentication.
     * @param string $baseUrl Gateway base URL (no trailing slash).
     * @param int    $timeout Request timeout in seconds (default 30).
     */
    public function __construct(string $apiKey, string $baseUrl, int $timeout = 30)
    {
        $this->apiKey  = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * Perform a GET request.
     *
     * @param string               $path   API path (e.g. `/api/v1/checkout/sessions`).
     * @param array<string, mixed> $params Query-string parameters.
     * @return array<string, mixed> Decoded JSON response body.
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws InvalidRequestException
     */
    public function get(string $path, array $params = []): array
    {
        $url = $this->buildUrl($path);

        if (!empty($params)) {
            $url .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        }

        return $this->request('GET', $url);
    }

    /**
     * Perform a POST request with a JSON body.
     *
     * @param string               $path API path.
     * @param array<string, mixed> $data Request body (will be JSON-encoded).
     * @return array<string, mixed> Decoded JSON response body.
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws InvalidRequestException
     */
    public function post(string $path, array $data = []): array
    {
        $url = $this->buildUrl($path);

        return $this->request('POST', $url, $data);
    }

    /**
     * Perform a DELETE request.
     *
     * @param string $path API path.
     * @return array<string, mixed> Decoded JSON response body.
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws InvalidRequestException
     */
    public function delete(string $path): array
    {
        $url = $this->buildUrl($path);

        return $this->request('DELETE', $url);
    }

    // -------------------------------------------------------------------------
    //  Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Build the full URL from a relative path.
     *
     * @param string $path
     * @return string
     */
    private function buildUrl(string $path): string
    {
        return $this->baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Execute a cURL request and handle the response.
     *
     * @param string                    $method HTTP verb.
     * @param string                    $url    Fully-qualified URL.
     * @param array<string, mixed>|null $data   JSON body (POST only).
     * @return array<string, mixed>
     *
     * @throws ApiException
     * @throws AuthenticationException
     * @throws InvalidRequestException
     */
    private function request(string $method, string $url, ?array $data = null): array
    {
        $ch = curl_init();

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: SmsPay-PHP-SDK/1.0',
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if ($method === 'POST' && $data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $responseBody = curl_exec($ch);
        $httpStatus   = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError    = curl_error($ch);
        $curlErrno    = curl_errno($ch);

        curl_close($ch);

        // cURL-level failure (network timeout, DNS resolution, etc.)
        if ($curlErrno !== 0) {
            throw new ApiException(
                sprintf('cURL error (%d): %s', $curlErrno, $curlError),
                null,
                null
            );
        }

        $decoded = json_decode((string) $responseBody, true);

        if ($httpStatus >= 200 && $httpStatus < 300) {
            return is_array($decoded) ? $decoded : [];
        }

        // Extract error message from the API response body.
        $errorMessage = 'Unknown error';
        $errorParam   = null;

        if (is_array($decoded)) {
            $errorMessage = $decoded['error']['message']
                ?? $decoded['error']
                ?? $decoded['message']
                ?? $errorMessage;

            $errorParam = $decoded['error']['param'] ?? null;
        }

        $bodyStr = is_string($responseBody) ? $responseBody : '';

        // Map HTTP status codes to specific exception types.
        switch ($httpStatus) {
            case 401:
                throw new AuthenticationException($errorMessage, $bodyStr);

            case 400:
            case 422:
                throw new InvalidRequestException(
                    $errorMessage,
                    is_string($errorParam) ? $errorParam : null,
                    $httpStatus,
                    $bodyStr
                );

            default:
                throw new ApiException($errorMessage, $httpStatus, $bodyStr);
        }
    }
}
