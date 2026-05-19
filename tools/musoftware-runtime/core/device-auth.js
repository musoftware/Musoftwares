'use strict';

/**
 * Device Auth Flow
 * ═══════════════════════════════════════════════════════════════
 * Implements a browser-based login handshake — no token ever
 * touches a config file or env var in production.
 *
 * Flow:
 *   1. Runtime generates a one-time device_code (crypto random)
 *   2. Runtime opens the user's browser to:
 *        {platform}/runtime/connect?code={device_code}
 *   3. User logs in on the website normally
 *   4. Website POSTs back to runtime:
 *        POST http://127.0.0.1:18400/auth/callback
 *        { token, userId, userName }
 *   5. Runtime saves token to config/runtime.json (encrypted-at-rest optional)
 *   6. Runtime broadcasts 'auth.connected' via WS + shows tray notification
 *
 * The device_code expires in 10 minutes. A new one is generated on each
 * login attempt to prevent replay.
 */

const crypto = require('crypto');
const { exec } = require('child_process');
const { saveConfig } = require('./config');

const CODE_TTL_MS = 10 * 60 * 1000; // 10 minutes

class DeviceAuth {
    constructor(config, logger, broadcast, onConnected) {
        this.config      = config;
        this.logger      = logger;
        this.broadcast   = broadcast;
        this.onConnected = onConnected; // called with (token, userId, userName)
        this._pending    = null;        // { code, expiresAt }
    }

    // ── Generate code + open browser ─────────────────────────────────────────

    startLogin() {
        const code = crypto.randomBytes(24).toString('hex');
        this._pending = { code, expiresAt: Date.now() + CODE_TTL_MS };

        const url = `${this.config.platformUrl}/runtime/connect?code=${code}&port=${this.config.port}`;
        this.logger.info(`[auth] Opening browser login: ${url}`);

        this._openBrowser(url);
        this.logger.info('[auth] Waiting for callback from platform...');

        // Auto-expire the pending code
        setTimeout(() => {
            if (this._pending?.code === code) {
                this._pending = null;
                this.logger.debug('[auth] Device code expired');
            }
        }, CODE_TTL_MS);

        return { code, url, expiresIn: CODE_TTL_MS / 1000 };
    }

    // ── Validate callback from platform ───────────────────────────────────────

    handleCallback({ token, userId, userName, device_code }) {
        if (!this._pending) {
            return { ok: false, error: 'no_pending_login', message: 'No login in progress. Start from the tray.' };
        }
        if (Date.now() > this._pending.expiresAt) {
            this._pending = null;
            return { ok: false, error: 'code_expired', message: 'Login link expired. Try again.' };
        }
        if (device_code !== this._pending.code) {
            this.logger.warn('[auth] Invalid device_code received — possible replay attempt');
            return { ok: false, error: 'invalid_code', message: 'Invalid code.' };
        }
        if (!token || !userId) {
            return { ok: false, error: 'missing_fields', message: 'token and userId required' };
        }

        // ✓ Valid — persist and notify
        this._pending = null;
        saveConfig({ token, userId: String(userId) });
        this.config.token  = token;
        this.config.userId = String(userId);

        this.logger.info(`[auth] ✓ Connected — user ${userName || userId} (id=${userId})`);
        this.broadcast('auth.connected', { userId, userName, ts: Date.now() });
        this.onConnected(token, userId, userName);

        return { ok: true, userName, userId };
    }

    get isAuthenticated() {
        return !!this.config.token;
    }

    get hasPendingLogin() {
        return !!this._pending && Date.now() < this._pending.expiresAt;
    }

    // ── Platform → Runtime CORS ───────────────────────────────────────────────
    // The /auth/callback endpoint only accepts requests from the platform origin

    isValidCallbackOrigin(origin = '') {
        const platform = this.config.platformUrl;
        // Allow same-origin (for local dev) or the production platform
        const localhostPattern = /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/;
        return !origin || origin.startsWith(platform) || localhostPattern.test(origin);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    _openBrowser(url) {
        const platform = process.platform;
        const cmd =
            platform === 'win32'  ? `start "" "${url}"` :
            platform === 'darwin' ? `open "${url}"` :
                                    `xdg-open "${url}"`;
        exec(cmd, err => {
            if (err) this.logger.warn(`[auth] Could not open browser: ${err.message}`);
        });
    }
}

module.exports = DeviceAuth;
