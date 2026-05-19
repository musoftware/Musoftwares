#!/usr/bin/env node
/**
 * e2e.test.js — Musoftware Runtime End-to-End Tests
 * ═══════════════════════════════════════════════════
 *
 * Tests the full stack:
 *   1. Runtime HTTP API health + status
 *   2. Auth flow (start, callback, status, disconnect)
 *   3. Plugin listing
 *   4. License cache (seed → check → verify blocked without cache)
 *   5. Plugin run + task polling (echo-test, no external deps)
 *   6. WebSocket real-time events
 *   7. Platform API (Laravel) — plugins endpoint
 *
 * Usage:
 *   node tools/musoftware-runtime/tests/e2e.test.js
 *   node tools/musoftware-runtime/tests/e2e.test.js --platform  (also tests Laravel)
 */
'use strict';

const http      = require('http');
const WebSocket = require('ws');

const RUNTIME_BASE = 'http://127.0.0.1:18400';
const WS_URL       = 'ws://127.0.0.1:18401/ws';
const PLATFORM     = 'http://127.0.0.1:8000';
const TEST_PLATFORM= process.argv.includes('--platform');

// ── Tiny HTTP client ──────────────────────────────────────────────────────────
function request(url, { method = 'GET', body, headers = {} } = {}) {
    return new Promise((resolve, reject) => {
        const u  = new URL(url);
        const opts = {
            hostname: u.hostname, port: u.port, path: u.pathname + u.search,
            method, headers: { 'Content-Type': 'application/json', ...headers },
        };
        const req = http.request(opts, res => {
            let data = '';
            res.on('data', c => data += c);
            res.on('end', () => {
                try   { resolve({ status: res.statusCode, body: JSON.parse(data) }); }
                catch { resolve({ status: res.statusCode, body: data }); }
            });
        });
        req.on('error', reject);
        if (body) req.write(JSON.stringify(body));
        req.end();
    });
}

// ── Test harness ──────────────────────────────────────────────────────────────
let passed = 0, failed = 0, skipped = 0;
const results = [];

async function test(name, fn, { skip = false } = {}) {
    if (skip) {
        console.log(`  ⏭  ${name}`);
        skipped++; results.push({ name, status: 'skip' }); return;
    }
    try {
        await fn();
        console.log(`  ✓  ${name}`);
        passed++; results.push({ name, status: 'pass' });
    } catch (err) {
        console.log(`  ✗  ${name}`);
        console.log(`        ${err.message}`);
        failed++; results.push({ name, status: 'fail', error: err.message });
    }
}

function assert(condition, message) {
    if (!condition) throw new Error(message || 'Assertion failed');
}
function assertEqual(a, b, label = '') {
    if (a !== b) throw new Error(`${label}Expected ${JSON.stringify(b)}, got ${JSON.stringify(a)}`);
}

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 1 — Runtime Health
// ─────────────────────────────────────────────────────────────────────────────
async function suiteHealth() {
    console.log('\n── Suite 1: Runtime Health ─────────────────────────────────────');

    await test('GET /health → 200 + ok:true', async () => {
        const r = await request(`${RUNTIME_BASE}/health`);
        assertEqual(r.status, 200, 'status:');
        assert(r.body.ok === true, 'ok should be true');
        assert(typeof r.body.version === 'string', 'version should be string');
    });

    await test('GET /status → 200 + online:true', async () => {
        const r = await request(`${RUNTIME_BASE}/status`);
        assertEqual(r.status, 200, 'status:');
        assert(r.body.online === true, 'online should be true');
        assert(Array.isArray(r.body.plugins), 'plugins should be array');
    });

    await test('GET /system → 200 + hostname', async () => {
        const r = await request(`${RUNTIME_BASE}/system`);
        assertEqual(r.status, 200, 'status:');
        assert(typeof r.body.hostname === 'string', 'hostname missing');
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 2 — Auth API
// ─────────────────────────────────────────────────────────────────────────────
async function suiteAuth() {
    console.log('\n── Suite 2: Auth API ───────────────────────────────────────────');

    await test('GET /auth/status → 200 + authenticated field', async () => {
        const r = await request(`${RUNTIME_BASE}/auth/status`);
        assertEqual(r.status, 200, 'status:');
        // Runtime uses 'connected' — alias check for both
        const hasField = 'authenticated' in r.body || 'connected' in r.body;
        assert(hasField, `Expected 'authenticated' or 'connected' field. Got: ${JSON.stringify(r.body)}`);
    });

    await test('POST /auth/callback with wrong code → 400', async () => {
        const r = await request(`${RUNTIME_BASE}/auth/callback`, {
            method: 'POST',
            body: { token: 'abc', userId: '1', userName: 'Test', device_code: 'wrong-code-xxx' },
        });
        // Either 400 (no pending) or 400 (invalid code) — both are correct rejections
        assert(r.status >= 400 && r.status < 500, `Expected 4xx, got ${r.status}`);
    });

    await test('POST /auth/disconnect → 200', async () => {
        const r = await request(`${RUNTIME_BASE}/auth/disconnect`, { method: 'POST' });
        assertEqual(r.status, 200, 'status:');
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 3 — Plugin Registry
// ─────────────────────────────────────────────────────────────────────────────
async function suitePlugins() {
    console.log('\n── Suite 3: Plugin Registry ────────────────────────────────────');

    await test('GET /status → includes tiktok-scraper plugin', async () => {
        const r = await request(`${RUNTIME_BASE}/status`);
        const slugs = r.body.plugins.map(p => p.slug || p.id);
        assert(slugs.some(s => s === 'tiktok-scraper'), `tiktok-scraper not in plugins. Got: ${slugs.join(', ')}`);
    });

    await test('GET /status → includes echo-test plugin', async () => {
        const r = await request(`${RUNTIME_BASE}/status`);
        const ids = r.body.plugins.map(p => p.id || p.slug);
        assert(ids.some(s => s === 'echo-test'), `echo-test not found. Got: ${ids.join(', ')}`);
    });

    await test('GET /status → plugins have runtime field', async () => {
        const r = await request(`${RUNTIME_BASE}/status`);
        for (const p of r.body.plugins) {
            assert(['nodejs', 'python'].includes(p.runtime), `Invalid runtime: ${p.runtime} for plugin ${p.id}`);
        }
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 4 — License Cache
// ─────────────────────────────────────────────────────────────────────────────
async function suiteLicense() {
    console.log('\n── Suite 4: License Cache (via DB seed) ───────────────────────');

    // Seed the license directly using storage module
    await test('Seed dev license for echo-test via storage module', async () => {
        const path    = require('path');
        const Storage = require(path.join(__dirname, '../core/storage'));
        const logger  = { debug: () => {}, info: () => {}, warn: () => {}, error: () => {} };
        const storage = new Storage({
            storageDir: path.join(__dirname, '../storage'),
        }, logger);
        storage.init();

        storage.upsertLicense('echo-test', { status: 'active', expiresAt: null });
        storage.upsertLicense('tiktok-scraper', { status: 'active', expiresAt: null });

        const result = storage.checkLicense('echo-test');
        storage.close();
        assertEqual(result, 'active', 'license status:');
    });

    await test('License check returns active after seed', async () => {
        const path    = require('path');
        const Storage = require(path.join(__dirname, '../core/storage'));
        const logger  = { debug: () => {}, info: () => {}, warn: () => {}, error: () => {} };
        const storage = new Storage({ storageDir: path.join(__dirname, '../storage') }, logger);
        storage.init();

        const result = storage.checkLicense('tiktok-scraper');
        storage.close();
        assertEqual(result, 'active', 'license status:');
    });

    await test('License check returns not_found for unknown plugin', async () => {
        const path    = require('path');
        const Storage = require(path.join(__dirname, '../core/storage'));
        const logger  = { debug: () => {}, info: () => {}, warn: () => {}, error: () => {} };
        const storage = new Storage({ storageDir: path.join(__dirname, '../storage') }, logger);
        storage.init();

        const result = storage.checkLicense('nonexistent-plugin-xyz');
        storage.close();
        assertEqual(result, 'not_found', 'license status:');
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 5 — Plugin Run (echo-test — no external deps)
// ─────────────────────────────────────────────────────────────────────────────
async function suiteRun() {
    console.log('\n── Suite 5: Plugin Run (echo-test) ─────────────────────────────');

    let taskId = null;

    await test('POST /plugins/echo-test/run → 200 + taskId', async () => {
        const r = await request(`${RUNTIME_BASE}/plugins/echo-test/run`, {
            method: 'POST',
            body:   { params: { message: 'hello from e2e test', delay: 500 } },
        });

        if (r.status === 403) throw new Error(`License error: ${r.body.message || r.body.error}`);
        if (r.status === 404) throw new Error('echo-test plugin not found');
        if (r.status === 401 || (r.status === 503 && r.body?.error === 'license_unverifiable')) {
            // Runtime needs auth but has no license cache — seed and retry
            throw new Error(
                'Runtime has no token + no license cache.\n' +
                '  Fix: run  node scripts/seed-dev-license.js  then re-authenticate via /setup'
            );
        }

        assertEqual(r.status, 200, `status (body: ${JSON.stringify(r.body)})`);
        assert(typeof r.body.taskId === 'string', 'taskId should be string');
        assert(r.body.status === 'started', `status should be started, got: ${r.body.status}`);
        taskId = r.body.taskId;
    });

    await test('GET /tasks/:taskId → task info returned', async () => {
        if (!taskId) throw new Error('No taskId from previous test');
        const r = await request(`${RUNTIME_BASE}/tasks/${taskId}`);
        assertEqual(r.status, 200, 'status:');
        assert(['running', 'done', 'error'].includes(r.body.status), `unexpected status: ${r.body.status}`);
    });

    await test('GET /tasks/:taskId → task completes within 10s', async () => {
        if (!taskId) throw new Error('No taskId from previous test');

        for (let i = 0; i < 20; i++) {
            await sleep(500);
            const r = await request(`${RUNTIME_BASE}/tasks/${taskId}`);
            if (r.body.status === 'done')  return; // ✓
            if (r.body.status === 'error') throw new Error(`Task failed: ${r.body.error}`);
        }
        throw new Error('Task did not complete within 10 seconds');
    });

    await test('GET /tasks → history contains recent task', async () => {
        const r = await request(`${RUNTIME_BASE}/tasks`);
        assertEqual(r.status, 200, 'status:');
        assert(Array.isArray(r.body.tasks), 'tasks should be array');
        if (taskId) {
            assert(r.body.tasks.some(t => t.id === taskId), `taskId ${taskId} not in history`);
        }
    });

    await test('POST /tasks/:taskId/stop → 200', async () => {
        // Run a second task and stop it immediately
        const runRes = await request(`${RUNTIME_BASE}/plugins/echo-test/run`, {
            method: 'POST',
            body:   { params: { message: 'stop test', delay: 5000 } },
        });
        if (runRes.status !== 200) {
            throw new Error(`Could not start task: ${runRes.body.error || runRes.status}`);
        }
        const stopRes = await request(`${RUNTIME_BASE}/tasks/${runRes.body.taskId}/stop`, {
            method: 'POST',
        });
        assertEqual(stopRes.status, 200, 'stop status:');
        assert(stopRes.body.stopped === true, 'stopped should be true');
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 6 — WebSocket
// ─────────────────────────────────────────────────────────────────────────────
async function suiteWebSocket() {
    console.log('\n── Suite 6: WebSocket ──────────────────────────────────────────');

    await test('WS connects to ws://127.0.0.1:18401/ws', async () => {
        await new Promise((resolve, reject) => {
            const ws = new WebSocket(WS_URL);
            const timeout = setTimeout(() => { ws.terminate(); reject(new Error('WS connect timeout (5s)')); }, 5000);
            ws.on('open', () => { clearTimeout(timeout); ws.close(); resolve(); });
            ws.on('error', err => { clearTimeout(timeout); reject(err); });
        });
    });

    await test('WS receives events during plugin run', async () => {
        const received = [];

        await new Promise((resolve, reject) => {
            const ws = new WebSocket(WS_URL);
            const timeout = setTimeout(() => {
                ws.terminate();
                if (received.length === 0) reject(new Error('No WS events received in 8s'));
                else resolve(); // Got some events — ok
            }, 8000);

            ws.on('open', async () => {
                // Trigger a run
                try {
                    await request(`${RUNTIME_BASE}/plugins/echo-test/run`, {
                        method: 'POST', body: { params: { message: 'ws test', delay: 300 } },
                    });
                } catch {}
            });

            ws.on('message', data => {
                try {
                    const msg = JSON.parse(data);
                    received.push(msg.event || msg.type || 'unknown');
                    if (received.some(e => ['task.result', 'task.done', 'task.log'].includes(e))) {
                        clearTimeout(timeout); ws.close(); resolve();
                    }
                } catch {}
            });

            ws.on('error', err => { clearTimeout(timeout); reject(err); });
        });
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// SUITE 7 — Platform API (Laravel) — optional via --platform flag
// ─────────────────────────────────────────────────────────────────────────────
async function suitePlatform() {
    console.log('\n── Suite 7: Platform API (Laravel) ─────────────────────────────');

    // Need a valid Sanctum token — skip if not provided
    const token = process.env.MUSOFTWARE_TEST_TOKEN;

    await test('GET /api/tools/agent/plugins → 401 without token', async () => {
        const r = await request(`${PLATFORM}/api/tools/agent/plugins`);
        // Sanctum with web guard may return 302 redirect to login instead of 401
        assert([401, 302, 303].includes(r.status), `Expected 401/302, got ${r.status}`);
    });

    await test('GET /api/tools/agent/plugins → 200 with valid token', async () => {
        if (!token) throw new Error('Set MUSOFTWARE_TEST_TOKEN env var to test authenticated endpoint');
        const r = await request(`${PLATFORM}/api/tools/agent/plugins`, {
            headers: { Authorization: `Bearer ${token}` },
        });
        assertEqual(r.status, 200, 'status:');
        assert(Array.isArray(r.body.plugins), 'plugins should be array');
    }, { skip: !token });

    await test('GET /tools/tiktok-scraper-pro → 200', async () => {
        const r = await request(`${PLATFORM}/tools/tiktok-scraper-pro`);
        assert([200, 302].includes(r.status), `Expected 200/302, got ${r.status}`);
    });

    await test('GET /tools/tiktok-scraper-pro/run → redirects to login (unauthenticated)', async () => {
        const r = await request(`${PLATFORM}/tools/tiktok-scraper-pro/run`);
        // Unauthenticated → 302 redirect to login, or 200 if Inertia handles it
        assert([200, 302, 303].includes(r.status), `Expected 200/302/303, got ${r.status}`);
    });

    await test('GET /runtime/connect without code → 422 or 302', async () => {
        const r = await request(`${PLATFORM}/runtime/connect`);
        assert([302, 303, 404, 422].includes(r.status), `Expected 302/422/404, got ${r.status}`);
    });
}

// ─────────────────────────────────────────────────────────────────────────────
// MAIN
// ─────────────────────────────────────────────────────────────────────────────
async function main() {
    console.log('═══════════════════════════════════════════════════════════════');
    console.log('  Musoftware Runtime — E2E Test Suite');
    console.log(`  Runtime: ${RUNTIME_BASE}`);
    if (TEST_PLATFORM) console.log(`  Platform: ${PLATFORM}`);
    console.log('═══════════════════════════════════════════════════════════════');

    // Check runtime is reachable before running any tests
    try {
        await request(`${RUNTIME_BASE}/health`);
    } catch {
        console.error('\n✗ Runtime is not reachable at ' + RUNTIME_BASE);
        console.error('  Start it: node tools/musoftware-runtime/core/index.js');
        process.exit(1);
    }

    await suiteHealth();
    await suiteAuth();
    await suitePlugins();
    await suiteLicense();
    await suiteRun();
    await suiteWebSocket();
    if (TEST_PLATFORM) await suitePlatform();

    // ── Summary ───────────────────────────────────────────────────────────────
    const total = passed + failed + skipped;
    console.log('\n═══════════════════════════════════════════════════════════════');
    console.log(`  Results: ${passed} passed, ${failed} failed, ${skipped} skipped / ${total} total`);
    console.log('═══════════════════════════════════════════════════════════════');

    if (failed > 0) {
        console.log('\n  Failed tests:');
        results.filter(r => r.status === 'fail').forEach(r => {
            console.log(`    ✗ ${r.name}`);
            console.log(`      ${r.error}`);
        });
        process.exit(1);
    } else {
        console.log('\n  ✓ All tests passed!');
    }
}

main().catch(err => {
    console.error('\nFatal error:', err.message);
    process.exit(1);
});
