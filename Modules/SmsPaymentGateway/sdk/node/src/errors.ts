// ---------------------------------------------------------------------------
// @musoftware/smspay — Custom Error Classes
// ---------------------------------------------------------------------------

import type { SmsPayErrorDetail } from './types';

/**
 * Base error class for all SmsPay SDK errors.
 *
 * Extends the native `Error` with structured API error metadata so callers
 * can programmatically inspect `type`, `statusCode`, and field-level `errors`.
 */
export class SmsPayError extends Error {
    /** Error category returned by the API. */
    readonly type: SmsPayErrorDetail['type'];

    /** HTTP status code associated with this error. */
    readonly statusCode: number;

    /** Optional field-level validation errors. */
    readonly errors?: Record<string, string[]>;

    constructor(detail: SmsPayErrorDetail) {
        super(detail.message);
        this.name = 'SmsPayError';
        this.type = detail.type;
        this.statusCode = detail.statusCode;
        this.errors = detail.errors;

        // Maintain proper prototype chain for instanceof checks in Node 14+.
        Object.setPrototypeOf(this, new.target.prototype);
    }
}

/**
 * Raised when the API key is missing, malformed, or revoked.
 *
 * HTTP 401.
 */
export class AuthenticationError extends SmsPayError {
    constructor(message: string) {
        super({
            message,
            type: 'authentication_error',
            statusCode: 401,
        });
        this.name = 'AuthenticationError';
        Object.setPrototypeOf(this, new.target.prototype);
    }
}

/**
 * Raised when the request is malformed or contains invalid parameters.
 *
 * HTTP 400 / 422.
 */
export class InvalidRequestError extends SmsPayError {
    constructor(message: string, statusCode: number = 400, errors?: Record<string, string[]>) {
        super({
            message,
            type: 'invalid_request_error',
            statusCode,
            errors,
        });
        this.name = 'InvalidRequestError';
        Object.setPrototypeOf(this, new.target.prototype);
    }
}

/**
 * Raised when the API returns a server-side error (5xx).
 */
export class ApiError extends SmsPayError {
    constructor(message: string, statusCode: number = 500) {
        super({
            message,
            type: 'api_error',
            statusCode,
        });
        this.name = 'ApiError';
        Object.setPrototypeOf(this, new.target.prototype);
    }
}

/**
 * Raised when webhook signature verification fails.
 */
export class WebhookSignatureError extends SmsPayError {
    constructor(message: string) {
        super({
            message,
            type: 'webhook_error',
            statusCode: 400,
        });
        this.name = 'WebhookSignatureError';
        Object.setPrototypeOf(this, new.target.prototype);
    }
}
