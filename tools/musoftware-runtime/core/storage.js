'use strict';

/**
 * Storage — SQLite local database
 *
 * Tables:
 *   tasks   — task history (id, plugin_id, status, params, result, created_at)
 *   plugins — installed plugin registry (id, slug, version, runtime, dir)
 *   logs    — per-task log buffer (task_id, level, message, ts)
 */

const fs   = require('fs');
const path = require('path');

class Storage {
    constructor(config, logger) {
        this.logger     = logger;
        this.storageDir = config.storageDir;
        this.db         = null;
    }

    init() {
        fs.mkdirSync(this.storageDir, { recursive: true });
        const dbPath = path.join(this.storageDir, 'runtime.db');

        let Database;
        try {
            Database = require('better-sqlite3');
        } catch {
            this.logger.warn('better-sqlite3 not available — running without persistence');
            this._noop = true;
            return;
        }

        this.db = new Database(dbPath);
        this.db.pragma('journal_mode = WAL');

        this.db.exec(`
            CREATE TABLE IF NOT EXISTS tasks (
                id          TEXT PRIMARY KEY,
                plugin_id   TEXT NOT NULL,
                runtime     TEXT NOT NULL DEFAULT 'nodejs',
                status      TEXT NOT NULL DEFAULT 'running',
                params      TEXT,
                result      TEXT,
                error       TEXT,
                created_at  INTEGER DEFAULT (strftime('%s','now')),
                updated_at  INTEGER DEFAULT (strftime('%s','now'))
            );

            CREATE TABLE IF NOT EXISTS plugins (
                id          TEXT PRIMARY KEY,
                slug        TEXT UNIQUE NOT NULL,
                name        TEXT,
                version     TEXT,
                runtime     TEXT NOT NULL DEFAULT 'nodejs',
                dir         TEXT,
                installed_at INTEGER DEFAULT (strftime('%s','now'))
            );

            CREATE TABLE IF NOT EXISTS logs (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                task_id     TEXT NOT NULL,
                level       TEXT NOT NULL DEFAULT 'info',
                message     TEXT NOT NULL,
                ts          INTEGER DEFAULT (strftime('%s','now'))
            );

            CREATE INDEX IF NOT EXISTS idx_logs_task ON logs(task_id);
            CREATE INDEX IF NOT EXISTS idx_tasks_status ON tasks(status);

            -- License cache: local copy of what the user is subscribed to
            -- Refreshed every time plugin-syncer runs. TTL checked at runtime.
            CREATE TABLE IF NOT EXISTS licenses (
                slug        TEXT PRIMARY KEY,
                status      TEXT NOT NULL DEFAULT 'active',  -- active | expired | suspended
                granted_at  INTEGER DEFAULT (strftime('%s','now')),
                expires_at  INTEGER,  -- null = never expires
                checked_at  INTEGER DEFAULT (strftime('%s','now'))
            );
        `);

        this.logger.debug(`Storage initialized: ${dbPath}`);
    }

    // ── Tasks ─────────────────────────────────────────────────────────────────

    createTask(id, pluginId, runtime, params) {
        if (this._noop) return;
        this.db.prepare(
            `INSERT OR REPLACE INTO tasks (id, plugin_id, runtime, status, params)
             VALUES (?, ?, ?, 'running', ?)`
        ).run(id, pluginId, runtime, JSON.stringify(params));
    }

    updateTask(id, status, result = null, error = null) {
        if (this._noop) return;
        this.db.prepare(
            `UPDATE tasks SET status=?, result=?, error=?, updated_at=strftime('%s','now') WHERE id=?`
        ).run(status, result ? JSON.stringify(result) : null, error, id);
    }

    getTask(id) {
        if (this._noop) return null;
        const row = this.db.prepare('SELECT * FROM tasks WHERE id=?').get(id);
        if (!row) return null;
        return {
            ...row,
            params: row.params ? JSON.parse(row.params) : {},
            result: row.result ? JSON.parse(row.result) : null,
        };
    }

    getRecentTasks(limit = 50) {
        if (this._noop) return [];
        return this.db.prepare(
            'SELECT * FROM tasks ORDER BY created_at DESC LIMIT ?'
        ).all(limit);
    }

    // ── Logs ──────────────────────────────────────────────────────────────────

    addLog(taskId, level, message) {
        if (this._noop) return;
        this.db.prepare(
            'INSERT INTO logs (task_id, level, message) VALUES (?, ?, ?)'
        ).run(taskId, level, message);
    }

    getLogs(taskId, limit = 500) {
        if (this._noop) return [];
        return this.db.prepare(
            'SELECT level, message, ts FROM logs WHERE task_id=? ORDER BY id ASC LIMIT ?'
        ).all(taskId, limit);
    }

    // ── Plugins ───────────────────────────────────────────────────────────────

    upsertPlugin(manifest) {
        if (this._noop) return;
        this.db.prepare(
            `INSERT INTO plugins (id, slug, name, version, runtime, dir)
             VALUES (?, ?, ?, ?, ?, ?)
             ON CONFLICT(slug) DO UPDATE SET
               version=excluded.version, name=excluded.name,
               runtime=excluded.runtime, dir=excluded.dir`
        ).run(
            manifest.id, manifest.tool_slug || manifest.id,
            manifest.name, manifest.version,
            manifest.runtime || 'nodejs', manifest.dir
        );
    }

    // ── License Cache ─────────────────────────────────────────────────────────

    /**
     * Write or refresh a license entry from the platform sync.
     * @param {string} slug
     * @param {object} opts  { status, expiresAt: Date|null }
     */
    upsertLicense(slug, { status = 'active', expiresAt = null } = {}) {
        if (this._noop) return;
        const expiresTs = expiresAt ? Math.floor(new Date(expiresAt).getTime() / 1000) : null;
        this.db.prepare(
            `INSERT INTO licenses (slug, status, expires_at, checked_at)
             VALUES (?, ?, ?, strftime('%s','now'))
             ON CONFLICT(slug) DO UPDATE SET
               status=excluded.status,
               expires_at=excluded.expires_at,
               checked_at=strftime('%s','now')`
        ).run(slug, status, expiresTs);
    }

    /**
     * Check if slug has a valid (non-expired, active) cached license.
     * Returns null if no entry — caller must decide how to handle.
     * @param {string} slug
     * @param {number} maxAgeSeconds  Cache TTL — default 1 hour
     * @returns {'active'|'expired'|'suspended'|'not_found'|'cache_stale'}
     */
    checkLicense(slug, maxAgeSeconds = 3600) {
        if (this._noop) return 'not_found';
        const row = this.db.prepare('SELECT * FROM licenses WHERE slug=?').get(slug);
        if (!row) return 'not_found';

        const now = Math.floor(Date.now() / 1000);

        // Cache staleness check
        if (now - row.checked_at > maxAgeSeconds) return 'cache_stale';

        // Expiry check
        if (row.expires_at && now > row.expires_at) return 'expired';

        return row.status; // 'active' | 'suspended'
    }

    /** Remove a specific license (subscription cancelled/revoked) */
    revokeLicense(slug) {
        if (this._noop) return;
        this.db.prepare('DELETE FROM licenses WHERE slug=?').run(slug);
    }

    /** Wipe all cached licenses (called on token change / logout) */
    clearLicenses() {
        if (this._noop) return;
        this.db.prepare('DELETE FROM licenses').run();
    }

    close() {
        if (this.db) this.db.close();
    }
}

module.exports = Storage;
