// ---------------------------------------------------------------------------
// @musoftware/smspay — Webhook Signature Verification
// ---------------------------------------------------------------------------

import * as crypto from 'crypto';
import type { WebhookEvent } from './types';
import { WebhookSignatureError } from './errors';

/**
 * Default tolerance window for timestamp validation (5 minutes in seconds).
 */
const DEFAULT_TOLERANCE_SECONDS = 300;

/**
 * Webhook utilities for verifying SmsPay webhook event signatures.
 *
 * SmsPay signs every webhook delivery with an HMAC-SHA256 signature computed
 * over the string `{timestamp}.{payload}` using your webhook secret. This
 * class lets you verify that signature and safely parse the event payload.
 *
 * @example
 * ```ts
 * const event = Webhook.constructEvent(
 *     req.body,               // raw body string or Buffer
 *     req.headers['x-smspay-signature'],
 *     req.headers['x-smspay-timestamp'],
 *     'whsec_xxxx',
 * );
 * ```
 */
export class Webhook {
    /**
     * Verify the webhook signature and parse the event payload.
     *
     * @param payload   - Raw request body (string or Buffer).
     * @param signature - Value of the `X-SmsPay-Signature` header.
     * @param timestamp - Value of the `X-SmsPay-Timestamp` header.
     * @param secret    - Your webhook signing secret (`whsec_xxxx`).
     * @param tolerance - Maximum allowed age of the event in seconds. Defaults to 300 (5 min).
     * @returns The parsed and verified {@link WebhookEvent}.
     * @throws {WebhookSignatureError} If verification fails.
     */
    static constructEvent(
        payload: string | Buffer,
        signature: string,
        timestamp: string,
        secret: string,
        tolerance: number = DEFAULT_TOLERANCE_SECONDS,
    ): WebhookEvent {
        Webhook.verifySignature(payload, signature, timestamp, secret, tolerance);

        const body = typeof payload === 'string' ? payload : payload.toString('utf8');

        try {
            const parsed = JSON.parse(body);
            return Webhook.normalizeEvent(parsed);
        } catch {
            throw new WebhookSignatureError(
                'Failed to parse webhook payload as JSON.',
            );
        }
    }

    /**
     * Verify the HMAC-SHA256 signature without parsing the body.
     *
     * @param payload   - Raw request body.
     * @param signature - Value of the `X-SmsPay-Signature` header.
     * @param timestamp - Value of the `X-SmsPay-Timestamp` header.
     * @param secret    - Webhook signing secret.
     * @param tolerance - Maximum age in seconds.
     * @throws {WebhookSignatureError} On any verification failure.
     */
    static verifySignature(
        payload: string | Buffer,
        signature: string,
        timestamp: string,
        secret: string,
        tolerance: number = DEFAULT_TOLERANCE_SECONDS,
    ): void {
        if (!payload) {
            throw new WebhookSignatureError('Webhook payload must not be empty.');
        }
        if (!signature) {
            throw new WebhookSignatureError(
                'Missing X-SmsPay-Signature header.',
            );
        }
        if (!timestamp) {
            throw new WebhookSignatureError(
                'Missing X-SmsPay-Timestamp header.',
            );
        }
        if (!secret) {
            throw new WebhookSignatureError('Webhook secret must not be empty.');
        }

        // ── Timestamp validation ────────────────────────────────────────
        const ts = parseInt(timestamp, 10);
        if (isNaN(ts)) {
            throw new WebhookSignatureError(
                'X-SmsPay-Timestamp header is not a valid integer.',
            );
        }

        const now = Math.floor(Date.now() / 1000);
        if (Math.abs(now - ts) > tolerance) {
            throw new WebhookSignatureError(
                `Webhook timestamp is outside the tolerance window of ${tolerance} seconds. ` +
                'This may indicate a replay attack or a clock skew.',
            );
        }

        // ── Signature computation & comparison ──────────────────────────
        const body = typeof payload === 'string' ? payload : payload.toString('utf8');
        const signedPayload = `${timestamp}.${body}`;
        const expectedSignature = crypto
            .createHmac('sha256', secret)
            .update(signedPayload, 'utf8')
            .digest('hex');

        // Constant-time comparison to prevent timing attacks.
        const sigBuffer = Buffer.from(signature, 'utf8');
        const expectedBuffer = Buffer.from(expectedSignature, 'utf8');

        if (
            sigBuffer.length !== expectedBuffer.length ||
            !crypto.timingSafeEqual(sigBuffer, expectedBuffer)
        ) {
            throw new WebhookSignatureError(
                'Webhook signature verification failed. The signature does not match the expected value.',
            );
        }
    }

    /**
     * Normalise a raw API event object into a camelCase {@link WebhookEvent}.
     */
    private static normalizeEvent(raw: Record<string, unknown>): WebhookEvent {
        return {
            id: String(raw.id ?? raw.event_id ?? ''),
            type: String(raw.type ?? raw.event_type ?? ''),
            data: Webhook.normalizeSession(raw.data as Record<string, unknown> ?? {}),
            createdAt: String(raw.created_at ?? raw.createdAt ?? ''),
        };
    }

    /**
     * Normalise a raw session object from snake_case to camelCase.
     */
    private static normalizeSession(raw: Record<string, unknown>): WebhookEvent['data'] {
        return {
            id: String(raw.id ?? ''),
            url: String(raw.url ?? ''),
            amount: Number(raw.amount ?? 0),
            currency: String(raw.currency ?? ''),
            status: (raw.status as WebhookEvent['data']['status']) ?? 'open',
            paymentStatus: (raw.payment_status ?? raw.paymentStatus ?? 'unpaid') as WebhookEvent['data']['paymentStatus'],
            successUrl: String(raw.success_url ?? raw.successUrl ?? ''),
            cancelUrl: String(raw.cancel_url ?? raw.cancelUrl ?? ''),
            metadata: (raw.metadata as Record<string, string | number | boolean>) ?? null,
            description: (raw.description as string) ?? null,
            customerEmail: String(raw.customer_email ?? raw.customerEmail ?? '') || null,
            customerPhone: String(raw.customer_phone ?? raw.customerPhone ?? '') || null,
            createdAt: String(raw.created_at ?? raw.createdAt ?? ''),
            expiresAt: String(raw.expires_at ?? raw.expiresAt ?? ''),
            completedAt: (raw.completed_at ?? raw.completedAt ?? null) as string | null,
        };
    }
}
