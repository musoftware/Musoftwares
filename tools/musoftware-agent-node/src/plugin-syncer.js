/**
 * Plugin Syncer — Auto-downloads plugins after user subscribes
 *
 * Polls platform API every 2 minutes:
 *   GET {platformUrl}/api/tools/agent/plugins?agent=nodejs
 *   Returns: [{ tool_slug, version, download_url, is_subscribed }]
 *
 * If a subscribed tool plugin is not installed → auto-downloads it.
 * Broadcasts 'plugin.installed' event to connected browser clients.
 */

'use strict';

const axios = require('axios');

const SYNC_INTERVAL_MS = 2 * 60 * 1000; // every 2 minutes

class PluginSyncer {
    constructor(config, logger, pluginLoader, broadcast) {
        this.config       = config;
        this.logger       = logger;
        this.loader       = pluginLoader;
        this.broadcast    = broadcast; // fn(event, data) → sends to WS clients
        this._timer       = null;
        this._syncing     = false;
    }

    start() {
        this._sync(); // immediate first sync
        this._timer = setInterval(() => this._sync(), SYNC_INTERVAL_MS);
        this.logger.info('Plugin syncer started');
    }

    stop() {
        if (this._timer) { clearInterval(this._timer); this._timer = null; }
    }

    async _sync() {
        if (!this.config.token || this._syncing) return;
        this._syncing = true;

        try {
            const res = await axios.get(
                `${this.config.platformUrl}/api/tools/agent/plugins`,
                {
                    params:  { agent: 'nodejs' },
                    headers: {
                        Authorization: `Bearer ${this.config.token}`,
                        'X-Agent-Version': '1.0.0',
                    },
                    timeout: 10000,
                }
            );

            const plugins = res.data?.plugins ?? [];

            for (const item of plugins) {
                if (!item.is_subscribed || !item.download_url) continue;

                const installed = this.loader.getBySlug(item.tool_slug);

                // Skip if already on latest version
                if (installed && installed.version === item.version) continue;

                this.logger.info(`Syncing plugin: ${item.tool_slug} v${item.version}`);
                this.broadcast('plugin.installing', { slug: item.tool_slug, version: item.version });

                try {
                    const plugin = await this.loader.ensurePlugin(item.tool_slug, item.download_url);
                    this.broadcast('plugin.installed', {
                        slug:    item.tool_slug,
                        name:    plugin.name,
                        version: plugin.version,
                    });
                    this.logger.info(`Plugin ready: ${item.tool_slug}`);
                } catch (err) {
                    this.broadcast('plugin.install_failed', { slug: item.tool_slug, error: err.message });
                    this.logger.error(`Plugin sync failed: ${item.tool_slug} — ${err.message}`);
                }
            }
        } catch (err) {
            this.logger.debug(`Plugin sync error: ${err.message}`);
        } finally {
            this._syncing = false;
        }
    }
}

module.exports = PluginSyncer;
