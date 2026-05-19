/**
 * WhatsApp Session Manager — Shared Module
 * ==========================================
 * Manages Puppeteer browser sessions for WhatsApp Web.
 * Handles multi-number session isolation, health checks,
 * rotation, backup/restore, and QR authentication.
 *
 * Usage:
 *   const SessionManager = require('../_shared/session-manager');
 *   const sm = new SessionManager({ log });
 *
 *   const session = await sm.acquire('my-session');
 *   // ... use session.page ...
 *   await sm.release('my-session');
 */

'use strict';

const path = require('path');
const fs   = require('fs');

// ── Constants ────────────────────────────────────────────────────────────────

const SESSIONS_DIR    = path.join(__dirname, '..', '_sessions');
const HEALTH_FILE     = 'session-health.json';
const MAX_RETRIES     = 3;
const WA_URL          = 'https://web.whatsapp.com';
const QR_TIMEOUT      = 300000; // 5 minutes for QR scan
const LOAD_TIMEOUT    = 30000;  // 30s for page load
const SESSION_TIMEOUT = 120000; // 2 min for session restore

// ── Selectors (WhatsApp Web 2026) ────────────────────────────────────────────

const SELECTORS = {
    chatList:    '[data-testid="chat-list"]',
    qrCanvas:    'canvas[aria-label*="QR"]',
    composeBox:  '[data-testid="conversation-compose-box-input"]',
    sendButton:  '[data-testid="send"], [data-icon="send"]',
    searchBox:   '[data-testid="chat-list-search"]',
    banNotice:   '[data-testid="alert-notification"]',
    popup:       '[data-testid="popup"]',
};

// ── Session States ───────────────────────────────────────────────────────────

const SESSION_STATES = {
    IDLE:          'idle',
    CONNECTING:    'connecting',
    QR_PENDING:    'qr_pending',
    CONNECTED:     'connected',
    DISCONNECTED:  'disconnected',
    BANNED:        'banned',
    ERROR:         'error',
};

// ── Class ────────────────────────────────────────────────────────────────────

class SessionManager {

    /**
     * @param {object} opts
     * @param {function} opts.log — (level, message) logger
     * @param {string}   opts.sessionsDir — override sessions directory
     * @param {object}   opts.proxyManager — optional ProxyManager instance
     */
    constructor(opts = {}) {
        this.log         = opts.log || (() => {});
        this.sessionsDir = opts.sessionsDir || SESSIONS_DIR;
        this.proxyMgr    = opts.proxyManager || null;
        this._sessions   = new Map(); // sessionId → { browser, page, state, health, lastActivity }

        // Ensure sessions directory exists
        if (!fs.existsSync(this.sessionsDir)) {
            fs.mkdirSync(this.sessionsDir, { recursive: true });
        }
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Acquire a session — launch browser + open WhatsApp.
     * Reuses existing browser if already connected.
     * @param {string} sessionId
     * @param {object} opts
     * @param {boolean} opts.headless — run headless (default false for QR)
     * @param {string}  opts.proxy — proxy URL override
     * @returns {{ page, browser, state }}
     */
    async acquire(sessionId, opts = {}) {
        const existing = this._sessions.get(sessionId);
        if (existing?.state === SESSION_STATES.CONNECTED) {
            this.log('info', `[session] Reusing connected session: ${sessionId}`);
            existing.lastActivity = Date.now();
            return existing;
        }

        this.log('info', `[session] Acquiring session: ${sessionId}`);

        const session = {
            browser: null,
            page: null,
            state: SESSION_STATES.CONNECTING,
            health: this._loadHealth(sessionId),
            lastActivity: Date.now(),
            startedAt: Date.now(),
        };
        this._sessions.set(sessionId, session);

        try {
            const { browser, page } = await this._launchBrowser(sessionId, opts);
            session.browser = browser;
            session.page    = page;

            await this._openWhatsApp(session, sessionId);

            session.state = SESSION_STATES.CONNECTED;
            session.health.lastConnected = Date.now();
            session.health.connectCount  = (session.health.connectCount || 0) + 1;
            this._saveHealth(sessionId, session.health);

            this.log('info', `[session] Connected: ${sessionId}`);
            return session;

        } catch (err) {
            session.state = SESSION_STATES.ERROR;
            session.health.lastError = err.message;
            session.health.errorCount = (session.health.errorCount || 0) + 1;
            this._saveHealth(sessionId, session.health);

            // Clean up on failure
            try { await session.browser?.close(); } catch (_) {}
            this._sessions.delete(sessionId);

            throw err;
        }
    }

    /**
     * Release a session — close browser.
     * @param {string} sessionId
     */
    async release(sessionId) {
        const session = this._sessions.get(sessionId);
        if (!session) return;

        this.log('info', `[session] Releasing session: ${sessionId}`);
        try {
            await session.browser?.close();
        } catch (_) {}

        session.state = SESSION_STATES.DISCONNECTED;
        this._sessions.delete(sessionId);
    }

    /**
     * Release all sessions.
     */
    async releaseAll() {
        const ids = Array.from(this._sessions.keys());
        for (const id of ids) {
            await this.release(id);
        }
    }

    /**
     * Check if a session is alive and WhatsApp is connected.
     * @param {string} sessionId
     * @returns {{ alive: boolean, state: string, health: object }}
     */
    async checkHealth(sessionId) {
        const session = this._sessions.get(sessionId);
        if (!session || !session.page) {
            return { alive: false, state: SESSION_STATES.DISCONNECTED, health: this._loadHealth(sessionId) };
        }

        try {
            // Check if browser is still running
            if (!session.browser.isConnected()) {
                session.state = SESSION_STATES.DISCONNECTED;
                return { alive: false, state: session.state, health: session.health };
            }

            // Check for ban notice
            const banned = await session.page.$(SELECTORS.banNotice);
            if (banned) {
                const text = await session.page.evaluate(el => el.textContent, banned);
                if (text && (text.includes('banned') || text.includes('suspended') || text.includes('محظور'))) {
                    session.state = SESSION_STATES.BANNED;
                    session.health.banned = true;
                    session.health.banDate = Date.now();
                    this._saveHealth(sessionId, session.health);
                    return { alive: false, state: session.state, health: session.health };
                }
            }

            // Check if chat list is still visible
            const chatList = await session.page.$(SELECTORS.chatList);
            const alive = !!chatList;

            if (!alive) {
                session.state = SESSION_STATES.DISCONNECTED;
            }

            return { alive, state: session.state, health: session.health };

        } catch (err) {
            this.log('warn', `[session] Health check error for ${sessionId}: ${err.message}`);
            return { alive: false, state: SESSION_STATES.ERROR, health: session.health };
        }
    }

    /**
     * Get the best available session for sending.
     * Picks the healthiest, least-recently-used session.
     * @returns {{ sessionId: string, session: object }|null}
     */
    getBestSession() {
        let best = null;
        let bestScore = -1;

        for (const [id, session] of this._sessions) {
            if (session.state !== SESSION_STATES.CONNECTED) continue;

            const health = session.health;
            const score = (health.trustScore || 50) -
                          (health.banned ? 1000 : 0) -
                          (health.warningCount || 0) * 10 +
                          (Date.now() - session.lastActivity) / 60000; // favor idle sessions

            if (score > bestScore) {
                bestScore = score;
                best = { sessionId: id, session };
            }
        }

        return best;
    }

    /**
     * List all known sessions (from disk).
     * @returns {object[]} [{ sessionId, health, hasData }]
     */
    listSessions() {
        const result = [];
        try {
            const dirs = fs.readdirSync(this.sessionsDir);
            for (const dir of dirs) {
                if (dir.startsWith('_') || dir.startsWith('.')) continue;
                const fullPath = path.join(this.sessionsDir, dir);
                if (!fs.statSync(fullPath).isDirectory()) continue;

                const health = this._loadHealth(dir);
                const active = this._sessions.get(dir);

                result.push({
                    sessionId: dir,
                    state: active?.state || SESSION_STATES.IDLE,
                    health,
                    hasData: fs.existsSync(path.join(fullPath, 'Default')), // Chrome profile exists
                });
            }
        } catch (_) {}
        return result;
    }

    /**
     * Backup a session's browser data (for migration/recovery).
     * @param {string} sessionId
     * @param {string} backupDir
     */
    async backupSession(sessionId, backupDir) {
        const srcDir = path.join(this.sessionsDir, sessionId);
        if (!fs.existsSync(srcDir)) {
            throw new Error(`Session ${sessionId} not found`);
        }

        // Must release the session first to avoid file locks
        await this.release(sessionId);

        this.log('info', `[session] Backing up ${sessionId} → ${backupDir}`);
        this._copyDirSync(srcDir, backupDir);
    }

    /**
     * Restore a session from backup.
     * @param {string} sessionId
     * @param {string} backupDir
     */
    restoreSession(sessionId, backupDir) {
        const destDir = path.join(this.sessionsDir, sessionId);
        this.log('info', `[session] Restoring ${sessionId} from ${backupDir}`);
        this._copyDirSync(backupDir, destDir);
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    async _launchBrowser(sessionId, opts = {}) {
        let puppeteer;
        try {
            puppeteer = require('puppeteer');
        } catch {
            try {
                puppeteer = require('puppeteer-core');
            } catch {
                throw new Error('puppeteer or puppeteer-core not installed. Run: npm install puppeteer');
            }
        }

        const userDataDir = path.join(this.sessionsDir, sessionId);
        if (!fs.existsSync(userDataDir)) {
            fs.mkdirSync(userDataDir, { recursive: true });
        }

        const args = [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-gpu',
            '--window-size=1280,800',
            '--disable-dev-shm-usage',
        ];

        // Proxy support
        const proxy = opts.proxy || this.proxyMgr?.getProxy(sessionId);
        if (proxy) {
            args.push(`--proxy-server=${proxy}`);
            this.log('info', `[session] Using proxy: ${proxy}`);
        }

        const browser = await puppeteer.launch({
            headless: opts.headless ?? false,
            userDataDir,
            args,
        });

        const page = (await browser.pages())[0] || await browser.newPage();
        await page.setViewport({ width: 1280, height: 800 });

        // Set realistic user-agent
        await page.setUserAgent(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 ' +
            '(KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'
        );

        return { browser, page };
    }

    async _openWhatsApp(session, sessionId) {
        const { page } = session;

        this.log('info', `[session] Opening WhatsApp Web for: ${sessionId}`);
        await page.goto(WA_URL, { waitUntil: 'domcontentloaded', timeout: LOAD_TIMEOUT });

        // Wait for either QR or chat list
        this.log('info', `[session] Waiting for WhatsApp to load...`);
        await page.waitForSelector(
            `${SELECTORS.chatList}, ${SELECTORS.qrCanvas}`,
            { timeout: SESSION_TIMEOUT }
        );

        // Check if QR scan needed
        const needsQR = await page.$(SELECTORS.qrCanvas);
        if (needsQR) {
            session.state = SESSION_STATES.QR_PENDING;
            this.log('info', `[session] 📱 QR Code displayed — scan with phone (${sessionId})`);

            // Wait for QR scan
            await page.waitForSelector(SELECTORS.chatList, { timeout: QR_TIMEOUT });
            this.log('info', `[session] ✓ QR scanned — connected (${sessionId})`);
        } else {
            this.log('info', `[session] ✓ Session restored — already logged in (${sessionId})`);
        }
    }

    _loadHealth(sessionId) {
        const healthPath = path.join(this.sessionsDir, sessionId, HEALTH_FILE);
        try {
            if (fs.existsSync(healthPath)) {
                return JSON.parse(fs.readFileSync(healthPath, 'utf-8'));
            }
        } catch (_) {}

        return {
            trustScore:    50,
            connectCount:  0,
            errorCount:    0,
            warningCount:  0,
            messagesSent:  0,
            messagesRecv:  0,
            replyRatio:    0,
            banned:        false,
            banDate:       null,
            lastConnected: null,
            lastError:     null,
            created:       Date.now(),
        };
    }

    _saveHealth(sessionId, health) {
        const dir = path.join(this.sessionsDir, sessionId);
        if (!fs.existsSync(dir)) fs.mkdirSync(dir, { recursive: true });

        const healthPath = path.join(dir, HEALTH_FILE);
        fs.writeFileSync(healthPath, JSON.stringify(health, null, 2));
    }

    _copyDirSync(src, dest) {
        if (!fs.existsSync(dest)) fs.mkdirSync(dest, { recursive: true });

        const entries = fs.readdirSync(src, { withFileTypes: true });
        for (const entry of entries) {
            const srcPath  = path.join(src, entry.name);
            const destPath = path.join(dest, entry.name);

            if (entry.isDirectory()) {
                this._copyDirSync(srcPath, destPath);
            } else {
                fs.copyFileSync(srcPath, destPath);
            }
        }
    }
}

// ── Export ────────────────────────────────────────────────────────────────────

module.exports = SessionManager;
module.exports.SESSION_STATES = SESSION_STATES;
module.exports.SELECTORS      = SELECTORS;
