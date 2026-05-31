# SmsPay PHP SDK

Official PHP SDK for the **SmsPay SMS Payment Gateway** — accept mobile wallet payments with SMS verification on any PHP website.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D7.4-8892BF.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

---

## Table of Contents

- [Installation](#installation)
- [Quick Start](#quick-start)
- [Checkout Sessions](#checkout-sessions)
  - [Create a Session](#create-a-session)
  - [Retrieve a Session](#retrieve-a-session)
  - [Expire a Session](#expire-a-session)
- [Handling Webhooks](#handling-webhooks)
- [Error Handling](#error-handling)
- [Configuration](#configuration)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Requirements](#requirements)

---

## Installation

Install the SDK via Composer:

```bash
composer require musoftware/smspay-php
```

> **Zero dependencies** — the SDK uses native PHP cURL only. No Guzzle required.

---

## Quick Start

```php
<?php

require_once 'vendor/autoload.php';

// 1. Initialise the client
$smspay = new \SmsPay\SmsPay('sk_live_your_secret_key', [
    'base_url' => 'https://www.musoftwares.com', // Your gateway URL
]);

// 2. Create a checkout session
$session = $smspay->checkoutSessions->create([
    'amount'      => 150.00,
    'currency'    => 'EGP',
    'success_url' => 'https://yoursite.com/success?session_id={SESSION_ID}',
    'cancel_url'  => 'https://yoursite.com/cancel',
    'metadata'    => ['order_id' => '12345'],
]);

// 3. Redirect the customer to the payment page
header('Location: ' . $session->url);
exit;
```

---

## Checkout Sessions

Checkout sessions are the primary way to collect payments. You create a session server-side, redirect your customer to the hosted payment page, then listen for webhook events to confirm fulfilment.

### Create a Session

```php
$session = $smspay->checkoutSessions->create([
    'amount'         => 250.00,
    'currency'       => 'EGP',
    'success_url'    => 'https://yoursite.com/success?session_id={SESSION_ID}',
    'cancel_url'     => 'https://yoursite.com/cancel',
    'customer_phone' => '+201234567890',           // Optional: pre-fill phone
    'description'    => 'Premium Plan — Monthly',  // Optional
    'metadata'       => [                          // Optional: your custom data
        'order_id'    => 'ORD-2026-001',
        'customer_id' => 'cus_abc123',
    ],
]);

echo $session->id;     // "cs_xxxxxxxxxxxxxxxx"
echo $session->url;    // "https://www.musoftwares.com/pay/cs_xxx..."
echo $session->status; // "open"
```

| Parameter        | Type   | Required | Description                                              |
| ---------------- | ------ | -------- | -------------------------------------------------------- |
| `amount`         | float  | ✅       | Amount to charge.                                        |
| `currency`       | string | ✅       | Three-letter ISO currency code (e.g. `EGP`, `SAR`).     |
| `success_url`    | string | ✅       | Redirect URL on success. `{SESSION_ID}` is replaced.     |
| `cancel_url`     | string | ✅       | Redirect URL if the customer cancels.                    |
| `metadata`       | array  | ❌       | Key-value pairs attached to the session.                 |
| `description`    | string | ❌       | Human-readable description shown on the payment page.    |
| `customer_phone` | string | ❌       | Pre-fill the customer's phone number.                    |

### Retrieve a Session

```php
$session = $smspay->checkoutSessions->retrieve('cs_xxxxxxxxxxxxxxxx');

echo $session->id;              // "cs_xxxxxxxxxxxxxxxx"
echo $session->status;          // "open" | "complete" | "expired"
echo $session->amount;          // 250.00
echo $session->payment_status;  // "unpaid" | "paid"
```

### Expire a Session

Cancel an open session so it can no longer be completed:

```php
$session = $smspay->checkoutSessions->expire('cs_xxxxxxxxxxxxxxxx');

echo $session->status; // "expired"
```

---

## Handling Webhooks

SmsPay sends webhook events to your server when payment status changes. Always verify the signature before processing.

### 1. Set Up Your Endpoint

```php
<?php
// webhook.php

require_once 'vendor/autoload.php';

$payload   = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_SMSPAY_SIGNATURE'] ?? '';
$timestamp = $_SERVER['HTTP_X_SMSPAY_TIMESTAMP'] ?? '';
$secret    = 'whsec_your_webhook_secret';

try {
    $event = \SmsPay\Webhook::constructEvent(
        $payload,
        $signature,
        $timestamp,
        $secret
    );
} catch (\SmsPay\Exception\ApiException $e) {
    // Invalid signature — reject the request.
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

// Handle the event
switch ($event->type) {
    case 'checkout.session.completed':
        $session = $event->data;
        // Fulfil the order using $session->metadata->order_id
        fulfillOrder($session);
        break;

    case 'checkout.session.expired':
        // Handle expiration
        break;

    default:
        // Unexpected event type
        break;
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
```

### 2. Signature Verification

The SDK verifies webhooks using **HMAC-SHA256**:

- **Signed payload:** `{timestamp}.{raw_body}`
- **Algorithm:** SHA-256
- **Tolerance:** 5 minutes (configurable)

```php
// Custom tolerance (in seconds) — e.g. 10 minutes:
$event = \SmsPay\Webhook::constructEvent(
    $payload, $signature, $timestamp, $secret, 600
);

// Disable timestamp check entirely:
$event = \SmsPay\Webhook::constructEvent(
    $payload, $signature, $timestamp, $secret, 0
);
```

### 3. Verify Only (Without Constructing)

```php
\SmsPay\Webhook::verifySignature($payload, $signature, $timestamp, $secret);
// Throws ApiException if invalid — otherwise silently passes.
```

---

## Error Handling

The SDK throws typed exceptions so you can handle each failure mode precisely:

```php
use SmsPay\Exception\ApiException;
use SmsPay\Exception\AuthenticationException;
use SmsPay\Exception\InvalidRequestException;

try {
    $session = $smspay->checkoutSessions->create([
        'amount'      => 150.00,
        'currency'    => 'EGP',
        'success_url' => 'https://yoursite.com/success',
        'cancel_url'  => 'https://yoursite.com/cancel',
    ]);
} catch (AuthenticationException $e) {
    // 401 — Invalid API key
    echo "Authentication failed: " . $e->getMessage();

} catch (InvalidRequestException $e) {
    // 400 or 422 — Validation error
    echo "Invalid request: " . $e->getMessage();
    echo "Problem parameter: " . $e->getErrorParam();

} catch (ApiException $e) {
    // Any other API error (500, 502, etc.)
    echo "API error ({$e->getHttpStatus()}): " . $e->getMessage();

    // Access the raw response body:
    $body = $e->getJsonBody();
}
```

| Exception                  | HTTP Status | When                                      |
| -------------------------- | ----------- | ----------------------------------------- |
| `AuthenticationException`  | 401         | Missing, invalid, or revoked API key.     |
| `InvalidRequestException`  | 400 / 422   | Malformed request or validation failure.  |
| `ApiException`             | Any other   | Server errors, network failures, etc.     |

---

## Configuration

```php
$smspay = new \SmsPay\SmsPay('sk_live_xxxx', [
    'base_url' => 'https://gateway.example.com', // Default: https://www.musoftwares.com
    'timeout'  => 60,                             // Default: 30 seconds
]);
```

| Option     | Type   | Default                          | Description               |
| ---------- | ------ | -------------------------------- | ------------------------- |
| `base_url` | string | `https://www.musoftwares.com`    | Gateway server URL.       |
| `timeout`  | int    | `30`                             | cURL timeout in seconds.  |

---

## API Reference

### `SmsPay\SmsPay`

| Method / Property       | Returns                | Description                  |
| ----------------------- | ---------------------- | ---------------------------- |
| `->checkoutSessions`    | `CheckoutSessions`     | Checkout session operations. |
| `->getApiKey()`         | `string`               | The configured API key.      |
| `->getHttpClient()`     | `CurlClient`           | The underlying HTTP client.  |

### `SmsPay\Resources\CheckoutSessions`

| Method                  | Returns         | Description                          |
| ----------------------- | --------------- | ------------------------------------ |
| `->create(array)`       | `SmsPayObject`  | Create a new checkout session.       |
| `->retrieve(string)`    | `SmsPayObject`  | Retrieve a session by ID.            |
| `->expire(string)`      | `SmsPayObject`  | Expire an open session.              |

### `SmsPay\Webhook`

| Method                                             | Returns        | Description                               |
| -------------------------------------------------- | -------------- | ----------------------------------------- |
| `::constructEvent($payload, $sig, $ts, $secret)`   | `SmsPayObject` | Verify signature & construct the event.   |
| `::verifySignature($payload, $sig, $ts, $secret)`  | `void`         | Verify signature only (throws on failure).|

### `SmsPay\SmsPayObject`

| Method / Access       | Returns        | Description                           |
| --------------------- | -------------- | ------------------------------------- |
| `->propertyName`      | `mixed\|null`  | Dynamic property access.              |
| `->toArray()`         | `array`        | Convert back to associative array.    |
| `(string) $obj`       | `string`       | JSON representation.                  |

---

## Testing

```bash
# Install dev dependencies
composer install

# Run the test suite
composer test
# or
./vendor/bin/phpunit
```

---

## Requirements

- **PHP** ≥ 7.4
- **ext-curl** (native PHP cURL extension)
- **ext-json** (bundled with PHP)
- **No external dependencies** — no Guzzle, no Symfony HTTP Client.

---

## License

MIT — see [LICENSE](LICENSE) for details.
