// ---------------------------------------------------------------------------
// Tests — SmsPay Client & CheckoutSessions
// ---------------------------------------------------------------------------

import * as http from 'http';
import { SmsPay, AuthenticationError, InvalidRequestError, ApiError } from '../src';
import { __ } from '@/lib/i18n';

// ─── Helpers ────────────────────────────────────────────────────────────────

/** Spin up a local HTTP server that returns a canned JSON response. */
function createMockServer(
    handler: (req: http.IncomingMessage, body: string) => { status: number; body: Record<string, unknown> },
): Promise<{ server: http.Server; port: number }> {
    return new Promise((resolve) => {
        const server = http.createServer((req, res) => {
            const chunks: Buffer[] = [];
            req.on('data', (c: Buffer) => chunks.push(c));
            req.on('end', () => {
                const raw = Buffer.concat(chunks).toString('utf8');
                const result = handler(req, raw);
                res.writeHead(result.status, { 'Content-Type': 'application/json' });
                res.end(JSON.stringify(result.body));
            });
        });
        server.listen(0, '127.0.0.1', () => {
            const addr = server.address() as { port: number };
            resolve({ server, port: addr.port });
        });
    });
}

function closeServer(server: http.Server): Promise<void> {
    return new Promise((resolve) => server.close(() => resolve()));
}

// ─── Test Suite ─────────────────────────────────────────────────────────────

describe('SmsPay Client', () => {
    // ── Constructor ─────────────────────────────────────────────────────

    it('should throw AuthenticationError when no API key is provided', () => {
        expect(() => new SmsPay('')).toThrow(AuthenticationError);
    });

    it('should throw AuthenticationError when API key is not a string', () => {
        // @ts-expect-error — intentionally passing wrong type
        expect(() => new SmsPay(123)).toThrow(AuthenticationError);
    });

    it('should instantiate successfully with a valid API key', () => {
        const client = new SmsPay('sk_test_abc123');
        expect(client).toBeInstanceOf(SmsPay);
        expect(client.checkoutSessions).toBeDefined();
        expect(client.webhooks).toBeDefined();
        expect(client.webhooks.constructEvent).toBeTypeOf('function');
        expect(client.webhooks.verifySignature).toBeTypeOf('function');
    });

    it('should accept custom configuration', () => {
        const client = new SmsPay('sk_test_abc', {
            baseUrl: 'https://custom.example.com',
            timeout: 5000,
            apiVersion: '/api/v2',
        });
        expect(client).toBeInstanceOf(SmsPay);
    });
});

describe('CheckoutSessions', () => {
    let server: http.Server;
    let port: number;
    let client: SmsPay;

    // ── Create ──────────────────────────────────────────────────────────

    describe('.create()', () => {
        afterEach(async () => {
            if (server) await closeServer(server);
        });

        it('should create a checkout session and return camelCase fields', async () => {
            const mockSession = {
                id: 'cs_test_001',
                url: 'https://pay.example.com/cs_test_001',
                amount: 150.00,
                currency: 'EGP',
                status: 'open',
                payment_status: 'unpaid',
                success_url: 'https://merchant.com/success?session_id=cs_test_001',
                cancel_url: 'https://merchant.com/cancel',
                metadata: { orderId: '12345' },
                description: null,
                customer_email: null,
                customer_phone: null,
                created_at: '2026-05-31T15:00:00Z',
                expires_at: '2026-05-31T15:30:00Z',
                completed_at: null,
            };

            ({ server, port } = await createMockServer((req, body) => {
                expect(req.method).toBe('POST');
                expect(req.url).toContain('/checkout/sessions');
                expect(req.headers['authorization']).toBe('Bearer sk_test_create');
                expect(req.headers['content-type']).toBe('application/json');

                const parsed = JSON.parse(body);
                expect(parsed.amount).toBe(150);
                expect(parsed.currency).toBe('EGP');
                expect(parsed.success_url).toBeDefined();
                expect(parsed.cancel_url).toBeDefined();

                return {
                    status: 200,
                    body: { status: 'success', data: mockSession },
                };
            }));

            client = new SmsPay('sk_test_create', {
                baseUrl: `http://127.0.0.1:${port}`,
            });

            const session = await client.checkoutSessions.create({
                amount: 150,
                currency: 'EGP',
                successUrl: 'https://merchant.com/success?session_id={SESSION_ID}',
                cancelUrl: 'https://merchant.com/cancel',
                metadata: { orderId: '12345' },
            });

            expect(session.id).toBe('cs_test_001');
            expect(session.url).toContain('cs_test_001');
            expect(session.amount).toBe(150);
            expect(session.currency).toBe('EGP');
            expect(session.status).toBe('open');
            expect(session.paymentStatus).toBe('unpaid');
            expect(session.successUrl).toContain('merchant.com');
            expect(session.cancelUrl).toContain('merchant.com');
            expect(session.metadata).toEqual({ orderId: '12345' });
            expect(session.createdAt).toBe('2026-05-31T15:00:00Z');
            expect(session.expiresAt).toBe('2026-05-31T15:30:00Z');
            expect(session.completedAt).toBeNull();
        });

        it('should send optional fields when provided', async () => {
            ({ server, port } = await createMockServer((_req, body) => {
                const parsed = JSON.parse(body);
                expect(parsed.customer_email).toBe('test@example.com');
                expect(parsed.customer_phone).toBe('+201234567890');
                expect(parsed.description).toBe('Order #99');
                expect(parsed.expires_in_minutes).toBe(15);

                return {
                    status: 200,
                    body: {
                        status: 'success',
                        data: { id: 'cs_opt', url: '', amount: 50, currency: 'EGP', status: 'open', payment_status: 'unpaid', success_url: '', cancel_url: '', metadata: null, description: 'Order #99', customer_email: 'test@example.com', customer_phone: '+201234567890', created_at: '', expires_at: '', completed_at: null },
                    },
                };
            }));

            client = new SmsPay('sk_test_opt', { baseUrl: `http://127.0.0.1:${port}` });

            const session = await client.checkoutSessions.create({
                amount: 50,
                currency: 'EGP',
                successUrl: 'https://m.com/ok',
                cancelUrl: 'https://m.com/no',
                customerEmail: 'test@example.com',
                customerPhone: '+201234567890',
                description: 'Order #99',
                expiresInMinutes: 15,
            });

            expect(session.customerEmail).toBe('test@example.com');
            expect(session.description).toBe('Order #99');
        });
    });

    // ── Retrieve ────────────────────────────────────────────────────────

    describe('.retrieve()', () => {
        afterEach(async () => {
            if (server) await closeServer(server);
        });

        it('should retrieve a checkout session by ID', async () => {
            ({ server, port } = await createMockServer((req) => {
                expect(req.method).toBe('GET');
                expect(req.url).toContain('/checkout/sessions/cs_ret_001');

                return {
                    status: 200,
                    body: {
                        status: 'success',
                        data: {
                            id: 'cs_ret_001', url: 'https://pay.example.com/cs_ret_001',
                            amount: 200, currency: 'EGP', status: 'complete',
                            payment_status: 'paid', success_url: '', cancel_url: '',
                            metadata: null, description: null,
                            customer_email: null, customer_phone: null,
                            created_at: '2026-05-31T12:00:00Z',
                            expires_at: '2026-05-31T12:30:00Z',
                            completed_at: '2026-05-31T12:05:00Z',
                        },
                    },
                };
            }));

            client = new SmsPay('sk_test_ret', { baseUrl: `http://127.0.0.1:${port}` });

            const session = await client.checkoutSessions.retrieve('cs_ret_001');
            expect(session.id).toBe('cs_ret_001');
            expect(session.status).toBe('complete');
            expect(session.paymentStatus).toBe('paid');
            expect(session.completedAt).toBe('2026-05-31T12:05:00Z');
        });
    });

    // ── Expire ──────────────────────────────────────────────────────────

    describe('.expire()', () => {
        afterEach(async () => {
            if (server) await closeServer(server);
        });

        it('should expire a checkout session', async () => {
            ({ server, port } = await createMockServer((req) => {
                expect(req.method).toBe('POST');
                expect(req.url).toContain('/checkout/sessions/cs_exp_001/expire');

                return {
                    status: 200,
                    body: {
                        status: 'success',
                        data: {
                            id: 'cs_exp_001', url: '', amount: 100, currency: 'EGP',
                            status: 'expired', payment_status: 'unpaid',
                            success_url: '', cancel_url: '', metadata: null,
                            description: null, customer_email: null, customer_phone: null,
                            created_at: '', expires_at: '', completed_at: null,
                        },
                    },
                };
            }));

            client = new SmsPay('sk_test_exp', { baseUrl: `http://127.0.0.1:${port}` });

            const session = await client.checkoutSessions.expire('cs_exp_001');
            expect(session.status).toBe('expired');
        });
    });

    // ── Error handling ──────────────────────────────────────────────────

    describe('Error handling', () => {
        afterEach(async () => {
            if (server) await closeServer(server);
        });

        it('should throw AuthenticationError on 401', async () => {
            ({ server, port } = await createMockServer(() => ({
                status: 401,
                body: { status: 'error', message: 'Invalid API key.' },
            })));

            client = new SmsPay('sk_bad', { baseUrl: `http://127.0.0.1:${port}` });

            await expect(client.checkoutSessions.retrieve('cs_x')).rejects.toThrow(AuthenticationError);
            await expect(client.checkoutSessions.retrieve('cs_x')).rejects.toMatchObject({
                statusCode: 401,
                type: 'authentication_error',
            });
        });

        it('should throw InvalidRequestError on 422 with field errors', async () => {
            ({ server, port } = await createMockServer(() => ({
                status: 422,
                body: {
                    status: 'error',
                    message: 'Validation failed.',
                    errors: {
                        amount: ['The amount field is required.'],
                        currency: ['The currency field is required.'],
                    },
                },
            })));

            client = new SmsPay('sk_test_val', { baseUrl: `http://127.0.0.1:${port}` });

            try {
                await client.checkoutSessions.create({
                    amount: 0,
                    currency: '',
                    successUrl: '',
                    cancelUrl: '',
                });
                fail('Should have thrown');
            } catch (err) {
                expect(err).toBeInstanceOf(InvalidRequestError);
                const typedErr = err as InvalidRequestError;
                expect(typedErr.statusCode).toBe(422);
                expect(typedErr.errors).toBeDefined();
                expect(typedErr.errors!.amount).toContain('The amount field is required.');
            }
        });

        it('should throw ApiError on 500', async () => {
            ({ server, port } = await createMockServer(() => ({
                status: 500,
                body: { status: 'error', message: 'Internal server error.' },
            })));

            client = new SmsPay('sk_test_500', { baseUrl: `http://127.0.0.1:${port}` });

            await expect(client.checkoutSessions.retrieve('cs_z')).rejects.toThrow(ApiError);
            await expect(client.checkoutSessions.retrieve('cs_z')).rejects.toMatchObject({
                statusCode: 500,
                type: 'api_error',
            });
        });

        it('should throw ApiError on invalid JSON response', async () => {
            ({ server, port } = await createMockServer(() => {
                // Return a response that will produce invalid JSON
                return { status: 502, body: {} };
            }));

            // Override to send raw invalid response
            await closeServer(server);

            server = http.createServer((_req, res) => {
                res.writeHead(502, { 'Content-Type': 'text/html' });
                res.end('<html>{__('general.bad_gateway')}</html>');
            });

            await new Promise<void>((resolve) => {
                server.listen(port, '127.0.0.1', resolve);
            });

            client = new SmsPay('sk_test_bad_json', { baseUrl: `http://127.0.0.1:${port}` });

            await expect(client.checkoutSessions.retrieve('cs_y')).rejects.toThrow(ApiError);
        });

        it('should throw ApiError on network error', async () => {
            // Use a port that nothing listens on
            client = new SmsPay('sk_test_net', { baseUrl: 'http://127.0.0.1:1' });

            await expect(client.checkoutSessions.retrieve('cs_net')).rejects.toThrow(ApiError);
        });
    });
});

// ─── Custom matchers ────────────────────────────────────────────────────────

declare global {
    // eslint-disable-next-line @typescript-eslint/no-namespace
    namespace jest {
        interface Matchers<R> {
            toBeTypeOf(expected: string): R;
        }
    }
}

expect.extend({
    toBeTypeOf(received: unknown, expected: string) {
        const pass = typeof received === expected;
        return {
            pass,
            message: () =>
                `expected typeof ${String(received)} to be "${expected}", but got "${typeof received}"`,
        };
    },
});
