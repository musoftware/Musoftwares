# SmsPay Node.js SDK

Official Node.js SDK for **SmsPay** — the SMS-based mobile wallet payment gateway by [Musoftwares](https://www.musoftwares.com).

Accept payments from Vodafone Cash, Instapay, and other mobile wallets using a simple, Stripe-like API.

[![npm version](https://img.shields.io/npm/v/@musoftware/smspay.svg)](https://www.npmjs.com/package/@musoftware/smspay)
[![Node.js](https://img.shields.io/badge/node-%3E%3D14-brightgreen.svg)](https://nodejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-Ready-blue.svg)](https://www.typescriptlang.org/)

---

## Features

- **Zero dependencies** — uses native Node.js `https`/`http` modules
- **TypeScript-first** — full type definitions included
- **Dual module format** — works with both CommonJS (`require`) and ES Modules (`import`)
- **Node.js 14+** compatible
- **Stripe-like API** — familiar, intuitive developer experience
- **Webhook verification** — HMAC-SHA256 signature verification with timing-safe comparison

---

## Installation

```bash
npm install @musoftware/smspay
```

```bash
yarn add @musoftware/smspay
```

---

## Quick Start

### Express.js Example

```typescript
import express from 'express';
import SmsPay from '@musoftware/smspay';

const app = express();
const smspay = new SmsPay('sk_live_your_secret_key');

// Create a checkout session
app.post('/create-checkout', async (req, res) => {
    try {
        const session = await smspay.checkoutSessions.create({
            amount: 150.00,
            currency: 'EGP',
            successUrl: 'https://yoursite.com/success?session_id={SESSION_ID}',
            cancelUrl: 'https://yoursite.com/cancel',
            metadata: {
                orderId: req.body.orderId,
            },
        });

        res.json({ url: session.url });
    } catch (err) {
        console.error(err);
        res.status(500).json({ error: 'Payment session creation failed.' });
    }
});

app.listen(3000);
```

### CommonJS

```javascript
const SmsPay = require('@musoftware/smspay');

const smspay = new SmsPay('sk_live_your_secret_key');
```

---

## API Reference

### Initialization

```typescript
import SmsPay from '@musoftware/smspay';

const smspay = new SmsPay('sk_live_xxxx', {
    baseUrl: 'https://www.musoftwares.com', // optional, defaults to production
    timeout: 30000,                          // optional, request timeout in ms
});
```

| Option     | Type     | Default                        | Description              |
|------------|----------|--------------------------------|--------------------------|
| `baseUrl`  | `string` | `https://www.musoftwares.com`  | API base URL             |
| `timeout`  | `number` | `30000`                        | Request timeout (ms)     |

---

### Checkout Sessions

#### Create a Session

```typescript
const session = await smspay.checkoutSessions.create({
    amount: 150.00,
    currency: 'EGP',
    successUrl: 'https://merchant.com/success?session_id={SESSION_ID}',
    cancelUrl: 'https://merchant.com/cancel',
    metadata: { orderId: '12345' },
    description: 'Premium subscription',
    customerEmail: 'customer@example.com',
    customerPhone: '+201234567890',
    expiresInMinutes: 15,
});

console.log(session.id);  // 'cs_xxxx'
console.log(session.url); // 'https://www.musoftwares.com/pay/cs_xxxx'

// Redirect your customer to session.url to complete payment
```

| Parameter          | Type                       | Required | Description                                      |
|--------------------|----------------------------|----------|--------------------------------------------------|
| `amount`           | `number`                   | ✅       | Payment amount                                   |
| `currency`         | `string`                   | ✅       | ISO 4217 currency code (e.g. `'EGP'`)            |
| `successUrl`       | `string`                   | ✅       | Redirect URL on success (`{SESSION_ID}` replaced) |
| `cancelUrl`        | `string`                   | ✅       | Redirect URL on cancellation                     |
| `metadata`         | `Record<string, string>`   | ❌       | Arbitrary key-value metadata                     |
| `description`      | `string`                   | ❌       | Description shown during payment                 |
| `customerEmail`    | `string`                   | ❌       | Pre-fill customer email                          |
| `customerPhone`    | `string`                   | ❌       | Pre-fill customer phone                          |
| `expiresInMinutes` | `number`                   | ❌       | Session expiry (default: 30 min)                 |

#### Retrieve a Session

```typescript
const session = await smspay.checkoutSessions.retrieve('cs_xxxx');

console.log(session.status);        // 'open' | 'complete' | 'expired'
console.log(session.paymentStatus); // 'unpaid' | 'paid' | 'failed'
```

#### Expire a Session

```typescript
const session = await smspay.checkoutSessions.expire('cs_xxxx');

console.log(session.status); // 'expired'
```

---

### Webhooks

SmsPay sends webhook events to your server when payment events occur. Verify the signature before processing.

#### Express.js Webhook Handler

```typescript
import express from 'express';
import SmsPay from '@musoftware/smspay';

const app = express();
const smspay = new SmsPay('sk_live_xxxx');

// ⚠️ IMPORTANT: Use express.raw() for the webhook route to get the raw body
app.post(
    '/webhooks/smspay',
    express.raw({ type: 'application/json' }),
    (req, res) => {
        const signature = req.headers['x-smspay-signature'] as string;
        const timestamp = req.headers['x-smspay-timestamp'] as string;

        try {
            const event = smspay.webhooks.constructEvent(
                req.body,           // raw Buffer
                signature,
                timestamp,
                'whsec_your_webhook_secret',
            );

            switch (event.type) {
                case 'checkout.session.completed':
                    const session = event.data;
                    console.log(`Payment received: ${session.id}`);
                    // Fulfill the order using session.metadata
                    break;

                case 'checkout.session.expired':
                    console.log(`Session expired: ${event.data.id}`);
                    break;

                default:
                    console.log(`Unhandled event type: ${event.type}`);
            }

            res.json({ received: true });
        } catch (err) {
            console.error('Webhook verification failed:', err);
            res.status(400).json({ error: 'Webhook verification failed.' });
        }
    },
);

app.listen(3000);
```

#### Signature Details

SmsPay signs each webhook with HMAC-SHA256:

```
signature = HMAC-SHA256(
    key: webhook_secret,
    message: "{timestamp}.{raw_body}"
)
```

Headers sent with each webhook:

| Header                  | Description                          |
|-------------------------|--------------------------------------|
| `X-SmsPay-Signature`    | HMAC-SHA256 hex signature            |
| `X-SmsPay-Timestamp`    | Unix timestamp (seconds)             |

---

### Error Handling

The SDK throws typed errors that you can catch and handle:

```typescript
import SmsPay, {
    SmsPayError,
    AuthenticationError,
    InvalidRequestError,
    ApiError,
    WebhookSignatureError,
} from '@musoftware/smspay';

const smspay = new SmsPay('sk_live_xxxx');

try {
    await smspay.checkoutSessions.create({ /* ... */ });
} catch (err) {
    if (err instanceof AuthenticationError) {
        // Invalid API key (HTTP 401)
        console.error('Auth failed:', err.message);
    } else if (err instanceof InvalidRequestError) {
        // Validation error (HTTP 400/422)
        console.error('Validation:', err.message);
        console.error('Field errors:', err.errors);
    } else if (err instanceof ApiError) {
        // Server error (HTTP 5xx) or network failure
        console.error('Server error:', err.message);
    } else if (err instanceof SmsPayError) {
        // Any other SmsPay error
        console.error('SmsPay error:', err.message, err.statusCode);
    } else {
        throw err; // Unknown error, re-throw
    }
}
```

#### Error Types

| Error Class              | HTTP Code | Description                       |
|--------------------------|-----------|-----------------------------------|
| `AuthenticationError`    | 401       | Invalid or missing API key        |
| `InvalidRequestError`   | 400 / 422 | Malformed request or validation   |
| `ApiError`               | 5xx       | Server error or network failure   |
| `WebhookSignatureError`  | —         | Webhook signature mismatch        |
| `SmsPayError`            | *         | Base class for all SDK errors     |

All errors include:
- `message` — Human-readable error description
- `type` — Error category (`authentication_error`, `invalid_request_error`, `api_error`, `webhook_error`)
- `statusCode` — HTTP status code
- `errors` — Field-level validation errors (on `InvalidRequestError`)

---

### TypeScript Support

The SDK is written in TypeScript and ships with full type definitions. All interfaces are exported:

```typescript
import SmsPay, {
    type SmsPayConfig,
    type CheckoutSessionCreateParams,
    type CheckoutSession,
    type WebhookEvent,
    type ApiResponse,
} from '@musoftware/smspay';
```

---

## Development

```bash
# Install dependencies
npm install

# Build (CJS + ESM + type declarations)
npm run build

# Run tests
npm test
```

---

## Requirements

- **Node.js** ≥ 14.0.0
- **Zero runtime dependencies**

---

## License

MIT © [Musoftwares](https://www.musoftwares.com)
