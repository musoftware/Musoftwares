'use strict';

/**
 * System Tray — background service helper
 * For packaged builds only (pkg/electron-less service).
 * No-ops in dev mode.
 */

function initTray(config, logger) {
    if (process.env.NODE_ENV !== 'production') {
        logger.debug('[tray] Tray skipped — running in dev mode');
        return;
    }

    // Try loading node-systray if available
    try {
        // Optional dep — not required
        const SysTray = require('systray').default ?? require('systray');
        const tray = new SysTray({
            menu: {
                icon: '',
                title: 'Musoftware Runtime',
                tooltip: `Musoftware Runtime v${require('../package.json').version}`,
                items: [
                    { title: 'Open Setup', tooltip: 'Configure agent', checked: false, enabled: true },
                    { title: 'Open Logs',  tooltip: 'View logs',       checked: false, enabled: true },
                    SysTray.separator,
                    { title: 'Stop Runtime', tooltip: 'Shut down the runtime', checked: false, enabled: true },
                ],
            },
            debug: false,
            copyDir: true,
        });

        tray.onClick(action => {
            if (action.item.title === 'Stop Runtime') {
                logger.info('[tray] Stop requested via tray');
                process.emit('SIGTERM');
            }
            if (action.item.title === 'Open Setup') {
                const { exec } = require('child_process');
                exec(`start http://127.0.0.1:${config.port}/setup`);
            }
        });

        logger.info('[tray] System tray initialized');
    } catch {
        logger.debug('[tray] systray not available — running headless');
    }
}

module.exports = { initTray };
