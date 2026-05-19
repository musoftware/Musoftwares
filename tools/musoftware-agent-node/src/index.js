/**
 * Musoftware Node.js Agent — Entry Point
 * =========================================
 * A general-purpose plugin host for Node.js tools.
 * Each tool is a plugin in plugins/ — auto-downloaded after subscription.
 *
 * Single HTTP+WS server on 127.0.0.1:18400
 *   Browser UI connects directly → ws://127.0.0.1:18400/ws
 *   Website polls status        → GET http://127.0.0.1:18400/status
 */

'use strict';

// Load .env before anything else (local dev)
require('dotenv').config({ path: require('path').join(__dirname, '../.env') });

const http             = require('http');
const os               = require('os');
const express          = require('express');
const { WebSocketServer, WebSocket } = require('ws');
const cors             = require('cors');
const helmet           = require('helmet');
const { createLogger } = require('./logger');
const { loadConfig, saveConfig } = require('./config');
const PluginLoader     = require('./plugin-loader');
const PluginSyncer     = require('./plugin-syncer');
const TaskRunner       = require('./task-runner');
const { setupPage }    = require('./setup-page');
const { initTray }     = require('./tray');

const AGENT_VERSION = '1.0.0';
const AGENT_TYPE    = 'nodejs';

async function main() {
    const config = loadConfig();
    const logger = createLogger(config);

    logger.info('╔══════════════════════════════════════════╗');
    logger.info(`║  Musoftware Node.js Agent  v${AGENT_VERSION}          ║`);
    logger.info(`║  Listening → http://127.0.0.1:${config.port}         ║`);
    logger.info('╚══════════════════════════════════════════╝');

    // ── Core modules ──────────────────────────────────────────────────────────
    const pluginLoader = new PluginLoader(config, logger);
    await pluginLoader.loadAll();

    const runner = new TaskRunner(logger);

    // ── System tray (packaged builds only) ─────────────────────────────────────
    const tray = await initTray(config, logger);

    // ── Express API ────────────────────────────────────────────────────────────
    const app = express();
    app.use(helmet({ contentSecurityPolicy: false }));
    app.use(cors({
        origin: (origin, cb) => {
            if (!origin) return cb(null, true); // same-origin, Postman
            const ok = /^https?:\/\/(.*\.)?musoftware\.com$/.test(origin)
                || /^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/.test(origin);
            cb(ok ? null : new Error('CORS blocked'), ok);
        },
        credentials: true,
    }));
    app.use(express.json());

    /**
     * GET /setup
     * Local setup wizard — dark-themed page to paste API token.
     */
    app.get('/setup', (req, res) => {
        res.type('html').send(setupPage(config));
    });

    /**
     * GET /system
     * Machine info — useful for the dashboard to show device details.
     */
    app.get('/system', (req, res) => {
        res.json({
            hostname:  os.hostname(),
            platform:  os.platform(),
            arch:      os.arch(),
            release:   os.release(),
            cpus:      os.cpus().length,
            memoryGB:  Math.round(os.totalmem() / (1024 ** 3) * 10) / 10,
            uptime:    Math.round(os.uptime()),
            nodeVer:   process.version,
            agentVer:  AGENT_VERSION,
        });
    });

    /**
     * GET /status
     * Polled by website to detect agent presence.
     * Returns immediately if agent is running.
     */
    app.get('/status', (req, res) => {
        res.json({
            online: true,
            agent: AGENT_TYPE,
            version: AGENT_VERSION,
            plugins: pluginLoader.getAll().map(p => ({
                id: p.id,
                name: p.name,
                slug: p.tool_slug,
                version: p.version,
            })),
            activeTasks: runner.getActiveTasks(),
        });
    });

    /**
     * GET /plugins
     * List installed plugins.
     */
    app.get('/plugins', (req, res) => {
        res.json({ plugins: pluginLoader.getAll() });
    });

    /**
     * POST /plugins/:slug/run
     * Start a plugin task. Website sends this when user clicks "Run".
     * Body: { params: { ... } }
     */
    app.post('/plugins/:slug/run', (req, res) => {
        const plugin = pluginLoader.getBySlug(req.params.slug);
        if (!plugin) {
            return res.status(404).json({ error: `Plugin '${req.params.slug}' not installed` });
        }
        try {
            const taskId = runner.run(plugin, req.body?.params ?? {});
            res.json({ taskId, status: 'started' });
        } catch (err) {
            logger.error(`Run error: ${err.message}`);
            res.status(500).json({ error: err.message });
        }
    });

    /**
     * POST /tasks/:taskId/stop
     * Stop a running task.
     */
    app.post('/tasks/:taskId/stop', (req, res) => {
        runner.stop(req.params.taskId);
        res.json({ stopped: true });
    });

    /**
     * GET /tasks/:taskId/logs
     * Get buffered logs for a task.
     */
    app.get('/tasks/:taskId/logs', (req, res) => {
        res.json({ logs: runner.getLogs(req.params.taskId) });
    });

    /**
     * POST /auth
     * Called once from setup wizard — saves user token locally.
     * Body: { token, userId }
     */
    app.post('/auth', (req, res) => {
        const { token, userId } = req.body ?? {};
        if (!token || !userId) return res.status(400).json({ error: 'token and userId required' });
        saveConfig({ token, userId });
        config.token = token;
        config.userId = userId;
        logger.info(`Auth configured for user ${userId}`);

        // Immediately trigger plugin sync now that we have a token
        if (syncer) syncer._sync();

        res.json({ ok: true });
    });

    // ── Create HTTP server shared with WebSocket ───────────────────────────────
    const server = http.createServer(app);

    // ── WebSocket server (same port, /ws path) ─────────────────────────────────
    const wss = new WebSocketServer({ server, path: '/ws' });
    const clients = new Set();

    const broadcast = (event, data) => {
        const payload = JSON.stringify({ event, data, ts: Date.now() });
        for (const ws of clients) {
            if (ws.readyState === WebSocket.OPEN) {
                try { ws.send(payload); } catch (_) { }
            }
        }
    };

    // Wire runner events → WS broadcast
    runner.on('task.log', d => broadcast('task.log', d));
    runner.on('task.progress', d => broadcast('task.progress', d));
    runner.on('task.done', d => broadcast('task.done', d));
    runner.on('task.error', d => broadcast('task.error', d));

    wss.on('connection', (ws) => {
        clients.add(ws);
        logger.info('Browser WS connected');

        // Greeting with current state
        ws.send(JSON.stringify({
            event: 'agent.ready',
            data: {
                agent: AGENT_TYPE,
                version: AGENT_VERSION,
                plugins: pluginLoader.getAll().map(p => ({ id: p.id, slug: p.tool_slug })),
            },
            ts: Date.now(),
        }));

        ws.on('message', raw => {
            try {
                const { type, payload } = JSON.parse(raw.toString());
                if (type === 'ping') ws.send(JSON.stringify({ event: 'pong', ts: Date.now() }));
                if (type === 'stop') runner.stop(payload?.taskId);
            } catch (_) { }
        });

        ws.on('close', () => { clients.delete(ws); logger.info('Browser WS disconnected'); });
        ws.on('error', () => clients.delete(ws));
    });

    // ── Plugin syncer (auto-downloads after subscription) ─────────────────────
    let syncer = null;
    if (config.token) {
        syncer = new PluginSyncer(config, logger, pluginLoader, broadcast);
        syncer.start();
    } else {
        logger.warn('No auth token — agent waiting for setup. Open musoftwares.com to connect.');
    }

    // ── Start listening ────────────────────────────────────────────────────────
    await new Promise(resolve => server.listen(config.port, '127.0.0.1', resolve));
    logger.info(`Agent ready — http://127.0.0.1:${config.port}`);

    // ── Graceful shutdown ──────────────────────────────────────────────────────
    const shutdown = (sig) => {
        logger.info(`${sig} — shutting down`);
        syncer?.stop();
        runner.stopAll();
        server.close(() => { logger.info('Agent stopped.'); process.exit(0); });
        setTimeout(() => process.exit(1), 5000);
    };
    process.on('SIGINT', () => shutdown('SIGINT'));
    process.on('SIGTERM', () => shutdown('SIGTERM'));
    process.on('uncaughtException', e => logger.error('Uncaught:', e));
    process.on('unhandledRejection', r => logger.error('Unhandled:', r));
}

main().catch(e => { console.error('[FATAL]', e); process.exit(1); });
