'use strict';

const fs = require('fs');
const path = require('path');

const CONFIG_FILE = path.join(__dirname, '../config/agent.json');

const DEFAULTS = {
    port:        18400,
    platformUrl: 'http://127.0.0.1:8000',  // local dev default — override via .env or agent.json
    token:       null,
    userId:      null,
    logLevel:    'info',
    pluginsDir:  path.join(__dirname, '../plugins'),
    autoUpdate:  true,
};

function loadConfig() {
    let saved = {};
    if (fs.existsSync(CONFIG_FILE)) {
        try { saved = JSON.parse(fs.readFileSync(CONFIG_FILE, 'utf8')); } catch (_) { }
    }
    return {
        ...DEFAULTS,
        ...saved,
        port:        parseInt(process.env.MUSOFTWARE_PORT      || saved.port        || DEFAULTS.port),
        platformUrl: process.env.MUSOFTWARE_PLATFORM           || saved.platformUrl || DEFAULTS.platformUrl,
        token:       process.env.MUSOFTWARE_TOKEN              || saved.token       || null,
        userId:      process.env.MUSOFTWARE_USER_ID            || saved.userId      || null,
        logLevel:    process.env.MUSOFTWARE_LOG_LEVEL           || saved.logLevel   || DEFAULTS.logLevel,
    };
}

function saveConfig(updates) {
    const current = loadConfig();
    const merged = { ...current, ...updates };
    fs.mkdirSync(path.dirname(CONFIG_FILE), { recursive: true });
    fs.writeFileSync(CONFIG_FILE, JSON.stringify(merged, null, 2), 'utf8');
    return merged;
}

module.exports = { loadConfig, saveConfig };
