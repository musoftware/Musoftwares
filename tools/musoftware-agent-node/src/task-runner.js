/**
 * Task Runner — Node.js Agent
 *
 * Spawns plugin workers as child Node.js processes.
 * Each worker communicates via stdout (JSON lines):
 *   { type: "log",      level, message }
 *   { type: "progress", percent, message }
 *   { type: "result",   data }
 *   { type: "error",    message }
 *
 * The runner broadcasts all events to connected browser clients via WS.
 */

'use strict';

const { spawn }      = require('child_process');
const { v4: uuidv4 } = require('uuid');
const EventEmitter   = require('events');

class TaskRunner extends EventEmitter {
    constructor(logger) {
        super();
        this.logger  = logger;
        this._tasks  = new Map(); // taskId → { process, pluginId, status, logs[] }
    }

    /**
     * Run a plugin worker.
     * @param {object} plugin  — from PluginLoader
     * @param {object} params  — task parameters from website
     * @returns {string}       taskId
     */
    run(plugin, params = {}) {
        const taskId = uuidv4();
        const logs   = [];

        this.logger.info(`Starting task [${taskId}] plugin=${plugin.id}`);

        const child = spawn(process.execPath, [plugin.entryPath], {
            cwd:   plugin.dir,
            stdio: ['pipe', 'pipe', 'pipe'],
            env: {
                ...process.env,
                MUSOFTWARE_TASK_ID:   taskId,
                MUSOFTWARE_PLUGIN_ID: plugin.id,
                MUSOFTWARE_PARAMS:    JSON.stringify(params),
            },
        });

        this._tasks.set(taskId, { process: child, pluginId: plugin.id, status: 'running', logs });

        // ── stdout → JSON line protocol ─────────────────────────────────────
        let buf = '';
        child.stdout.on('data', chunk => {
            buf += chunk.toString();
            const lines = buf.split('\n');
            buf = lines.pop();
            for (const line of lines) {
                if (!line.trim()) continue;
                this._handleLine(taskId, plugin.id, line.trim(), logs);
            }
        });

        // ── stderr → error log ──────────────────────────────────────────────
        child.stderr.on('data', chunk => {
            const msg = chunk.toString().trim();
            if (!msg) return;
            const entry = { taskId, level: 'error', message: msg };
            logs.push(entry);
            this.emit('task.log', entry);
        });

        // ── exit ────────────────────────────────────────────────────────────
        child.on('exit', (code) => {
            const task = this._tasks.get(taskId);
            if (task) task.status = code === 0 ? 'done' : 'failed';

            if (code === 0) {
                this.emit('task.done',  { taskId, pluginId: plugin.id });
                this.logger.info(`Task done [${taskId}]`);
            } else {
                this.emit('task.error', { taskId, pluginId: plugin.id, error: `Exit ${code}` });
                this.logger.warn(`Task failed [${taskId}] exit=${code}`);
            }
        });

        child.on('error', err => {
            this.emit('task.error', { taskId, pluginId: plugin.id, error: err.message });
            this.logger.error(`Task spawn error [${taskId}]:`, err.message);
        });

        return taskId;
    }

    _handleLine(taskId, pluginId, line, logs) {
        try {
            const msg = JSON.parse(line);
            let event;

            switch (msg.type) {
                case 'log':
                    event = { taskId, level: msg.level || 'info', message: msg.message };
                    logs.push(event);
                    this.emit('task.log', event);
                    break;
                case 'progress':
                    this.emit('task.progress', { taskId, pluginId, percent: msg.percent, message: msg.message });
                    break;
                case 'result':
                    this.emit('task.done', { taskId, pluginId, result: msg.data });
                    break;
                case 'error':
                    this.emit('task.error', { taskId, pluginId, error: msg.message });
                    break;
                default:
                    logs.push({ taskId, level: 'debug', message: line });
                    this.emit('task.log', { taskId, level: 'debug', message: line });
            }
        } catch {
            // Plain text stdout line
            const entry = { taskId, level: 'info', message: line };
            logs.push(entry);
            this.emit('task.log', entry);
        }
    }

    stop(taskId) {
        const task = this._tasks.get(taskId);
        if (task && task.status === 'running') {
            task.process.kill('SIGTERM');
            task.status = 'stopped';
            this.logger.info(`Task stopped [${taskId}]`);
        }
    }

    stopAll() {
        for (const [taskId, task] of this._tasks) {
            if (task.status === 'running') {
                try { task.process.kill('SIGTERM'); } catch (_) {}
            }
        }
    }

    getLogs(taskId) {
        return this._tasks.get(taskId)?.logs ?? [];
    }

    getActiveTasks() {
        return Array.from(this._tasks.entries())
            .filter(([, t]) => t.status === 'running')
            .map(([id, t]) => ({ taskId: id, pluginId: t.pluginId }));
    }
}

module.exports = TaskRunner;
