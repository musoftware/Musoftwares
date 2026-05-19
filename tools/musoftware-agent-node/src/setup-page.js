/**
 * Setup Wizard — serves a local HTML page for first-time agent configuration.
 *
 * GET /setup → one-page setup form
 *
 * Flow:
 *   1. User installs agent
 *   2. Agent opens http://127.0.0.1:18400/setup in browser
 *   3. User pastes their API token from musoftware.com/settings/tokens
 *   4. Token is saved locally → agent starts syncing plugins
 *
 * Alternatively, the website can POST /auth directly via JS (no setup page needed).
 */

'use strict';

function setupPage(config) {
    const isConfigured = !!config.token;

    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musoftware Agent — Setup</title>
    <style>
        :root {
            --bg:       #0a0a0c;
            --surface:  #13131a;
            --border:   #1e1e2a;
            --text:     #e2e8f0;
            --muted:    #64748b;
            --accent:   #3b82f6;
            --success:  #22c55e;
            --error:    #ef4444;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem;
            max-width: 480px;
            width: 100%;
        }
        .logo {
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.02em;
            margin-bottom: 0.25rem;
        }
        .logo span { color: var(--accent); }
        .subtitle {
            color: var(--muted);
            font-size: 0.85rem;
            margin-bottom: 2rem;
        }
        .status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            font-size: 0.85rem;
            margin-bottom: 1.5rem;
        }
        .status.connected {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.2);
            color: var(--success);
        }
        .status.disconnected {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.15);
            color: var(--error);
        }
        .dot {
            width: 8px; height: 8px;
            border-radius: 50%;
            display: inline-block;
        }
        .dot.green { background: var(--success); box-shadow: 0 0 6px var(--success); }
        .dot.red   { background: var(--error);   box-shadow: 0 0 6px var(--error);   }
        label {
            display: block;
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--muted);
            margin-bottom: 0.4rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 0.7rem 1rem;
            color: var(--text);
            font-size: 0.9rem;
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            outline: none;
            transition: border-color 0.15s;
        }
        input:focus { border-color: var(--accent); }
        .field { margin-bottom: 1rem; }
        .hint {
            font-size: 0.75rem;
            color: var(--muted);
            margin-top: 0.3rem;
            line-height: 1.4;
        }
        .hint a { color: var(--accent); text-decoration: none; }
        .hint a:hover { text-decoration: underline; }
        button {
            width: 100%;
            padding: 0.75rem;
            background: var(--accent);
            border: none;
            border-radius: 10px;
            color: white;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s, transform 0.1s;
            margin-top: 0.5rem;
        }
        button:hover { opacity: 0.9; }
        button:active { transform: scale(0.98); }
        button:disabled { opacity: 0.5; cursor: not-allowed; }
        .msg {
            margin-top: 1rem;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            display: none;
        }
        .msg.ok  { background: rgba(34,197,94,0.1); color: var(--success); border: 1px solid rgba(34,197,94,0.2); display: block; }
        .msg.err { background: rgba(239,68,68,0.08); color: var(--error); border: 1px solid rgba(239,68,68,0.15); display: block; }
        .plugins {
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border);
        }
        .plugins h3 { font-size: 0.85rem; color: var(--muted); margin-bottom: 0.8rem; font-weight: 500; }
        .plugin-item {
            display: flex; align-items: center; gap: 0.6rem;
            padding: 0.5rem 0; font-size: 0.85rem;
        }
        .plugin-item .pill {
            background: rgba(59,130,246,0.15); color: var(--accent);
            padding: 0.15rem 0.5rem; border-radius: 6px; font-size: 0.7rem; font-weight: 600;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="logo">mu<span>software</span> agent</div>
    <div class="subtitle">Node.js Runtime Agent — v1.0.0</div>

    <div class="status ${isConfigured ? 'connected' : 'disconnected'}">
        <span class="dot ${isConfigured ? 'green' : 'red'}"></span>
        ${isConfigured
            ? 'Connected — syncing plugins automatically'
            : 'Not connected — paste your token below to get started'}
    </div>

    <form id="setupForm">
        <div class="field">
            <label for="token">API Token</label>
            <input type="password" id="token" name="token" placeholder="paste your token here" value="${config.token || ''}" />
            <div class="hint">
                ${config.platformUrl.includes('127.0.0.1') || config.platformUrl.includes('localhost')
                    ? '<strong style="color:#22c55e">✓ Local dev mode</strong> — token loaded from <code style="background:#1e1e2a;padding:0.1rem 0.3rem;border-radius:4px;font-size:0.7rem">.env</code> file. Edit it to change.'
                    : 'Get your token from <a href="' + config.platformUrl + '/settings/tokens" target="_blank">' + config.platformUrl.replace('https://', '') + '/settings/tokens</a>'
                }
            </div>
        </div>
        <div class="field">
            <label for="userId">User ID</label>
            <input type="text" id="userId" name="userId" placeholder="your user ID" value="${config.userId || ''}" />
            <div class="hint">Your numerical user ID (shown on the same tokens page)</div>
        </div>
        <button type="submit" id="saveBtn">${isConfigured ? 'Update Configuration' : 'Connect Agent'}</button>
    </form>

    <div class="msg" id="msg"></div>

    <div class="plugins" id="pluginsSection" style="display:none">
        <h3>Installed Plugins</h3>
        <div id="pluginsList"></div>
    </div>
</div>

<script>
    const form = document.getElementById('setupForm');
    const msg  = document.getElementById('msg');

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const token  = document.getElementById('token').value.trim();
        const userId = document.getElementById('userId').value.trim();
        if (!token || !userId) return showMsg('Token and User ID are required', 'err');

        document.getElementById('saveBtn').disabled = true;
        try {
            const res = await fetch('/auth', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ token, userId }),
            });
            const data = await res.json();
            if (data.ok) {
                showMsg('✓ Connected! Agent will now sync your plugins automatically.', 'ok');
                setTimeout(() => location.reload(), 1500);
            } else {
                showMsg(data.error || 'Failed to save', 'err');
            }
        } catch (err) {
            showMsg('Network error: ' + err.message, 'err');
        } finally {
            document.getElementById('saveBtn').disabled = false;
        }
    });

    function showMsg(text, type) {
        msg.textContent = text;
        msg.className = 'msg ' + type;
    }

    // Load plugins list
    fetch('/plugins').then(r => r.json()).then(data => {
        if (data.plugins?.length) {
            document.getElementById('pluginsSection').style.display = 'block';
            document.getElementById('pluginsList').innerHTML = data.plugins.map(p =>
                '<div class="plugin-item">' +
                '<span class="pill">v' + p.version + '</span>' +
                '<span>' + p.name + '</span>' +
                '</div>'
            ).join('');
        }
    }).catch(() => {});
</script>
</body>
</html>`;
}

module.exports = { setupPage };
