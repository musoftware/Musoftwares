'use strict';

/**
 * Process Monitor
 *
 * Wraps TaskRunner to add:
 *   - crash detection via worker.crashed event
 *   - auto-restart for plugins with "autoStart": true
 *   - per-plugin restart delay + max-retry circuit breaker
 */

const EventEmitter = require('events');

const MAX_RESTARTS  = 5;
const RESTART_DELAY = 5_000; // ms between restarts

class ProcessMonitor extends EventEmitter {
    constructor(runner, pluginLoader, logger) {
        super();
        this.runner       = runner;
        this.pluginLoader = pluginLoader;
        this.logger       = logger;
        this._restarts    = new Map(); // pluginId → { count, lastAt }
    }

    start() {
        this.runner.on('worker.crashed', ({ taskId, pluginId, error }) => {
            this.logger.warn(`[monitor] Worker crashed: plugin=${pluginId} error=${error}`);
            this.emit('worker.crashed', { taskId, pluginId, error });
            this._maybeRestart(pluginId);
        });

        this.runner.on('task.done',  d => this.emit('task.done',  d));
        this.runner.on('task.error', d => this.emit('task.error', d));
        this.runner.on('task.log',   d => this.emit('task.log',   d));
        this.runner.on('task.progress', d => this.emit('task.progress', d));
        this.runner.on('worker.started', d => this.emit('worker.started', d));

        this.logger.info('[monitor] Process monitor active');
    }

    _maybeRestart(pluginId) {
        const plugin = this.pluginLoader.getById(pluginId);
        if (!plugin?.autoStart) return; // only auto-restart persistent plugins

        const state = this._restarts.get(pluginId) || { count: 0, lastAt: 0 };

        // Reset counter if last crash was > 5 minutes ago
        if (Date.now() - state.lastAt > 5 * 60_000) state.count = 0;

        if (state.count >= MAX_RESTARTS) {
            this.logger.error(`[monitor] Plugin ${pluginId} exceeded max restarts (${MAX_RESTARTS}). Giving up.`);
            return;
        }

        state.count++;
        state.lastAt = Date.now();
        this._restarts.set(pluginId, state);

        const delay = RESTART_DELAY * state.count;
        this.logger.info(`[monitor] Restarting ${pluginId} in ${delay}ms (attempt ${state.count})`);

        setTimeout(async () => {
            try {
                await this.runner.run(plugin, plugin._lastParams || {});
                this.logger.info(`[monitor] Restarted: ${pluginId}`);
            } catch (err) {
                this.logger.error(`[monitor] Restart failed for ${pluginId}: ${err.message}`);
            }
        }, delay);
    }

    /** Auto-start plugins with autoStart: true on runtime boot */
    async bootAutoStartPlugins() {
        const plugins = this.pluginLoader.getAll().filter(p => p.autoStart);
        for (const plugin of plugins) {
            this.logger.info(`[monitor] Auto-starting: ${plugin.id}`);
            try {
                await this.runner.run(plugin, {});
            } catch (err) {
                this.logger.error(`[monitor] Auto-start failed for ${plugin.id}: ${err.message}`);
            }
        }
    }
}

module.exports = ProcessMonitor;
