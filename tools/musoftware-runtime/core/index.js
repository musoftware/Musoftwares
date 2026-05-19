'use strict';

/**
 * Musoftware Runtime Agent — Entry Point
 * =========================================
 * ONE unified runtime for all plugins (Node.js + Python + future)
 *
 * HTTP API  → http://127.0.0.1:18400
 * WebSocket → ws://127.0.0.1:18401/ws
 *
 * Architecture:
 *   Browser (website) → Runtime HTTP/WS → Plugin Workers (child processes)
 *
 * Frontend NEVER talks directly to workers.
 */

'use strict';

const http    = require('http');
const os      = require('os');
const express = require('express');
const { WebSocketServer, WebSocket } = require('ws');
const cors    = require('cors');
const helmet  = require('helmet');

const { loadConfig, saveConfig } = require('./config');
const { createLogger }           = require('./logger');
const Storage                    = require('./storage');
const PluginLoader               = require('./plugin-loader');
const PluginSyncer               = require('./plugin-syncer');
const TaskRunner                 = require('./task-runner');
const ProcessMonitor             = require('./process-monitor');
const UpdateChecker              = require('./update-checker');
const DeviceAuth                 = require('./device-auth');
const { setupPage }              = require('./setup-page');


const RUNTIME_VERSION = require('../package.json').version;

// ── CORS: only allow musoftware.com + localhost ───────────────────────────────
const ALLOWED_ORIGIN = /^https?:\/\/(.*\.)?musoftware\.com$|^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/;

async function main() {
    const config = loadConfig();
    const logger = createLogger(config);

    logger.info('╔══════════════════════════════════════════════╗');
    logger.info(`║  Musoftware Runtime  v${RUNTIME_VERSION.padEnd(8)}               ║`);
    logger.info(`║  HTTP  → http://127.0.0.1:${config.port}              ║`);
    logger.info(`║  WS    → ws://127.0.0.1:${config.wsPort}/ws           ║`);
    logger.info('╚══════════════════════════════════════════════╝');

    // ── Storage ───────────────────────────────────────────────────────────────
    const storage = new Storage(config, logger);
    storage.init();

    // ── Plugin Loader ─────────────────────────────────────────────────────────
    const pluginLoader = new PluginLoader(config, logger);
    await pluginLoader.loadAll();

    // ── Task Runner + Monitor ─────────────────────────────────────────────────
    const runner  = new TaskRunner(config, logger);
    runner.setStorage(storage);
    const monitor = new ProcessMonitor(runner, pluginLoader, logger);

    // ── WebSocket Server (port 18401) ─────────────────────────────────────────
    const wsServer  = http.createServer();
    const wss       = new WebSocketServer({ server: wsServer, path: '/ws' });
    const wsClients = new Set();

    const broadcast = (event, data) => {
        const payload = JSON.stringify({ event, data, ts: Date.now() });
        for (const ws of wsClients) {
            if (ws.readyState === WebSocket.OPEN) {
                try { ws.send(payload); } catch (_) {}
            }
        }
    };

    // Wire monitor events → WS
    monitor.on('task.log',       d => broadcast('task.log',       d));
    monitor.on('task.progress',  d => broadcast('task.progress',  d));
    monitor.on('task.done',      d => broadcast('task.done',      d));
    monitor.on('task.error',     d => broadcast('task.error',     d));
    monitor.on('worker.started', d => broadcast('worker.started', d));
    monitor.on('worker.crashed', d => broadcast('worker.crashed', d));

    wss.on('connection', (ws, req) => {
        const origin = req.headers.origin || '';
        if (origin && !ALLOWED_ORIGIN.test(origin)) {
            ws.close(4003, 'Forbidden origin');
            return;
        }

        wsClients.add(ws);
        logger.info(`[ws] Client connected (${wsClients.size} total)`);

        // Greeting
        ws.send(JSON.stringify({
            event: 'runtime.ready',
            data: {
                version:     RUNTIME_VERSION,
                plugins:     pluginLoader.getAll().map(p => ({
                    id: p.id, slug: p.tool_slug, name: p.name,
                    version: p.version, runtime: p.runtime,
                })),
                activeTasks: runner.getActiveTasks(),
            },
            ts: Date.now(),
        }));

        ws.on('message', raw => {
            try {
                const { type, payload } = JSON.parse(raw.toString());
                if (type === 'ping') {
                    ws.send(JSON.stringify({ event: 'pong', ts: Date.now() }));
                } else if (type === 'stop') {
                    runner.stop(payload?.taskId);
                }
            } catch (_) {}
        });

        ws.on('close', () => {
            wsClients.delete(ws);
            logger.info(`[ws] Client disconnected (${wsClients.size} remaining)`);
        });
        ws.on('error', () => wsClients.delete(ws));
    });

    await new Promise(resolve => wsServer.listen(config.wsPort, '127.0.0.1', resolve));
    logger.info(`[ws] WebSocket server listening on ws://127.0.0.1:${config.wsPort}/ws`);

    // ── Express HTTP API (port 18400) ─────────────────────────────────────────
    const app = express();
    app.use(helmet({ contentSecurityPolicy: false }));
    app.use(cors({
        origin: (origin, cb) => {
            if (!origin) return cb(null, true);
            cb(ALLOWED_ORIGIN.test(origin) ? null : new Error('CORS blocked'), true);
        },
        credentials: true,
    }));
    app.use(express.json());

    // ── GET /setup — local setup wizard ──────────────────────────────────────
    app.get('/setup', (req, res) => res.type('html').send(setupPage(config)));

    // ── GET /status — agent health (polled by website) ────────────────────────
    app.get('/status', (req, res) => {
        res.json({
            online:      true,
            version:     RUNTIME_VERSION,
            wsPort:      config.wsPort,
            plugins:     pluginLoader.getAll().map(p => ({
                id: p.id, name: p.name, slug: p.tool_slug,
                version: p.version, runtime: p.runtime,
            })),
            activeTasks: runner.getActiveTasks(),
        });
    });

    // ── GET /health — simple liveness check (alias for /status) ─────────────────
    app.get('/health', (req, res) => res.json({ ok: true, version: RUNTIME_VERSION }));

    // ── GET /system — machine info ─────────────────────────────────────────────
    app.get('/system', (req, res) => {
        res.json({
            hostname:   os.hostname(),
            platform:   os.platform(),
            arch:       os.arch(),
            release:    os.release(),
            cpus:       os.cpus().length,
            memoryGB:   Math.round(os.totalmem() / (1024 ** 3) * 10) / 10,
            uptime:     Math.round(os.uptime()),
            nodeVer:    process.version,
            runtimeVer: RUNTIME_VERSION,
        });
    });

    // ── GET /plugins — list installed plugins ─────────────────────────────────
    app.get('/plugins', (req, res) => {
        const { runtime } = req.query;
        let plugins = pluginLoader.getAll();
        if (runtime) plugins = plugins.filter(p => p.runtime === runtime);
        res.json({ plugins });
    });

    // ── POST /plugins/reload — hot-reload plugin list from disk ───────────────
    app.post('/plugins/reload', async (req, res) => {
        await pluginLoader.loadAll();
        const plugins = pluginLoader.getAll();
        broadcast('plugins.reloaded', { count: plugins.length });
        logger.info(`[api] Plugins reloaded — ${plugins.length} loaded`);
        res.json({ reloaded: true, count: plugins.length, plugins: plugins.map(p => ({ id: p.id, runtime: p.runtime, version: p.version })) });
    });

    // ── POST /plugins/:slug/run — start a plugin task (LICENSE GATED) ────────
    app.post('/plugins/:slug/run', async (req, res) => {
        const slug   = req.params.slug;
        const plugin = pluginLoader.getBySlug(slug);

        if (!plugin) {
            return res.status(404).json({ error: `Plugin '${slug}' not installed` });
        }

        // ── LICENSE CHECK ────────────────────────────────────────────────────
        // Fast path: check local license cache first.
        // An 'active' cache hit runs immediately — no token or network needed.
        // Token is only required when we need to call the platform for verification.
        const licenseState = storage.checkLicense(slug);

        if (licenseState === 'expired') {
            return res.status(403).json({
                error:   'license_expired',
                message: `Your subscription for '${slug}' has expired. Renew at ${config.platformUrl}/tools.`,
            });
        }

        if (licenseState === 'suspended') {
            return res.status(403).json({
                error:   'license_suspended',
                message: `Access to '${slug}' has been suspended. Contact support.`,
            });
        }

        if (licenseState !== 'active') {
            // 'not_found' or 'cache_stale' — must verify with platform.
            // Requires a valid token.
            if (!config.token) {
                return res.status(401).json({
                    error:     'runtime_not_configured',
                    message:   'Log in at /setup first — the runtime needs to verify your subscription.',
                    setup_url: `http://127.0.0.1:${config.port}/setup`,
                });
            }

            logger.info(`[license] Cache '${licenseState}' for '${slug}' — verifying with platform...`);
            try {
                const axios     = require('axios');
                const verifyRes = await axios.get(
                    `${config.platformUrl}/api/tools/agent/plugins`,
                    {
                        params:  { agent: 'nodejs', slug },
                        headers: { Authorization: `Bearer ${config.token}` },
                        timeout: 8_000,
                    }
                );
                const serverPlugins = verifyRes.data?.plugins ?? [];
                const match = serverPlugins.find(p => p.tool_slug === slug);

                if (!match || match.license_status === 'expired') {
                    storage.revokeLicense(slug);
                    return res.status(403).json({
                        error:   'license_required',
                        message: `No active subscription for '${slug}'. Subscribe at ${config.platformUrl}/tools.`,
                        buy_url: `${config.platformUrl}/tools/${slug}`,
                    });
                }

                // Platform confirmed — refresh cache
                storage.upsertLicense(slug, {
                    status:    match.license_status ?? 'active',
                    expiresAt: match.expires_at ?? null,
                });
                logger.info(`[license] Platform confirmed active license for '${slug}'`);

            } catch (verifyErr) {
                if (licenseState === 'cache_stale') {
                    // Platform unreachable but we have a stale cache — grace period
                    logger.warn(`[license] Platform unreachable, using stale cache for '${slug}' (grace period)`);
                } else {
                    // No cache at all + platform unreachable — must block
                    logger.error(`[license] Cannot verify '${slug}': ${verifyErr.message}`);
                    return res.status(503).json({
                        error:   'license_unverifiable',
                        message: 'Cannot verify license — platform unreachable and no local cache.',
                    });
                }
            }
        }
        // ── END LICENSE CHECK ────────────────────────────────────────────────

        try {
            const taskId = await runner.run(plugin, req.body?.params ?? {});
            res.json({ taskId, status: 'started', runtime: plugin.runtime });
        } catch (err) {
            logger.error(`[api] Run error: ${err.message}`);
            res.status(500).json({ error: err.message });
        }
    });



    // ── POST /tasks/:taskId/stop ──────────────────────────────────────────────
    app.post('/tasks/:taskId/stop', (req, res) => {
        runner.stop(req.params.taskId);
        res.json({ stopped: true });
    });

    // ── GET /tasks/:taskId — task info + logs ─────────────────────────────────
    app.get('/tasks/:taskId', (req, res) => {
        const task = runner.getTask(req.params.taskId);
        if (!task) return res.status(404).json({ error: 'Task not found' });
        const logs = runner.getLogs(req.params.taskId);
        res.json({ ...task, logs });
    });

    // ── GET /tasks — recent task history (from SQLite) ────────────────────────
    app.get('/tasks', (req, res) => {
        const tasks = storage.getRecentTasks(50);
        res.json({ tasks });
    });

    // ── POST /auth/start — initiate browser login flow ────────────────────────
    // Called by: tray menu item, /setup page "Login" button
    app.post('/auth/start', (req, res) => {
        if (deviceAuth.isAuthenticated) {
            return res.json({ ok: true, already_connected: true, userId: config.userId });
        }
        const result = deviceAuth.startLogin();
        res.json({ ok: true, ...result });
    });

    // ── POST /auth/callback — platform calls this after user logs in ──────────
    // The website POSTs { token, userId, userName, device_code } here.
    // CORS: only accepted from the configured platform origin.
    app.post('/auth/callback', async (req, res) => {
        const origin = req.headers.origin || req.headers.referer || '';
        if (!deviceAuth.isValidCallbackOrigin(origin)) {
            logger.warn(`[auth] Rejected callback from: ${origin}`);
            return res.status(403).json({ error: 'forbidden_origin' });
        }
        const result = deviceAuth.handleCallback(req.body ?? {});
        if (!result.ok) return res.status(400).json(result);

        // Start or re-sync on new connection
        storage.clearLicenses();
        if (!syncer) {
            syncer = new PluginSyncer(config, logger, pluginLoader, storage, broadcast);
            syncer.start();
        } else {
            syncer.forcSync();
        }
        res.json({ ok: true, message: `Connected as ${result.userName || result.userId}` });
    });

    // ── POST /auth/disconnect ─────────────────────────────────────────────────
    app.post('/auth/disconnect', (req, res) => {
        config.token  = null;
        config.userId = null;
        saveConfig({ token: null, userId: null });
        storage.clearLicenses();
        syncer?.stop();
        syncer = null;
        broadcast('auth.disconnected', {});
        logger.info('[auth] Disconnected');
        res.json({ ok: true });
    });

    // ── GET /auth/status — frontend polls to know if login completed ──────────
    app.get('/auth/status', (req, res) => {
        res.json({
            connected:    deviceAuth.isAuthenticated,
            pendingLogin: deviceAuth.hasPendingLogin,
            userId:       config.userId ?? null,
        });
    });

    // ── GET /version — current runtime version info ────────────────────────────
    app.get('/version', (req, res) => {
        res.json({
            version:  RUNTIME_VERSION,
            channel:  config.updateChannel || 'stable',
            platform: process.platform,
            arch:     process.arch,
            nodeVer:  process.version,
        });
    });

    // ── POST /update/check — force immediate update check ─────────────────────
    app.post('/update/check', async (req, res) => {
        logger.info('[api] Manual update check triggered');
        try {
            const result = await updater.checkNow();
            res.json(result ?? { checked: true, upToDate: true, current: RUNTIME_VERSION });
        } catch (err) {
            res.status(500).json({ error: err.message });
        }
    });

    // ── POST /plugins/:slug/update — update a specific plugin ─────────────────
    app.post('/plugins/:slug/update', async (req, res) => {
        const { downloadUrl } = req.body ?? {};
        const slug = req.params.slug;
        if (!downloadUrl) return res.status(400).json({ error: 'downloadUrl required' });
        try {
            const pluginDir = require('path').join(config.pluginsDir, slug);
            if (require('fs').existsSync(pluginDir)) {
                require('fs').rmSync(pluginDir, { recursive: true, force: true });
            }
            const plugin = await pluginLoader.ensurePlugin(slug, downloadUrl, broadcast);
            broadcast('plugin.updated', { slug, version: plugin.version });
            res.json({ updated: true, version: plugin.version });
        } catch (err) {
            res.status(500).json({ error: err.message });
        }
    });

    // ── POST /plugins/sync — force plugin sync ────────────────────────────────
    app.post('/plugins/sync', (req, res) => {
        if (!syncer) return res.status(400).json({ error: 'Not connected — login first' });
        syncer.forcSync();
        res.json({ syncing: true });
    });

    // ── Create HTTP server ─────────────────────────────────────────────────────
    const httpServer = http.createServer(app);
    await new Promise(resolve => httpServer.listen(config.port, '127.0.0.1', resolve));
    logger.info(`[http] API listening on http://127.0.0.1:${config.port}`);

    // ── Background services ───────────────────────────────────────────────────
    monitor.start();
    await monitor.bootAutoStartPlugins();

    const updater = new UpdateChecker(config, logger, broadcast);
    updater.start();

    // Device auth — handles the browser-based login handshake
    let syncer = null;
    const deviceAuth = new DeviceAuth(config, logger, broadcast, (token, userId, userName) => {
        logger.info(`[auth] Login complete for user ${userName || userId}`);
    });

    if (config.token) {
        // Already authenticated (token saved from previous session in config/runtime.json)
        logger.info(`[runtime] Authenticated as user ${config.userId}`);
        syncer = new PluginSyncer(config, logger, pluginLoader, storage, broadcast);
        syncer.start();
    } else {
        // No token — prompt user via tray
        logger.warn('[runtime] Not connected. Click the tray icon to log in.');
        broadcast('auth.required', {
            message:  'Click the Musoftware tray icon to connect your account.',
            loginUrl: `http://127.0.0.1:${config.port}/auth/start`,
        });
    }

    logger.info('[runtime] ✓ All systems ready');


    // ── Graceful shutdown ─────────────────────────────────────────────────────
    const shutdown = async (sig) => {
        logger.info(`[runtime] ${sig} — shutting down`);
        syncer?.stop();
        updater.stop();
        runner.stopAll();
        storage.close();
        httpServer.close();
        wsServer.close(() => { logger.info('[runtime] Stopped.'); process.exit(0); });
        setTimeout(() => process.exit(1), 5000);
    };

    process.on('SIGINT',  () => shutdown('SIGINT'));
    process.on('SIGTERM', () => shutdown('SIGTERM'));
    process.on('uncaughtException',  e => logger.error('[runtime] Uncaught:', e.message));
    process.on('unhandledRejection', r => logger.error('[runtime] Unhandled rejection:', r));
}

main().catch(e => { console.error('[FATAL]', e); process.exit(1); });
