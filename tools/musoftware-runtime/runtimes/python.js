'use strict';

/**
 * Python Worker Runtime Launcher
 *
 * Auto-detects the Python binary (python3 → python → py).
 * Handles pip dependency installation if requirements.txt exists.
 */

const { spawn, execSync, spawnSync } = require('child_process');
const fs   = require('fs');
const path = require('path');

class PythonRuntime {
    constructor(config, logger) {
        this.logger  = logger;
        this.bin     = config.pythonBin || this._detectBin();
        this.logger.info(`[python-runtime] Using binary: ${this.bin}`);
    }

    _detectBin() {
        const candidates = process.platform === 'win32'
            ? ['python', 'python3', 'py']
            : ['python3', 'python'];

        for (const bin of candidates) {
            try {
                const result = spawnSync(bin, ['--version'], { timeout: 3000 });
                if (result.status === 0) {
                    const ver = (result.stdout || result.stderr || '').toString().trim();
                    this.logger && this.logger.debug(`[python-runtime] Found: ${bin} → ${ver}`);
                    return bin;
                }
            } catch (_) {}
        }

        this.logger && this.logger.warn(
            '[python-runtime] No Python binary found. ' +
            'Install Python or set PYTHON_BIN in .env'
        );
        return 'python'; // fallback — will fail gracefully at task start
    }

    /**
     * Install plugin deps if requirements.txt exists.
     */
    async installDeps(pluginDir) {
        const reqTxt = path.join(pluginDir, 'requirements.txt');
        if (!fs.existsSync(reqTxt)) return;

        this.logger.info(`[python-runtime] Installing pip deps in ${pluginDir}...`);
        execSync(
            `${this.bin} -m pip install -r requirements.txt --quiet --disable-pip-version-check`,
            { cwd: pluginDir, timeout: 180_000, stdio: 'ignore' }
        );
        this.logger.info('[python-runtime] Pip deps installed');
    }

    /**
     * Spawn a Python worker process.
     */
    spawn(plugin, taskId, params, env = {}) {
        return spawn(this.bin, [plugin.entryPath], {
            cwd:   plugin.dir,
            stdio: ['pipe', 'pipe', 'pipe'],
            env: {
                ...process.env,
                MUSOFTWARE_RUNTIME:   'python',
                MUSOFTWARE_TASK_ID:   taskId,
                MUSOFTWARE_PLUGIN_ID: plugin.id,
                MUSOFTWARE_PARAMS:    JSON.stringify(params),
                PYTHONUNBUFFERED:     '1', // force line-buffered stdout
                ...env,
            },
        });
    }

    get available() {
        try {
            const r = spawnSync(this.bin, ['--version'], { timeout: 2000 });
            return r.status === 0;
        } catch {
            return false;
        }
    }
}

module.exports = PythonRuntime;
