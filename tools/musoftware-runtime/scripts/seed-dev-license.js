#!/usr/bin/env node
/**
 * seed-dev-license.js
 * ───────────────────
 * Seeds the local SQLite license cache with an active dev license
 * for `tiktok-scraper` so the plugin can run without platform auth.
 *
 * Usage:
 *   node tools/musoftware-runtime/scripts/seed-dev-license.js
 *
 * Only for local development. In production the syncer handles this.
 */
'use strict';

const path = require('path');
const Database = require('better-sqlite3');

const DB_PATH = path.join(__dirname, '../storage/runtime.db');

try {
    const db = new Database(DB_PATH);
    db.pragma('journal_mode = WAL');

    // Ensure the licenses table exists
    db.exec(`
        CREATE TABLE IF NOT EXISTS licenses (
            slug        TEXT PRIMARY KEY,
            status      TEXT NOT NULL DEFAULT 'active',
            granted_at  INTEGER DEFAULT (strftime('%s','now')),
            expires_at  INTEGER,
            checked_at  INTEGER DEFAULT (strftime('%s','now'))
        );
    `);

    // Seed the tiktok-scraper license — expires in 30 days
    const expiresAt = Math.floor(Date.now() / 1000) + (30 * 24 * 3600);

    const slugs = ['tiktok-scraper', 'echo-test', 'whatsapp-sender', 'snapchat-tool'];

    for (const slug of slugs) {
        db.prepare(`
            INSERT INTO licenses (slug, status, expires_at, checked_at)
            VALUES (?, 'active', ?, strftime('%s','now'))
            ON CONFLICT(slug) DO UPDATE SET
                status     = 'active',
                expires_at = excluded.expires_at,
                checked_at = strftime('%s','now')
        `).run(slug, expiresAt);

        console.log(`✓ Seeded license: ${slug} (expires: ${new Date(expiresAt * 1000).toLocaleDateString()})`);
    }

    db.close();
    console.log('\n✓ Dev licenses seeded. Restart the runtime if it is running.');
} catch (err) {
    if (err.message.includes('Cannot find module')) {
        console.error('✗ better-sqlite3 not installed. Run: npm install in musoftware-runtime/');
    } else {
        console.error('✗ Error:', err.message);
    }
    process.exit(1);
}
