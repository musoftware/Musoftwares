'use strict';

/**
 * Task Runner — Unified Multi-Language Orchestrator
 *
 * Dispatches workers by plugin.runtime:
 *   "nodejs"  → NodeRuntime.spawn()
 *   "python"  → PythonRuntime.spawn()
 *
 * All workers communicate via stdout JSON lines:
 *   { type: "log",      level, message }
 *   { type: "progress", percent, message }
 *   { type: "result",   data }
 *   { type: "error",    message }
 *
 * Events emitted (→ WS broadcast):
 *   task.log | task.progress | task.done | task.error
 *   worker.started | worker.crashed
 */

const { v4: uuidv4 } = require('uuid');
const EventEmitter   = require('events');
const NodeRuntime    = require('../runtimes/node');
const PythonRuntime  = require('../runtimes/python');

class TaskRunner extends EventEmitter {
    constructor(config, logger) {
        super();
        this.logger   = logger;
        this.storage  = null; // injected after storage init
        this._tasks   = new Map(); // taskId → { process, plugin, status, logs[] }
        this._runtimes = {
            nodejs: new NodeRuntime(logger),
            python: new PythonRuntime(config, logger),
        };
    }

    setStorage(storage) {
        this.storage = storage;
    }

    // ── Public API ────────────────────────────────────────────────────────────

    /**
     * Run a plugin worker.
     * @param {object} plugin — from PluginLoader
     * @param {object} params — task parameters from website
     * @returns {string}       taskId
     */
    async run(plugin, params = {}) {
        const taskId  = uuidv4();
        const runtime = plugin.runtime || 'nodejs';
        const logs    = [];

        this.logger.info(`[runner] Starting task=${taskId} plugin=${plugin.id} runtime=${runtime}`);

        const launcher = this._runtimes[runtime];
        if (!launcher) {
            const err = `Unknown runtime: ${runtime}`;
            this.logger.error(`[runner] ${err}`);
            throw new Error(err);
        }

        // Install deps before first run
        try {
            await launcher.installDeps(plugin.dir);
        } catch (e) {
            this.logger.warn(`[runner] Dep install warning: ${e.message}`);
        }

        const child = launcher.spawn(plugin, taskId, params);

        this._tasks.set(taskId, {
            process:  child,
            plugin,
            runtime,
            status:   'running',
            logs,
            startedAt: Date.now(),
        });

        this.storage?.createTask(taskId, plugin.id, runtime, params);
        this.emit('worker.started', { taskId, pluginId: plugin.id, runtime });

        // ── stdout → JSON line protocol ────────────────────────────────────────
        let buf = '';
        child.stdout.on('data', chunk => {
            buf += chunk.toString();
            const lines = buf.split('\n');
            buf = lines.pop();
            for (const line of lines) {
                if (line.trim()) this._handleLine(taskId, plugin, line.trim(), logs);
            }
        });

        // ── stderr ─────────────────────────────────────────────────────────────
        child.stderr.on('data', chunk => {
            const msg = chunk.toString().trim();
            if (!msg) return;
            const entry = { taskId, level: 'error', message: msg };
            logs.push(entry);
            this.storage?.addLog(taskId, 'error', msg);
            this.emit('task.log', entry);
        });

        // ── exit ───────────────────────────────────────────────────────────────
        child.on('exit', code => {
            const task = this._tasks.get(taskId);
            if (!task) return;

            const status = code === 0 ? 'done' : 'failed';
            task.status  = status;
            this.storage?.updateTask(taskId, status);

            if (code === 0) {
                this.emit('task.done', { taskId, pluginId: plugin.id, runtime });
                this.logger.info(`[runner] Task done: ${taskId}`);
            } else {
                const err = `Process exited with code ${code}`;
                this.storage?.updateTask(taskId, 'failed', null, err);
                this.emit('task.error', { taskId, pluginId: plugin.id, runtime, error: err });
                this.logger.warn(`[runner] Task failed: ${taskId} exit=${code}`);
            }
        });

        child.on('error', err => {
            const task = this._tasks.get(taskId);
            if (task) task.status = 'failed';
            this.storage?.updateTask(taskId, 'failed', null, err.message);
            this.emit('task.error', { taskId, pluginId: plugin.id, runtime, error: err.message });
            this.emit('worker.crashed', { taskId, pluginId: plugin.id, error: err.message });
            this.logger.error(`[runner] Spawn error ${taskId}: ${err.message}`);
        });

        return taskId;
    }

    _handleLine(taskId, plugin, line, logs) {
        try {
            const msg = JSON.parse(line);
            let entry;

            switch (msg.type) {
                case 'log':
                    entry = { taskId, pluginId: plugin.id, level: msg.level || 'info', message: msg.message };
                    logs.push(entry);
                    this.storage?.addLog(taskId, entry.level, entry.message);
                    this.emit('task.log', entry);
                    break;

                case 'progress':
                    this.emit('task.progress', {
                        taskId, pluginId: plugin.id,
                        percent: msg.percent, message: msg.message,
                    });
                    break;

                case 'result':
                    this.storage?.updateTask(taskId, 'done', msg.data);
                    this.emit('task.done', { taskId, pluginId: plugin.id, result: msg.data });
                    break;

                case 'error':
                    this.storage?.updateTask(taskId, 'failed', null, msg.message);
                    this.emit('task.error', { taskId, pluginId: plugin.id, error: msg.message });
                    break;

                default:
                    entry = { taskId, level: 'debug', message: line };
                    logs.push(entry);
                    this.emit('task.log', entry);
            }
        } catch {
            const entry = { taskId, level: 'info', message: line };
            logs.push(entry);
            this.emit('task.log', entry);
        }
    }

    // ── Control ───────────────────────────────────────────────────────────────

    stop(taskId) {
        const task = this._tasks.get(taskId);
        if (task?.status === 'running') {
            task.process.kill('SIGTERM');
            task.status = 'stopped';
            this.storage?.updateTask(taskId, 'stopped');
            this.logger.info(`[runner] Task stopped: ${taskId}`);
        }
    }

    stopAll() {
        for (const task of this._tasks.values()) {
            if (task.status === 'running') {
                try { task.process.kill('SIGTERM'); } catch (_) {}
            }
        }
    }

    // ── Queries ───────────────────────────────────────────────────────────────

    getLogs(taskId) {
        return this._tasks.get(taskId)?.logs ?? [];
    }

    getActiveTasks() {
        return Array.from(this._tasks.entries())
            .filter(([, t]) => t.status === 'running')
            .map(([id, t]) => ({
                taskId:   id,
                pluginId: t.plugin.id,
                runtime:  t.runtime,
                since:    t.startedAt,
            }));
    }

    getTask(taskId) {
        const t = this._tasks.get(taskId);
        if (!t) return null;
        return {
            taskId,
            pluginId: t.plugin.id,
            runtime:  t.runtime,
            status:   t.status,
            since:    t.startedAt,
        };
    }
}

module.exports = TaskRunner;
