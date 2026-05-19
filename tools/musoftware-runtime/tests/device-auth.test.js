const crypto = require('crypto');
const DeviceAuth = require('../core/device-auth');
const { saveConfig } = require('../core/config');

jest.mock('crypto');
jest.mock('child_process', () => ({
    exec: jest.fn(),
}));
jest.mock('../core/config', () => ({
    saveConfig: jest.fn(),
}));

describe('DeviceAuth', () => {
    let deviceAuth;
    let config;
    let logger;
    let broadcast;
    let onConnected;

    beforeEach(() => {
        config = { platformUrl: 'http://localhost:8000', port: 18400 };
        logger = { info: jest.fn(), warn: jest.fn(), debug: jest.fn() };
        broadcast = jest.fn();
        onConnected = jest.fn();
        deviceAuth = new DeviceAuth(config, logger, broadcast, onConnected);

        crypto.randomBytes.mockReturnValue(Buffer.from('test_code_buffer'));
    });

    test('startLogin generates a code and opens browser', () => {
        const result = deviceAuth.startLogin();
        expect(result.code).toBe('746573745f636f64655f627566666572'); // hex of test_code_buffer
        expect(deviceAuth.hasPendingLogin).toBe(true);
    });

    test('handleCallback rejects if no pending login', () => {
        const result = deviceAuth.handleCallback({});
        expect(result.ok).toBe(false);
        expect(result.error).toBe('no_pending_login');
    });

    test('handleCallback processes valid login', () => {
        const loginData = deviceAuth.startLogin();

        const result = deviceAuth.handleCallback({
            token: 'test_token',
            userId: 'user_123',
            userName: 'Test User',
            device_code: loginData.code,
        });

        expect(result.ok).toBe(true);
        expect(deviceAuth.isAuthenticated).toBe(true);
        expect(saveConfig).toHaveBeenCalledWith({ token: 'test_token', userId: 'user_123' });
        expect(broadcast).toHaveBeenCalledWith('auth.connected', expect.any(Object));
        expect(onConnected).toHaveBeenCalledWith('test_token', 'user_123', 'Test User');
    });

    test('handleCallback rejects invalid device code', () => {
        deviceAuth.startLogin();
        const result = deviceAuth.handleCallback({
            token: 'test_token',
            userId: 'user_123',
            device_code: 'invalid_code',
        });

        expect(result.ok).toBe(false);
        expect(result.error).toBe('invalid_code');
        expect(deviceAuth.isAuthenticated).toBe(false);
    });
});
