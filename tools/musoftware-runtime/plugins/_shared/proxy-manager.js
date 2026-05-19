/**
 * WhatsApp Proxy Manager — Shared Module
 * ========================================
 * Manages proxy rotation for multi-number WhatsApp sessions.
 * Ensures each number uses a consistent proxy to avoid
 * fingerprinting/detection by WhatsApp.
 *
 * Features:
 *   - Proxy pool management (residential, datacenter)
 *   - Sticky sessions (same number → same proxy)
 *   - Health checking (latency, success rate)
 *   - Auto-rotation on failure
 *   - Proxy-number affinity tracking
 *
 * Usage:
 *   const ProxyManager = require('../_shared/proxy-manager');
 *   const pm = new ProxyManager({
 *       proxies: [
 *           { url: 'http://user:pass@proxy1:8080', type: 'residential' },
 *           { url: 'socks5://proxy2:1080', type: 'datacenter' },
 *       ]
 *   });
 *
 *   const proxy = pm.getProxy('session-123');
 */

'use strict';

const fs   = require('fs');
const path = require('path');

// ── Constants ────────────────────────────────────────────────────────────────

const AFFINITY_FILE = 'proxy-affinity.json';

// ── Class ────────────────────────────────────────────────────────────────────

class ProxyManager {

    /**
     * @param {object} opts
     * @param {object[]} opts.proxies — [{ url, type, region, maxSessions }]
     * @param {string}   opts.dataDir — directory for persisting affinity data
     * @param {function} opts.log — logger function
     */
    constructor(opts = {}) {
        this.log      = opts.log || (() => {});
        this.dataDir  = opts.dataDir || path.join(__dirname, '..', '_sessions');
        this._proxies = (opts.proxies || []).map((p, i) => ({
            id:          `proxy-${i}`,
            url:         p.url,
            type:        p.type || 'datacenter',       // 'residential' | 'datacenter'
            region:      p.region || 'unknown',
            maxSessions: p.maxSessions || 5,
            activeSessions: 0,
            successCount: 0,
            failCount:    0,
            totalLatency: 0,
            lastUsed:     0,
            alive:        true,
        }));

        // sessionId → proxyId (sticky mapping)
        this._affinity = this._loadAffinity();
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Get the assigned proxy for a session.
     * Creates a sticky assignment if none exists.
     * @param {string} sessionId
     * @returns {string|null} proxy URL or null if no proxies
     */
    getProxy(sessionId) {
        if (!this._proxies.length) return null;

        // Check existing affinity
        const affinityId = this._affinity[sessionId];
        if (affinityId) {
            const proxy = this._proxies.find(p => p.id === affinityId && p.alive);
            if (proxy) {
                proxy.lastUsed = Date.now();
                return proxy.url;
            }
            // Affinity proxy is dead — reassign
            delete this._affinity[sessionId];
        }

        // Assign best available proxy
        const proxy = this._selectBestProxy();
        if (!proxy) return null;

        this._affinity[sessionId] = proxy.id;
        proxy.activeSessions++;
        proxy.lastUsed = Date.now();
        this._saveAffinity();

        this.log('info', `[proxy] Assigned ${proxy.id} (${proxy.type}) → ${sessionId}`);
        return proxy.url;
    }

    /**
     * Release a proxy assignment.
     * @param {string} sessionId
     */
    releaseProxy(sessionId) {
        const affinityId = this._affinity[sessionId];
        if (affinityId) {
            const proxy = this._proxies.find(p => p.id === affinityId);
            if (proxy) proxy.activeSessions = Math.max(0, proxy.activeSessions - 1);
            delete this._affinity[sessionId];
            this._saveAffinity();
        }
    }

    /**
     * Report proxy result (success/failure).
     * @param {string} sessionId
     * @param {boolean} success
     * @param {number} latencyMs
     */
    reportResult(sessionId, success, latencyMs = 0) {
        const affinityId = this._affinity[sessionId];
        if (!affinityId) return;

        const proxy = this._proxies.find(p => p.id === affinityId);
        if (!proxy) return;

        if (success) {
            proxy.successCount++;
            proxy.totalLatency += latencyMs;
        } else {
            proxy.failCount++;
            // Auto-disable after 5 consecutive failures
            if (proxy.failCount > 5 && proxy.successCount === 0) {
                proxy.alive = false;
                this.log('warn', `[proxy] Disabled ${proxy.id} — too many failures`);
            }
        }
    }

    /**
     * Force-rotate a session to a new proxy.
     * @param {string} sessionId
     * @returns {string|null} new proxy URL
     */
    rotateProxy(sessionId) {
        const oldId = this._affinity[sessionId];
        this.releaseProxy(sessionId);

        // Exclude old proxy from selection
        const proxy = this._selectBestProxy(oldId);
        if (!proxy) return null;

        this._affinity[sessionId] = proxy.id;
        proxy.activeSessions++;
        proxy.lastUsed = Date.now();
        this._saveAffinity();

        this.log('info', `[proxy] Rotated ${sessionId}: ${oldId} → ${proxy.id}`);
        return proxy.url;
    }

    /**
     * Get proxy pool status.
     * @returns {object[]}
     */
    getStatus() {
        return this._proxies.map(p => ({
            id:             p.id,
            type:           p.type,
            region:         p.region,
            alive:          p.alive,
            activeSessions: p.activeSessions,
            maxSessions:    p.maxSessions,
            successRate:    p.successCount + p.failCount > 0
                ? Math.round((p.successCount / (p.successCount + p.failCount)) * 100)
                : 100,
            avgLatency:     p.successCount > 0
                ? Math.round(p.totalLatency / p.successCount)
                : 0,
        }));
    }

    /**
     * Add a proxy to the pool at runtime.
     * @param {object} proxy — { url, type, region, maxSessions }
     */
    addProxy(proxy) {
        this._proxies.push({
            id:             `proxy-${this._proxies.length}`,
            url:            proxy.url,
            type:           proxy.type || 'datacenter',
            region:         proxy.region || 'unknown',
            maxSessions:    proxy.maxSessions || 5,
            activeSessions: 0,
            successCount:   0,
            failCount:      0,
            totalLatency:   0,
            lastUsed:       0,
            alive:          true,
        });
        this.log('info', `[proxy] Added proxy: ${proxy.url}`);
    }

    /**
     * Remove a proxy from the pool.
     * @param {string} proxyId
     */
    removeProxy(proxyId) {
        this._proxies = this._proxies.filter(p => p.id !== proxyId);
        // Clean up affinities pointing to removed proxy
        for (const [sid, pid] of Object.entries(this._affinity)) {
            if (pid === proxyId) delete this._affinity[sid];
        }
        this._saveAffinity();
    }

    // ── Private Helpers ──────────────────────────────────────────────────────

    _selectBestProxy(excludeId = null) {
        const available = this._proxies.filter(p =>
            p.alive &&
            p.id !== excludeId &&
            p.activeSessions < p.maxSessions
        );

        if (!available.length) {
            this.log('warn', '[proxy] No available proxies!');
            return null;
        }

        // Score: prefer residential, low usage, high success rate
        available.sort((a, b) => {
            const scoreA = this._proxyScore(a);
            const scoreB = this._proxyScore(b);
            return scoreB - scoreA; // higher is better
        });

        return available[0];
    }

    _proxyScore(p) {
        let score = 0;

        // Prefer residential
        if (p.type === 'residential') score += 50;

        // Prefer less-loaded proxies
        score += (p.maxSessions - p.activeSessions) * 10;

        // Prefer high success rate
        const total = p.successCount + p.failCount;
        if (total > 0) {
            score += (p.successCount / total) * 30;
        } else {
            score += 15; // unknown = neutral
        }

        // Penalize high latency
        if (p.successCount > 0) {
            const avgLatency = p.totalLatency / p.successCount;
            score -= avgLatency / 100;
        }

        return score;
    }

    _loadAffinity() {
        const affinityPath = path.join(this.dataDir, AFFINITY_FILE);
        try {
            if (fs.existsSync(affinityPath)) {
                return JSON.parse(fs.readFileSync(affinityPath, 'utf-8'));
            }
        } catch (_) {}
        return {};
    }

    _saveAffinity() {
        try {
            if (!fs.existsSync(this.dataDir)) {
                fs.mkdirSync(this.dataDir, { recursive: true });
            }
            const affinityPath = path.join(this.dataDir, AFFINITY_FILE);
            fs.writeFileSync(affinityPath, JSON.stringify(this._affinity, null, 2));
        } catch (err) {
            this.log('warn', `[proxy] Failed to save affinity: ${err.message}`);
        }
    }
}

// ── Export ────────────────────────────────────────────────────────────────────

module.exports = ProxyManager;
