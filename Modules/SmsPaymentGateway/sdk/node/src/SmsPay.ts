// ---------------------------------------------------------------------------
// @musoftware/smspay — Main Client
// ---------------------------------------------------------------------------

import * as https from 'https';
import * as http from 'http';
import { URL } from 'url';

import type { SmsPayConfig, WebhookEvent } from './types';
import { CheckoutSessions } from './resources/CheckoutSessions';
import { Webhook } from './Webhook';
import {
    AuthenticationError,
    InvalidRequestError,
    ApiError,
    SmsPayError,
} from './errors';

/**
 * Default configuration values.
 */
const DEFAULTS = {
    BASE_URL: 'https://www.musoftwares.com',
    TIMEOUT: 30_000,
    API_VERSION: '/api/smspay/v1',
} as const;

/**
 * SDK version injected into the `User-Agent` header.
 */
const SDK_VERSION = '1.0.0';

/**
 * The main SmsPay client.
 *
 * Create an instance with your secret API key and use the attached resource
 * objects to interact with the SmsPay payment gateway.
 *
 * @example
 * ```ts
 * import SmsPay from '@musoftware/smspay';
 *
 * const smspay = new SmsPay('sk_live_xxxx');
 *
 * const session = await smspay.checkoutSessions.create({
 *     amount: 150.00,
 *     currency: 'EGP',
 *     successUrl: 'https://merchant.com/success?session_id={SESSION_ID}',
 *     cancelUrl: 'https://merchant.com/cancel',
 * });
 *
 * console.log(session.url); // redirect your customer here
 * ```
 */
export class SmsPay {
    /** The secret API key used for authentication. */
    private readonly apiKey: string;

    /** Resolved base URL (without trailing slash). */
    private readonly baseUrl: string;

    /** Request timeout in ms. */
    private readonly timeout: number;

    /** API version path prefix. */
    private readonly apiVersion: string;

    // ── Resource namespaces ─────────────────────────────────────────────

    /**
     * Checkout session operations (create, retrieve, expire).
     */
    readonly checkoutSessions: CheckoutSessions;

    /**
     * Webhook verification utilities.
     *
     * @example
     * ```ts
     * const event = smspay.webhooks.constructEvent(body, sig, ts, secret);
     * ```
     */
    readonly webhooks: {
        /**
         * Verify a webhook signature and parse the event payload.
         */
        constructEvent: (
            payload: string | Buffer,
            signature: string,
            timestamp: string,
            secret: string,
            tolerance?: number,
        ) => WebhookEvent;

        /**
         * Verify a webhook signature without parsing the body.
         */
        verifySignature: (
            payload: string | Buffer,
            signature: string,
            timestamp: string,
            secret: string,
            tolerance?: number,
        ) => void;
    };

    // ── Constructor ─────────────────────────────────────────────────────

    /**
     * Instantiate a new SmsPay client.
     *
     * @param apiKey - Your secret API key (`sk_live_…` or `sk_test_…`).
     * @param config - Optional configuration overrides.
     */
    constructor(apiKey: string, config: SmsPayConfig = {}) {
        if (!apiKey || typeof apiKey !== 'string') {
            throw new AuthenticationError(
                'An API key must be provided. You can find your API key in the SmsPay dashboard.',
            );
        }

        this.apiKey = apiKey;
        this.baseUrl = (config.baseUrl ?? DEFAULTS.BASE_URL).replace(/\/+$/, '');
        this.timeout = config.timeout ?? DEFAULTS.TIMEOUT;
        this.apiVersion = config.apiVersion ?? DEFAULTS.API_VERSION;

        // Mount resources.
        this.checkoutSessions = new CheckoutSessions(this);

        // Mount webhook helpers (static methods proxied for convenience).
        this.webhooks = {
            constructEvent: Webhook.constructEvent.bind(Webhook),
            verifySignature: Webhook.verifySignature.bind(Webhook),
        };
    }

    // ── HTTP transport (internal) ───────────────────────────────────────

    /**
     * Perform an authenticated HTTP request to the SmsPay API.
     *
     * This method is **internal** — resource classes call it to execute
     * requests. It handles JSON encoding/decoding, error mapping, timeouts,
     * and header injection.
     *
     * @internal
     * @param method - HTTP method.
     * @param path   - API path relative to the version prefix (e.g. `/checkout/sessions`).
     * @param body   - Optional request body (will be JSON-stringified).
     * @returns Parsed JSON response.
     */
    async _request<T>(method: string, path: string, body?: Record<string, unknown>): Promise<T> {
        const fullUrl = `${this.baseUrl}${this.apiVersion}${path}`;
        const url = new URL(fullUrl);

        const payload = body ? JSON.stringify(body) : undefined;

        const isHttps = url.protocol === 'https:';
        const transport = isHttps ? https : http;

        const options: https.RequestOptions = {
            hostname: url.hostname,
            port: url.port || (isHttps ? 443 : 80),
            path: url.pathname + url.search,
            method: method.toUpperCase(),
            headers: {
                'Authorization': `Bearer ${this.apiKey}`,
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'User-Agent': `SmsPay/Node.js/${SDK_VERSION}`,
            },
            timeout: this.timeout,
        };

        if (payload) {
            (options.headers as Record<string, string>)['Content-Length'] = Buffer.byteLength(payload, 'utf8').toString();
        }

        return new Promise<T>((resolve, reject) => {
            const req = transport.request(options, (res) => {
                const chunks: Buffer[] = [];

                res.on('data', (chunk: Buffer) => chunks.push(chunk));

                res.on('end', () => {
                    const rawBody = Buffer.concat(chunks).toString('utf8');
                    const statusCode = res.statusCode ?? 0;

                    // ── Parse JSON ──────────────────────────────────
                    let parsed: Record<string, unknown>;
                    try {
                        parsed = JSON.parse(rawBody);
                    } catch {
                        if (statusCode >= 500) {
                            return reject(
                                new ApiError(
                                    `API returned an invalid JSON response (HTTP ${statusCode}).`,
                                    statusCode,
                                ),
                            );
                        }
                        return reject(
                            new SmsPayError({
                                message: `Unexpected response format (HTTP ${statusCode}).`,
                                type: 'api_error',
                                statusCode,
                            }),
                        );
                    }

                    // ── Map HTTP errors to typed exceptions ─────────
                    if (statusCode >= 400) {
                        const message = (parsed.message as string) ?? 'An error occurred.';
                        const errors = parsed.errors as Record<string, string[]> | undefined;

                        if (statusCode === 401) {
                            return reject(new AuthenticationError(message));
                        }
                        if (statusCode === 400 || statusCode === 422) {
                            return reject(new InvalidRequestError(message, statusCode, errors));
                        }
                        if (statusCode >= 500) {
                            return reject(new ApiError(message, statusCode));
                        }

                        return reject(
                            new SmsPayError({
                                message,
                                type: 'api_error',
                                statusCode,
                                errors,
                            }),
                        );
                    }

                    resolve(parsed as unknown as T);
                });
            });

            // ── Timeout handling ────────────────────────────────────────
            req.on('timeout', () => {
                req.destroy();
                reject(
                    new ApiError(
                        `Request timed out after ${this.timeout}ms.`,
                        408,
                    ),
                );
            });

            // ── Network errors ──────────────────────────────────────────
            req.on('error', (err: NodeJS.ErrnoException) => {
                reject(
                    new ApiError(
                        `Network error: ${err.message}`,
                        0,
                    ),
                );
            });

            // ── Write body ──────────────────────────────────────────────
            if (payload) {
                req.write(payload);
            }
            req.end();
        });
    }
}

// Allow both `import SmsPay` and `const SmsPay = require(...)`.
export default SmsPay;
