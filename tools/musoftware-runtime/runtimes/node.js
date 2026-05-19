'use strict';

/**
 * Node.js Worker Runtime Launcher
 *
 * Spawns Node.js workers using the current Node binary.
 * Handles npm dependency installation if package.json exists.
 */

const { spawn, execSync } = require('child_process');
const fs   = require('fs');
const path = require('path');

class NodeRuntime {
    constructor(logger) {
        this.logger = logger;
        this.bin    = process.execPath; // same node binary running the runtime
    }

    /**
     * Install plugin deps if package.json exists and node_modules is missing.
     */
    async installDeps(pluginDir) {
        const pkgJson    = path.join(pluginDir, 'package.json');
        const nodeModules = path.join(pluginDir, 'node_modules');

        if (!fs.existsSync(pkgJson)) return;
        if (fs.existsSync(nodeModules)) return; // already installed

        this.logger.info(`[node-runtime] Installing deps in ${pluginDir}...`);
        execSync('npm install --production --silent', {
            cwd: pluginDir, timeout: 120_000, stdio: 'ignore',
        });
        this.logger.info(`[node-runtime] Deps installed`);
    }

    /**
     * Spawn a Node.js worker process.
     * @param {object} plugin   — manifest + dir + entryPath
     * @param {string} taskId
     * @param {object} params
     * @param {object} env      — extra env vars
     * @returns ChildProcess
     */
    spawn(plugin, taskId, params, env = {}) {
        return spawn(this.bin, [plugin.entryPath], {
            cwd:   plugin.dir,
            stdio: ['pipe', 'pipe', 'pipe'],
            env: {
                ...process.env,
                MUSOFTWARE_RUNTIME:   'nodejs',
                MUSOFTWARE_TASK_ID:   taskId,
                MUSOFTWARE_PLUGIN_ID: plugin.id,
                MUSOFTWARE_PARAMS:    JSON.stringify(params),
                ...env,
            },
        });
    }
}

module.exports = NodeRuntime;
