'use strict';

/**
 * Plugin Syncer — polls platform API for subscribed plugins and auto-installs them.
 *
 * Security model:
 *   - Only downloads plugins for which the user has an ACTIVE subscription.
 *   - Writes license records to local SQLite after every successful sync.
 *   - Revokes license cache for plugins that were removed from the subscription.
 *   - The run gate in index.js checks these cached licenses before spawning workers.
 *
 * Polled endpoint: GET /api/tools/agent/plugins  (auth: Bearer token)
 * Runs every SYNC_INTERVAL_MS in the background.
 */

const axios = require('axios');

const SYNC_INTERVAL_MS = 2 * 60 * 1000; // 2 minutes

class PluginSyncer {
    constructor(config, logger, pluginLoader, storage, broadcast) {
        this.config       = config;
        this.logger       = logger;
        this.pluginLoader = pluginLoader;
        this.storage      = storage;       // ← added for license cache writes
        this.broadcast    = broadcast;
        this._timer       = null;
        this._running     = false;
    }

    start() {
        this.logger.info('[syncer] Plugin syncer started (license-gated)');
        this._sync(); // immediate first sync
        this._timer = setInterval(() => this._sync(), SYNC_INTERVAL_MS);
    }

    stop() {
        if (this._timer) {
            clearInterval(this._timer);
            this._timer = null;
        }
    }

    async _sync() {
        if (!this.config.token) return;
        if (this._running)      return;
        this._running = true;

        try {
            const res = await axios.get(`${this.config.platformUrl}/api/tools/agent/plugins`, {
                params:  { agent: 'nodejs' },
                headers: { Authorization: `Bearer ${this.config.token}` },
                timeout: 10_000,
            });

            const plugins        = res.data?.plugins ?? [];
            const subscribedSlugs = new Set(plugins.map(p => p.tool_slug).filter(Boolean));

            this.logger.debug(`[syncer] Platform returned ${plugins.length} subscribed plugins`);

            // ── 1. Write / refresh license cache for all subscribed plugins ──────
            for (const plugin of plugins) {
                if (!plugin.tool_slug) continue;

                this.storage?.upsertLicense(plugin.tool_slug, {
                    status:    plugin.license_status ?? 'active',
                    expiresAt: plugin.expires_at ?? null,
                });

                if (!plugin.download_url) continue;

                const existing = this.pluginLoader.getBySlug(plugin.tool_slug);

                if (!existing) {
                    // New subscription — download plugin
                    this.logger.info(`[syncer] New subscription: ${plugin.tool_slug} — downloading`);
                    try {
                        await this.pluginLoader.ensurePlugin(
                            plugin.tool_slug,
                            plugin.download_url,
                            this.broadcast
                        );
                    } catch (err) {
                        this.logger.error(`[syncer] Install failed for ${plugin.tool_slug}: ${err.message}`);
                    }
                } else if (existing.version !== plugin.version) {
                    // Plugin update available — notify but don't auto-update yet
                    this.logger.info(`[syncer] Update: ${plugin.tool_slug} ${existing.version} → ${plugin.version}`);
                    this.broadcast('plugin.update_available', {
                        slug:      plugin.tool_slug,
                        current:   existing.version,
                        available: plugin.version,
                        downloadUrl: plugin.download_url,
                    });
                }
            }

            // ── 2. Revoke licenses for plugins no longer in subscription ─────────
            const installedPlugins = this.pluginLoader.getAll();
            for (const installed of installedPlugins) {
                const slug = installed.tool_slug;
                if (!slug) continue;
                if (!subscribedSlugs.has(slug)) {
                    const prevStatus = this.storage?.checkLicense(slug, Infinity);
                    if (prevStatus === 'active') {
                        this.logger.warn(`[syncer] License revoked for: ${slug}`);
                        this.storage?.revokeLicense(slug);
                        this.broadcast('plugin.license_revoked', { slug });
                    }
                }
            }

            this.broadcast('sync.complete', {
                subscribed: plugins.length,
                ts: Date.now(),
            });

        } catch (err) {
            this.logger.debug(`[syncer] Sync error: ${err.message}`);
        } finally {
            this._running = false;
        }
    }

    forcSync() {
        this._sync();
    }
}

module.exports = PluginSyncer;
