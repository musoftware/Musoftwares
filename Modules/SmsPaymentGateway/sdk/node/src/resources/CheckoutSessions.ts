// ---------------------------------------------------------------------------
// @musoftware/smspay — CheckoutSessions Resource
// ---------------------------------------------------------------------------

import type { SmsPay } from '../SmsPay';
import type {
    CheckoutSession,
    CheckoutSessionCreateParams,
    ApiResponse,
} from '../types';

/**
 * Provides CRUD-style methods for managing checkout sessions.
 *
 * Accessed via `smspay.checkoutSessions`.
 *
 * @example
 * ```ts
 * // Create a session
 * const session = await smspay.checkoutSessions.create({
 *     amount: 150.00,
 *     currency: 'EGP',
 *     successUrl: 'https://merchant.com/success?session_id={SESSION_ID}',
 *     cancelUrl: 'https://merchant.com/cancel',
 * });
 *
 * // Retrieve it later
 * const retrieved = await smspay.checkoutSessions.retrieve(session.id);
 *
 * // Expire it manually
 * await smspay.checkoutSessions.expire(session.id);
 * ```
 */
export class CheckoutSessions {
    private client: SmsPay;

    /** @internal */
    constructor(client: SmsPay) {
        this.client = client;
    }

    /**
     * Create a new checkout session.
     *
     * Returns the created session including the `url` that the customer
     * should be redirected to in order to complete payment.
     *
     * @param params - Session creation parameters.
     * @returns The newly created {@link CheckoutSession}.
     */
    async create(params: CheckoutSessionCreateParams): Promise<CheckoutSession> {
        const body = CheckoutSessions.toSnakeCase(params);
        const response = await this.client._request<ApiResponse<Record<string, unknown>>>(
            'POST',
            '/checkout/sessions',
            body,
        );
        return CheckoutSessions.normalizeSession(response.data!);
    }

    /**
     * Retrieve an existing checkout session by its ID.
     *
     * @param sessionId - The session identifier (e.g. `'cs_xxxx'`).
     * @returns The {@link CheckoutSession} object.
     */
    async retrieve(sessionId: string): Promise<CheckoutSession> {
        const response = await this.client._request<ApiResponse<Record<string, unknown>>>(
            'GET',
            `/checkout/sessions/${encodeURIComponent(sessionId)}`,
        );
        return CheckoutSessions.normalizeSession(response.data!);
    }

    /**
     * Expire a checkout session so it can no longer be used for payment.
     *
     * Only sessions with status `open` can be expired.
     *
     * @param sessionId - The session identifier.
     * @returns The updated {@link CheckoutSession}.
     */
    async expire(sessionId: string): Promise<CheckoutSession> {
        const response = await this.client._request<ApiResponse<Record<string, unknown>>>(
            'POST',
            `/checkout/sessions/${encodeURIComponent(sessionId)}/expire`,
        );
        return CheckoutSessions.normalizeSession(response.data!);
    }

    // ─── Internal helpers ───────────────────────────────────────────────

    /**
     * Convert camelCase create-params to the snake_case format the API expects.
     */
    private static toSnakeCase(params: CheckoutSessionCreateParams): Record<string, unknown> {
        const result: Record<string, unknown> = {
            amount: params.amount,
            currency: params.currency,
            success_url: params.successUrl,
            cancel_url: params.cancelUrl,
        };

        if (params.metadata !== undefined) result.metadata = params.metadata;
        if (params.description !== undefined) result.description = params.description;
        if (params.customerEmail !== undefined) result.customer_email = params.customerEmail;
        if (params.customerPhone !== undefined) result.customer_phone = params.customerPhone;
        if (params.expiresInMinutes !== undefined) result.expires_in_minutes = params.expiresInMinutes;

        return result;
    }

    /**
     * Normalise a raw API response object into a typed {@link CheckoutSession}.
     */
    private static normalizeSession(raw: Record<string, unknown>): CheckoutSession {
        return {
            id: String(raw.id ?? ''),
            url: String(raw.url ?? ''),
            amount: Number(raw.amount ?? 0),
            currency: String(raw.currency ?? ''),
            status: (raw.status as CheckoutSession['status']) ?? 'open',
            paymentStatus: String(raw.payment_status ?? raw.paymentStatus ?? 'unpaid') as CheckoutSession['paymentStatus'],
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
