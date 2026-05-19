'use strict';

/**
 * Update Checker — Runtime Auto-Updater
 *
 * Polls GET /api/runtime/version every hour.
 * Broadcasts runtime.update_available when a newer version exists.
 * Future: downloads update ZIP and restarts.
 */

const axios  = require('axios');
const semver = require('semver');
const fs     = require('fs');
const path   = require('path');

const CHECK_INTERVAL_MS = 60 * 60 * 1000; // 1 hour
const RUNTIME_VERSION   = require('../package.json').version;

class UpdateChecker {
    constructor(config, logger, broadcast) {
        this.config    = config;
        this.logger    = logger;
        this.broadcast = broadcast;
        this._timer    = null;
    }

    start() {
        if (!this.config.autoUpdate) return;
        this.logger.info(`[updater] Runtime v${RUNTIME_VERSION} — checking for updates every hour`);
        setTimeout(() => this._check(), 10_000); // first check after 10s
        this._timer = setInterval(() => this._check(), CHECK_INTERVAL_MS);
    }

    stop() {
        if (this._timer) { clearInterval(this._timer); this._timer = null; }
    }

    async checkNow() {
        try {
            const res = await axios.get(`${this.config.platformUrl}/api/runtime/version`, {
                timeout: 8_000,
                headers: this.config.token
                    ? { Authorization: `Bearer ${this.config.token}` }
                    : {},
            });

            const { latest, minimum_supported, download_url, changelog } = res.data ?? {};
            if (!latest) return { checked: true, error: 'No version in response' };

            const result = {
                checked:           true,
                current:           RUNTIME_VERSION,
                latest,
                minimum_supported: minimum_supported || '0.0.0',
                download_url,
                changelog:         changelog?.slice(0, 3) ?? [],
                upToDate:          false,
                updateRequired:    false,
                updateAvailable:   false,
            };

            if (semver.lt(RUNTIME_VERSION, minimum_supported || '0.0.0')) {
                result.updateRequired  = true;
                this.logger.warn(`[updater] REQUIRED update: ${RUNTIME_VERSION} → ${latest}`);
                this.broadcast('runtime.update_required', { current: RUNTIME_VERSION, required: minimum_supported, latest, download_url });
            } else if (semver.lt(RUNTIME_VERSION, latest)) {
                result.updateAvailable = true;
                this.logger.info(`[updater] Update available: ${RUNTIME_VERSION} → ${latest}`);
                this.broadcast('runtime.update_available', { current: RUNTIME_VERSION, latest, download_url, changelog: changelog?.slice(0, 3) ?? [] });
            } else {
                result.upToDate = true;
                this.logger.debug(`[updater] Up to date (${RUNTIME_VERSION})`);
            }

            return result;
        } catch (err) {
            this.logger.debug(`[updater] Check failed: ${err.message}`);
            return { checked: false, error: err.message, current: RUNTIME_VERSION };
        }
    }

    async _check() {
        await this.checkNow();
    }
}

module.exports = UpdateChecker;
