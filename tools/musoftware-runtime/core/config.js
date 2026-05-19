'use strict';

const fs   = require('fs');
const path = require('path');

// Load .env before anything else
require('dotenv').config({ path: path.join(__dirname, '../.env') });

const CONFIG_FILE = path.join(__dirname, '../config/runtime.json');

const DEFAULTS = {
    port:        18400,
    wsPort:      18401,
    platformUrl: 'http://127.0.0.1:8000',
    token:       null,
    userId:      null,
    logLevel:    'info',
    pluginsDir:  path.join(__dirname, '../plugins'),
    storageDir:  path.join(__dirname, '../storage'),
    logsDir:     path.join(__dirname, '../logs'),
    updatesDir:  path.join(__dirname, '../updates'),
    autoUpdate:  true,
    updateChannel: 'stable',
    pythonBin:   null,  // auto-detected if null
};

function loadConfig() {
    let saved = {};
    if (fs.existsSync(CONFIG_FILE)) {
        try { saved = JSON.parse(fs.readFileSync(CONFIG_FILE, 'utf8')); } catch (_) {}
    }

    return {
        ...DEFAULTS,
        ...saved,
        port:        parseInt(process.env.MUSOFTWARE_PORT       || saved.port        || DEFAULTS.port),
        wsPort:      parseInt(process.env.MUSOFTWARE_WS_PORT    || saved.wsPort      || DEFAULTS.wsPort),
        platformUrl: process.env.MUSOFTWARE_PLATFORM            || saved.platformUrl || DEFAULTS.platformUrl,
        token:       process.env.MUSOFTWARE_TOKEN               || saved.token       || null,
        userId:      process.env.MUSOFTWARE_USER_ID             || saved.userId      || null,
        logLevel:    process.env.MUSOFTWARE_LOG_LEVEL           || saved.logLevel    || DEFAULTS.logLevel,
        pythonBin:   process.env.PYTHON_BIN                     || saved.pythonBin   || null,
    };
}

function saveConfig(updates) {
    const current = loadConfig();
    const merged  = { ...current, ...updates };
    // Don't persist paths — they're computed
    const { pluginsDir, storageDir, logsDir, updatesDir, ...rest } = merged;
    fs.mkdirSync(path.dirname(CONFIG_FILE), { recursive: true });
    fs.writeFileSync(CONFIG_FILE, JSON.stringify(rest, null, 2), 'utf8');
    return merged;
}

module.exports = { loadConfig, saveConfig };
