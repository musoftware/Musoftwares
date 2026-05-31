// ---------------------------------------------------------------------------
// @musoftware/smspay — TypeScript Interfaces & Types
// ---------------------------------------------------------------------------

/**
 * Configuration options for the SmsPay client.
 */
export interface SmsPayConfig {
    /**
     * Base URL of the SmsPay API.
     * @default 'https://www.musoftwares.com'
     */
    baseUrl?: string;

    /**
     * Request timeout in milliseconds.
     * @default 30000
     */
    timeout?: number;

    /**
     * API version prefix.
     * @default '/api/smspay/v1'
     */
    apiVersion?: string;
}

// ---------------------------------------------------------------------------
// Checkout Sessions
// ---------------------------------------------------------------------------

/**
 * Parameters for creating a new checkout session.
 */
export interface CheckoutSessionCreateParams {
    /** Payment amount (e.g. 150.00). Must be a positive number. */
    amount: number;

    /** ISO 4217 currency code (e.g. 'EGP'). */
    currency: string;

    /**
     * URL the customer is redirected to after successful payment.
     * Use `{SESSION_ID}` as a placeholder — the gateway will substitute it.
     */
    successUrl: string;

    /** URL the customer is redirected to if they cancel the payment. */
    cancelUrl: string;

    /** Optional arbitrary key-value metadata attached to the session. */
    metadata?: Record<string, string | number | boolean>;

    /** Optional description shown to the customer during payment. */
    description?: string;

    /** Optional customer email to pre-fill in the payment form. */
    customerEmail?: string;

    /** Optional customer phone to pre-fill in the payment form. */
    customerPhone?: string;

    /**
     * Session expiration in minutes from creation.
     * @default 30
     */
    expiresInMinutes?: number;
}

/**
 * A checkout session object returned from the API.
 */
export interface CheckoutSession {
    /** Unique session identifier (e.g. 'cs_xxxx'). */
    id: string;

    /** The full payment page URL to redirect the customer to. */
    url: string;

    /** Payment amount in the smallest representable unit. */
    amount: number;

    /** ISO 4217 currency code. */
    currency: string;

    /** Current session status. */
    status: 'open' | 'complete' | 'expired';

    /** Payment status. */
    paymentStatus: 'unpaid' | 'paid' | 'failed';

    /** URL the customer returns to on success. */
    successUrl: string;

    /** URL the customer returns to on cancel. */
    cancelUrl: string;

    /** Arbitrary metadata associated with this session. */
    metadata: Record<string, string | number | boolean> | null;

    /** Optional description. */
    description: string | null;

    /** Customer email if provided. */
    customerEmail: string | null;

    /** Customer phone if provided. */
    customerPhone: string | null;

    /** ISO 8601 creation timestamp. */
    createdAt: string;

    /** ISO 8601 expiration timestamp. */
    expiresAt: string;

    /** ISO 8601 completion timestamp, or null if not yet completed. */
    completedAt: string | null;
}

// ---------------------------------------------------------------------------
// Webhooks
// ---------------------------------------------------------------------------

/**
 * A verified webhook event.
 */
export interface WebhookEvent {
    /** Unique event identifier. */
    id: string;

    /** Event type (e.g. 'checkout.session.completed'). */
    type: string;

    /** The event payload — typically a CheckoutSession. */
    data: CheckoutSession;

    /** ISO 8601 timestamp of when the event was created. */
    createdAt: string;
}

// ---------------------------------------------------------------------------
// API Response Envelope
// ---------------------------------------------------------------------------

/**
 * Raw API response wrapper returned by the gateway.
 */
export interface ApiResponse<T = unknown> {
    status: 'success' | 'error';
    data?: T;
    message?: string;
    errors?: Record<string, string[]>;
}

// ---------------------------------------------------------------------------
// Error Details
// ---------------------------------------------------------------------------

/**
 * Structured error detail returned by the API.
 */
export interface SmsPayErrorDetail {
    message: string;
    type: 'authentication_error' | 'invalid_request_error' | 'api_error' | 'webhook_error';
    statusCode: number;
    errors?: Record<string, string[]>;
}
