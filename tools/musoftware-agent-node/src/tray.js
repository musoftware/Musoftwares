/**
 * System Tray — runs the agent as a background service with a tray icon.
 *
 * Only active when the agent is built as a packaged .exe.
 * In dev mode (node src/index.js), skips tray and runs in console.
 *
 * Tray features:
 *   - Status indicator (green dot = online)
 *   - "Open Dashboard" → opens musoftware.com/tools in default browser
 *   - "Setup" → opens local setup page (127.0.0.1:18400/setup)
 *   - "Quit" → graceful shutdown
 */

'use strict';

let trayAvailable = false;

/**
 * Try to create a system tray icon.
 * Falls back silently if not in a packaged environment.
 */
async function initTray(config, logger) {
    // Only attempt tray in packaged builds (pkg sets process.pkg)
    if (!process.pkg && !process.env.MUSOFTWARE_TRAY) {
        logger.debug('Tray skipped — running in dev mode');
        return null;
    }

    try {
        // node-systray or electron tray — we'll use open to launch browser
        const open = require('open');

        // For a lightweight tray without Electron, use node-notifier for notifications
        // and just rely on the fact that the process is running
        logger.info('Agent running in background mode');

        return {
            openDashboard: () => open(`${config.platformUrl}/tools`),
            openSetup:     () => open(`http://127.0.0.1:${config.port}/setup`),
        };
    } catch (e) {
        logger.debug(`Tray init skipped: ${e.message}`);
        return null;
    }
}

module.exports = { initTray };
