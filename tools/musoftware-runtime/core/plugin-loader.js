'use strict';

/**
 * Plugin Loader — Unified Runtime
 *
 * Scans plugins/ directory, reads manifest.json from each sub-folder.
 * Supports both nodejs and python plugins.
 *
 * Manifest schema:
 * {
 *   "id":          "whatsapp-sender",
 *   "name":        "WhatsApp Sender",
 *   "version":     "1.0.0",
 *   "runtime":     "nodejs",          ← "nodejs" | "python"
 *   "entry":       "worker.js",
 *   "tool_slug":   "whatsapp-sender", ← matches platform tool slug
 *   "permissions": ["browser", "network"],
 *   "autoStart":   false,
 *   "requires_runtime": ">=1.0.0"
 * }
 */

'use strict';

const fs    = require('fs');
const path  = require('path');
const https = require('https');
const http  = require('http');
const { execSync } = require('child_process');
const AdmZip = require('adm-zip');

class PluginLoader {
    constructor(config, logger) {
        this.config     = config;
        this.logger     = logger;
        this.pluginsDir = config.pluginsDir;
        this._plugins   = new Map();
    }

    // ── Load all installed plugins from disk ──────────────────────────────────
    async loadAll() {
        this._plugins.clear();
        fs.mkdirSync(this.pluginsDir, { recursive: true });

        const dirs = fs.readdirSync(this.pluginsDir, { withFileTypes: true })
            .filter(d => d.isDirectory());

        for (const dir of dirs) {
            const pluginDir = path.join(this.pluginsDir, dir.name);
            const manifest  = this._readManifest(pluginDir);
            if (manifest) {
                this._plugins.set(manifest.id, { ...manifest, dir: pluginDir });
                this.logger.info(`[loader] Plugin: [${manifest.id}] v${manifest.version} (${manifest.runtime})`);
            }
        }

        return this.getAll();
    }

    _readManifest(dir) {
        const mPath = path.join(dir, 'manifest.json');
        if (!fs.existsSync(mPath)) return null;
        try {
            const m = JSON.parse(fs.readFileSync(mPath, 'utf8'));
            if (!m.id || !m.entry) return null;

            const entryPath = path.join(dir, m.entry);
            if (!fs.existsSync(entryPath)) {
                this.logger.warn(`[loader] Plugin '${m.id}' entry missing: ${entryPath}`);
                return null;
            }

            return {
                ...m,
                runtime:   m.runtime || 'nodejs',
                entryPath,
            };
        } catch (e) {
            this.logger.warn(`[loader] Bad manifest in ${dir}: ${e.message}`);
            return null;
        }
    }

    // ── Queries ───────────────────────────────────────────────────────────────
    getAll()        { return Array.from(this._plugins.values()); }
    getById(id)     { return this._plugins.get(id) ?? null; }
    getBySlug(slug) { return this.getAll().find(p => p.tool_slug === slug) ?? null; }

    getByRuntime(runtime) {
        return this.getAll().filter(p => p.runtime === runtime);
    }

    // ── Auto-download a plugin ────────────────────────────────────────────────
    async ensurePlugin(toolSlug, downloadUrl, broadcast) {
        const existing = this.getBySlug(toolSlug);
        if (existing) {
            this.logger.debug(`[loader] Already installed: ${toolSlug}`);
            return existing;
        }

        this.logger.info(`[loader] Auto-downloading plugin: ${toolSlug}`);
        broadcast?.('plugin.installing', { toolSlug });

        const pluginDir = path.join(this.pluginsDir, toolSlug);
        const zipPath   = path.join(this.pluginsDir, `${toolSlug}.zip`);

        try {
            await this._downloadFile(downloadUrl, zipPath);
            fs.mkdirSync(pluginDir, { recursive: true });

            // Extract with adm-zip (no system dependency)
            const zip = new AdmZip(zipPath);
            zip.extractAllTo(pluginDir, true);
            fs.unlinkSync(zipPath);

            // Install runtime deps
            const manifest = this._readManifest(pluginDir);
            if (!manifest) throw new Error('manifest.json missing after install');

            const runtime = manifest.runtime || 'nodejs';

            if (runtime === 'nodejs') {
                const pkgJson = path.join(pluginDir, 'package.json');
                if (fs.existsSync(pkgJson)) {
                    this.logger.info(`[loader] npm install for ${toolSlug}...`);
                    execSync('npm install --production --silent', { cwd: pluginDir, timeout: 120_000 });
                }
            } else if (runtime === 'python') {
                const reqTxt = path.join(pluginDir, 'requirements.txt');
                if (fs.existsSync(reqTxt)) {
                    const pythonBin = this.config.pythonBin || 'python';
                    this.logger.info(`[loader] pip install for ${toolSlug}...`);
                    execSync(
                        `${pythonBin} -m pip install -r requirements.txt --quiet`,
                        { cwd: pluginDir, timeout: 180_000 }
                    );
                }
            }

            this._plugins.set(manifest.id, { ...manifest, dir: pluginDir });
            this.logger.info(`[loader] Plugin installed: ${toolSlug} v${manifest.version}`);
            broadcast?.('plugin.installed', { toolSlug, version: manifest.version });
            return this._plugins.get(manifest.id);

        } catch (err) {
            this.logger.error(`[loader] Install failed for ${toolSlug}: ${err.message}`);
            if (fs.existsSync(zipPath))   fs.unlinkSync(zipPath);
            if (fs.existsSync(pluginDir)) fs.rmSync(pluginDir, { recursive: true, force: true });
            throw err;
        }
    }

    _downloadFile(url, dest) {
        return new Promise((resolve, reject) => {
            const proto = url.startsWith('https') ? https : http;
            const file  = fs.createWriteStream(dest);
            proto.get(url, { timeout: 30_000 }, res => {
                if ([301, 302, 307, 308].includes(res.statusCode)) {
                    file.close();
                    return this._downloadFile(res.headers.location, dest).then(resolve).catch(reject);
                }
                if (res.statusCode !== 200) {
                    return reject(new Error(`HTTP ${res.statusCode} downloading plugin`));
                }
                res.pipe(file);
                file.on('finish', () => file.close(resolve));
            }).on('error', err => { fs.unlink(dest, () => {}); reject(err); });
        });
    }
}

module.exports = PluginLoader;
