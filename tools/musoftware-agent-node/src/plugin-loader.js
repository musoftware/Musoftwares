/**
 * Plugin Loader — Node.js Agent
 *
 * Scans plugins/ directory, reads manifest.json from each sub-folder.
 * Auto-downloads new plugins when user subscribes (via platform API).
 *
 * Manifest format:
 * {
 *   "id":       "whatsapp-sender",
 *   "name":     "WhatsApp Sender",
 *   "version":  "1.0.0",
 *   "entry":    "worker.js",
 *   "tool_slug":"whatsapp-sender"   ← matches platform tool slug
 * }
 */

'use strict';

const fs    = require('fs');
const path  = require('path');
const https = require('https');
const http  = require('http');
const { execSync } = require('child_process');

class PluginLoader {
    constructor(config, logger) {
        this.config     = config;
        this.logger     = logger;
        this.pluginsDir = config.pluginsDir;
        this._plugins   = new Map(); // id → plugin
    }

    // ── Load all installed plugins from disk ─────────────────────────────────
    async loadAll() {
        this._plugins.clear();

        if (!fs.existsSync(this.pluginsDir)) {
            fs.mkdirSync(this.pluginsDir, { recursive: true });
            return [];
        }

        const dirs = fs.readdirSync(this.pluginsDir, { withFileTypes: true })
            .filter(d => d.isDirectory());

        for (const dir of dirs) {
            const pluginDir  = path.join(this.pluginsDir, dir.name);
            const manifest   = this._readManifest(pluginDir);
            if (!manifest) continue;

            this._plugins.set(manifest.id, { ...manifest, dir: pluginDir });
            this.logger.info(`Plugin loaded: [${manifest.id}] v${manifest.version}`);
        }

        return this.getAll();
    }

    _readManifest(dir) {
        const manifestPath = path.join(dir, 'manifest.json');
        if (!fs.existsSync(manifestPath)) return null;
        try {
            const m = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
            if (!m.id || !m.entry) return null;
            const entryPath = path.join(dir, m.entry);
            if (!fs.existsSync(entryPath)) {
                this.logger.warn(`Plugin '${m.id}' entry missing: ${entryPath}`);
                return null;
            }
            return { ...m, entryPath };
        } catch (e) {
            this.logger.warn(`Bad manifest in ${dir}: ${e.message}`);
            return null;
        }
    }

    getAll()        { return Array.from(this._plugins.values()); }
    getById(id)     { return this._plugins.get(id) ?? null; }
    getBySlug(slug) { return this.getAll().find(p => p.tool_slug === slug) ?? null; }

    // ── Auto-download a plugin after user subscribes ──────────────────────────
    // Called when platform heartbeat says: "you have subscribed tools"
    async ensurePlugin(toolSlug, downloadUrl) {
        const existing = this.getBySlug(toolSlug);
        if (existing) {
            this.logger.info(`Plugin already installed: ${toolSlug}`);
            return existing;
        }

        this.logger.info(`Auto-downloading plugin: ${toolSlug}`);
        const pluginDir = path.join(this.pluginsDir, toolSlug);

        try {
            // Download ZIP from platform (signed URL from Laravel storage)
            const zipPath = path.join(this.pluginsDir, `${toolSlug}.zip`);
            await this._downloadFile(downloadUrl, zipPath);

            // Extract ZIP
            fs.mkdirSync(pluginDir, { recursive: true });
            this._extractZip(zipPath, pluginDir);
            fs.unlinkSync(zipPath);

            // Install npm deps if package.json exists
            const pkgJson = path.join(pluginDir, 'package.json');
            if (fs.existsSync(pkgJson)) {
                this.logger.info(`Installing npm deps for ${toolSlug}...`);
                execSync('npm install --production --silent', { cwd: pluginDir, timeout: 120000 });
            }

            // Reload manifest
            const manifest = this._readManifest(pluginDir);
            if (!manifest) throw new Error('Manifest missing after install');

            this._plugins.set(manifest.id, { ...manifest, dir: pluginDir });
            this.logger.info(`Plugin installed: ${toolSlug} v${manifest.version}`);
            return this._plugins.get(manifest.id);

        } catch (err) {
            this.logger.error(`Plugin install failed for ${toolSlug}: ${err.message}`);
            // Cleanup failed install
            if (fs.existsSync(pluginDir)) fs.rmSync(pluginDir, { recursive: true, force: true });
            throw err;
        }
    }

    _downloadFile(url, dest) {
        return new Promise((resolve, reject) => {
            const proto = url.startsWith('https') ? https : http;
            const file  = fs.createWriteStream(dest);
            proto.get(url, res => {
                if (res.statusCode === 302 || res.statusCode === 301) {
                    file.close();
                    return this._downloadFile(res.headers.location, dest).then(resolve).catch(reject);
                }
                if (res.statusCode !== 200) {
                    return reject(new Error(`HTTP ${res.statusCode}`));
                }
                res.pipe(file);
                file.on('finish', () => file.close(resolve));
            }).on('error', err => { fs.unlink(dest, () => {}); reject(err); });
        });
    }

    _extractZip(zipPath, destDir) {
        // Use system unzip or PowerShell on Windows
        if (process.platform === 'win32') {
            execSync(`powershell -Command "Expand-Archive -Path '${zipPath}' -DestinationPath '${destDir}' -Force"`,
                { timeout: 60000 });
        } else {
            execSync(`unzip -o "${zipPath}" -d "${destDir}"`, { timeout: 60000 });
        }
    }
}

module.exports = PluginLoader;
