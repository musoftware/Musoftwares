// ---------------------------------------------------------------------------
// Tests — Webhook Signature Verification
// ---------------------------------------------------------------------------

import * as crypto from 'crypto';
import { Webhook, WebhookSignatureError } from '../src';

// ─── Helpers ────────────────────────────────────────────────────────────────

/**
 * Compute a valid HMAC-SHA256 signature for the given timestamp + payload.
 */
function sign(payload: string, timestamp: string, secret: string): string {
    return crypto
        .createHmac('sha256', secret)
        .update(`${timestamp}.${payload}`, 'utf8')
        .digest('hex');
}

const WEBHOOK_SECRET = 'whsec_test_secret_key_abc123';

const SAMPLE_EVENT = {
    id: 'evt_001',
    type: 'checkout.session.completed',
    data: {
        id: 'cs_test_001',
        url: 'https://pay.example.com/cs_test_001',
        amount: 150,
        currency: 'EGP',
        status: 'complete',
        payment_status: 'paid',
        success_url: 'https://merchant.com/success',
        cancel_url: 'https://merchant.com/cancel',
        metadata: { orderId: '12345' },
        description: null,
        customer_email: 'customer@example.com',
        customer_phone: '+201234567890',
        created_at: '2026-05-31T15:00:00Z',
        expires_at: '2026-05-31T15:30:00Z',
        completed_at: '2026-05-31T15:03:00Z',
    },
    created_at: '2026-05-31T15:03:00Z',
};

// ─── Test Suite ─────────────────────────────────────────────────────────────

describe('Webhook', () => {
    describe('constructEvent()', () => {
        it('should verify a valid signature and return a parsed event', () => {
            const payload = JSON.stringify(SAMPLE_EVENT);
            const timestamp = String(Math.floor(Date.now() / 1000));
            const signature = sign(payload, timestamp, WEBHOOK_SECRET);

            const event = Webhook.constructEvent(
                payload,
                signature,
                timestamp,
                WEBHOOK_SECRET,
            );

            expect(event.id).toBe('evt_001');
            expect(event.type).toBe('checkout.session.completed');
            expect(event.data.id).toBe('cs_test_001');
            expect(event.data.amount).toBe(150);
            expect(event.data.currency).toBe('EGP');
            expect(event.data.status).toBe('complete');
            expect(event.data.paymentStatus).toBe('paid');
            expect(event.data.customerEmail).toBe('customer@example.com');
            expect(event.data.customerPhone).toBe('+201234567890');
            expect(event.data.metadata).toEqual({ orderId: '12345' });
            expect(event.createdAt).toBe('2026-05-31T15:03:00Z');
        });

        it('should accept Buffer payload', () => {
            const payload = JSON.stringify(SAMPLE_EVENT);
            const timestamp = String(Math.floor(Date.now() / 1000));
            const signature = sign(payload, timestamp, WEBHOOK_SECRET);

            const event = Webhook.constructEvent(
                Buffer.from(payload, 'utf8'),
                signature,
                timestamp,
                WEBHOOK_SECRET,
            );

            expect(event.id).toBe('evt_001');
            expect(event.type).toBe('checkout.session.completed');
        });

        it('should throw WebhookSignatureError for invalid signature', () => {
            const payload = JSON.stringify(SAMPLE_EVENT);
            const timestamp = String(Math.floor(Date.now() / 1000));

            expect(() =>
                Webhook.constructEvent(
                    payload,
                    'invalidsignature',
                    timestamp,
                    WEBHOOK_SECRET,
                ),
            ).toThrow(WebhookSignatureError);
        });

        it('should throw WebhookSignatureError for tampered payload', () => {
            const payload = JSON.stringify(SAMPLE_EVENT);
            const timestamp = String(Math.floor(Date.now() / 1000));
            const signature = sign(payload, timestamp, WEBHOOK_SECRET);

            // Tamper with the payload
            const tampered = payload.replace('"amount":150', '"amount":9999');

            expect(() =>
                Webhook.constructEvent(
                    tampered,
                    signature,
                    timestamp,
                    WEBHOOK_SECRET,
                ),
            ).toThrow(WebhookSignatureError);
        });

        it('should throw WebhookSignatureError for wrong secret', () => {
            const payload = JSON.stringify(SAMPLE_EVENT);
            const timestamp = String(Math.floor(Date.now() / 1000));
            const signature = sign(payload, timestamp, 'wrong_secret');

            expect(() =>
                Webhook.constructEvent(
                    payload,
                    signature,
                    timestamp,
                    WEBHOOK_SECRET,
                ),
            ).toThrow(WebhookSignatureError);
        });

        it('should throw WebhookSignatureError for expired timestamp', () => {
            const payload = JSON.stringify(SAMPLE_EVENT);
            // 10 minutes ago (exceeds 5-min default tolerance)
            const oldTimestamp = String(Math.floor(Date.now() / 1000) - 600);
            const signature = sign(payload, oldTimestamp, WEBHOOK_SECRET);

            expect(() =>
                Webhook.constructEvent(
                    payload,
                    signature,
                    oldTimestamp,
                    WEBHOOK_SECRET,
                ),
            ).toThrow(WebhookSignatureError);

            expect(() =>
                Webhook.constructEvent(
                    payload,
                    signature,
                    oldTimestamp,
                    WEBHOOK_SECRET,
                ),
            ).toThrow(/tolerance window/);
        });

        it('should accept a custom tolerance window', () => {
            const payload = JSON.stringify(SAMPLE_EVENT);
            // 4 minutes ago — within a 5-min tolerance but outside a 3-min tolerance
            const ts = String(Math.floor(Date.now() / 1000) - 240);
            const signature = sign(payload, ts, WEBHOOK_SECRET);

            // Should fail with 3-minute tolerance
            expect(() =>
                Webhook.constructEvent(payload, signature, ts, WEBHOOK_SECRET, 180),
            ).toThrow(WebhookSignatureError);

            // Should pass with default 5-minute tolerance
            const event = Webhook.constructEvent(payload, signature, ts, WEBHOOK_SECRET, 300);
            expect(event.id).toBe('evt_001');
        });
    });

    describe('verifySignature()', () => {
        it('should not throw for a valid signature', () => {
            const payload = JSON.stringify(SAMPLE_EVENT);
            const timestamp = String(Math.floor(Date.now() / 1000));
            const signature = sign(payload, timestamp, WEBHOOK_SECRET);

            expect(() =>
                Webhook.verifySignature(payload, signature, timestamp, WEBHOOK_SECRET),
            ).not.toThrow();
        });

        it('should throw for an invalid signature', () => {
            const payload = JSON.stringify(SAMPLE_EVENT);
            const timestamp = String(Math.floor(Date.now() / 1000));

            expect(() =>
                Webhook.verifySignature(payload, 'bad_sig', timestamp, WEBHOOK_SECRET),
            ).toThrow(WebhookSignatureError);
        });
    });

    describe('Input validation', () => {
        it('should throw when payload is empty', () => {
            expect(() =>
                Webhook.constructEvent('', 'sig', '12345', WEBHOOK_SECRET),
            ).toThrow(WebhookSignatureError);
            expect(() =>
                Webhook.constructEvent('', 'sig', '12345', WEBHOOK_SECRET),
            ).toThrow(/payload must not be empty/);
        });

        it('should throw when signature header is missing', () => {
            expect(() =>
                Webhook.constructEvent('{}', '', '12345', WEBHOOK_SECRET),
            ).toThrow(WebhookSignatureError);
            expect(() =>
                Webhook.constructEvent('{}', '', '12345', WEBHOOK_SECRET),
            ).toThrow(/Missing X-SmsPay-Signature/);
        });

        it('should throw when timestamp header is missing', () => {
            expect(() =>
                Webhook.constructEvent('{}', 'sig', '', WEBHOOK_SECRET),
            ).toThrow(WebhookSignatureError);
            expect(() =>
                Webhook.constructEvent('{}', 'sig', '', WEBHOOK_SECRET),
            ).toThrow(/Missing X-SmsPay-Timestamp/);
        });

        it('should throw when secret is empty', () => {
            expect(() =>
                Webhook.constructEvent('{}', 'sig', '12345', ''),
            ).toThrow(WebhookSignatureError);
            expect(() =>
                Webhook.constructEvent('{}', 'sig', '12345', ''),
            ).toThrow(/secret must not be empty/);
        });

        it('should throw when timestamp is not a valid integer', () => {
            expect(() =>
                Webhook.constructEvent('{}', 'sig', 'not_a_number', WEBHOOK_SECRET),
            ).toThrow(WebhookSignatureError);
            expect(() =>
                Webhook.constructEvent('{}', 'sig', 'not_a_number', WEBHOOK_SECRET),
            ).toThrow(/not a valid integer/);
        });

        it('should throw when payload is not valid JSON', () => {
            const payload = 'not json';
            const timestamp = String(Math.floor(Date.now() / 1000));
            const signature = sign(payload, timestamp, WEBHOOK_SECRET);

            expect(() =>
                Webhook.constructEvent(payload, signature, timestamp, WEBHOOK_SECRET),
            ).toThrow(WebhookSignatureError);
            expect(() =>
                Webhook.constructEvent(payload, signature, timestamp, WEBHOOK_SECRET),
            ).toThrow(/Failed to parse/);
        });
    });
});
