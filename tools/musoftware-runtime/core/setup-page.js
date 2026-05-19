'use strict';

const RUNTIME_VERSION = require('../package.json').version;

function setupPage(config) {
    const isConnected = !!config.token;
    const isLocalDev  = config.platformUrl.includes('127.0.0.1') || config.platformUrl.includes('localhost');

    return `<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Musoftware Runtime — Setup</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg:      #08080b;
            --surface: #0f0f14;
            --card:    #13131a;
            --border:  #1e1e2c;
            --text:    #e2e8f0;
            --muted:   #4a5568;
            --accent:  #6366f1;
            --green:   #22c55e;
            --red:     #ef4444;
            --amber:   #f59e0b;
        }
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            min-height:100vh; background:var(--bg); color:var(--text);
            font-family:'Inter',system-ui,sans-serif;
            display:flex; flex-direction:column; align-items:center;
            justify-content:center; padding:2rem; gap:1.5rem;
        }
        .logo { font-size:1.1rem; font-weight:700; letter-spacing:-0.02em; color:var(--muted); }
        .logo em { color:var(--text); font-style:normal; }
        .card {
            background:var(--card); border:1px solid var(--border);
            border-radius:16px; padding:2rem; width:100%; max-width:460px;
        }
        .card-title { font-size:1rem; font-weight:600; margin-bottom:1.5rem; }
        .status-bar {
            display:flex; align-items:center; gap:.5rem;
            padding:.65rem .9rem; border-radius:10px; font-size:.82rem;
            margin-bottom:1.25rem; font-weight:500;
        }
        .status-bar.ok   { background:rgba(34,197,94,.08); border:1px solid rgba(34,197,94,.2); color:var(--green); }
        .status-bar.err  { background:rgba(239,68,68,.06); border:1px solid rgba(239,68,68,.15); color:var(--red); }
        .status-bar.dev  { background:rgba(99,102,241,.08); border:1px solid rgba(99,102,241,.2); color:var(--accent); }
        .status-bar.wait { background:rgba(245,158,11,.07); border:1px solid rgba(245,158,11,.2); color:var(--amber); }
        .dot { width:7px;height:7px;border-radius:50%;flex-shrink:0; animation:none; }
        .dot.green  { background:var(--green);  box-shadow:0 0 6px var(--green); }
        .dot.red    { background:var(--red);     box-shadow:0 0 6px var(--red); }
        .dot.purple { background:var(--accent);  box-shadow:0 0 6px var(--accent); animation:pulse 2s infinite; }
        .dot.amber  { background:var(--amber);   box-shadow:0 0 6px var(--amber); }
        @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }

        /* Login button — primary action */
        .btn-login {
            width:100%; padding:.8rem; background:var(--accent);
            border:none; border-radius:12px; color:#fff; font-size:.9rem;
            font-weight:600; cursor:pointer; display:flex; align-items:center;
            justify-content:center; gap:.5rem;
            transition:opacity .15s, transform .1s;
        }
        .btn-login:hover { opacity:.88; }
        .btn-login:active { transform:scale(.98); }
        .btn-login:disabled { opacity:.4; cursor:not-allowed; }
        .btn-login .icon { font-size:1rem; }

        /* Secondary / disconnect button */
        .btn-secondary {
            width:100%; padding:.65rem; background:transparent;
            border:1px solid var(--border); border-radius:10px;
            color:var(--muted); font-size:.82rem; font-weight:500;
            cursor:pointer; transition:background .15s, color .15s;
            margin-top:.6rem;
        }
        .btn-secondary:hover { background:var(--surface); color:var(--text); }

        .divider { border:none; border-top:1px solid var(--border); margin:1.25rem 0; }
        .hint { font-size:.72rem; color:var(--muted); margin-top:.5rem; line-height:1.6; }
        .hint a { color:var(--accent); text-decoration:none; }
        .hint a:hover { text-decoration:underline; }
        .msg { margin-top:.85rem; padding:.55rem .85rem; border-radius:8px; font-size:.82rem; }
        .msg.ok  { background:rgba(34,197,94,.08); color:var(--green); border:1px solid rgba(34,197,94,.2); }
        .msg.err { background:rgba(239,68,68,.06); color:var(--red); border:1px solid rgba(239,68,68,.15); }

        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:.5rem; }
        .info-item { background:var(--surface); border:1px solid var(--border); border-radius:8px; padding:.6rem .85rem; }
        .info-item .label { font-size:.65rem; color:var(--muted); text-transform:uppercase; letter-spacing:.06em; margin-bottom:.2rem; }
        .info-item .value { font-size:.82rem; font-weight:500; }
        .plugins-section h3 { font-size:.75rem; color:var(--muted); font-weight:500;
                              text-transform:uppercase; letter-spacing:.06em; margin-bottom:.7rem; }
        .plugin-row {
            display:flex; align-items:center; gap:.6rem; padding:.4rem 0;
            font-size:.82rem; border-bottom:1px solid var(--border);
        }
        .plugin-row:last-child { border-bottom:none; }
        .badge {
            padding:.12rem .45rem; border-radius:6px; font-size:.68rem; font-weight:600; flex-shrink:0;
        }
        .badge.node { background:rgba(99,102,241,.12); color:var(--accent); }
        .badge.py   { background:rgba(34,197,94,.1); color:var(--green); }
        .waiting-dots { display:inline-flex; gap:.2rem; }
        .waiting-dots span {
            width:4px;height:4px;border-radius:50%;background:var(--amber);
            animation:waiting .8s ease infinite;
        }
        .waiting-dots span:nth-child(2) { animation-delay:.15s; }
        .waiting-dots span:nth-child(3) { animation-delay:.3s; }
        @keyframes waiting { 0%,80%,100%{opacity:.3} 40%{opacity:1} }
    </style>
</head>
<body>

<div class="logo">mu<em>software</em> runtime</div>

<!-- CONNECTION CARD -->
<div class="card">
    <div class="card-title">Account Connection</div>

    <!-- Status bar -->
    <div class="status-bar ${isConnected ? (isLocalDev ? 'dev' : 'ok') : 'err'}" id="statusBar">
        <span class="dot ${isConnected ? (isLocalDev ? 'purple' : 'green') : 'red'}" id="statusDot"></span>
        <span id="statusText">
        ${isConnected
            ? (isLocalDev
                ? '🛠 Dev mode — connected via saved session'
                : `✓ Connected — syncing plugins automatically`)
            : 'Not connected — click below to log in'}
        </span>
    </div>

    <!-- Primary action -->
    ${isConnected ? `
    <p class="hint">
        You are connected as user <strong style="color:var(--text)">${config.userId}</strong>.
        Your subscribed plugins are syncing automatically.
    </p>
    <button class="btn-secondary" onclick="disconnect()">Disconnect account</button>
    ` : `
    <button class="btn-login" id="loginBtn" onclick="startLogin()">
        <span class="icon">🔐</span> Log in with musoftware.com
    </button>
    <div class="hint" id="waitMsg" style="display:none;text-align:center;margin-top:1rem">
        Browser opened — waiting for you to log in
        <span class="waiting-dots"><span></span><span></span><span></span></span>
    </div>
    <div class="hint" style="margin-top:.6rem;text-align:center">
        This opens <a href="${config.platformUrl}" target="_blank">${config.platformUrl}</a> in your browser.<br>
        No tokens or passwords needed here.
    </div>
    `}

    <div class="msg" id="msg" style="display:none"></div>
</div>

<!-- RUNTIME INFO CARD -->
<div class="card">
    <div class="card-title">Runtime Info</div>
    <div class="info-grid">
        <div class="info-item"><div class="label">Version</div><div class="value">v${RUNTIME_VERSION}</div></div>
        <div class="info-item"><div class="label">HTTP Port</div><div class="value">${config.port}</div></div>
        <div class="info-item"><div class="label">WS Port</div><div class="value">${config.wsPort}</div></div>
        <div class="info-item"><div class="label">Platform</div><div class="value" style="font-size:.7rem;word-break:break-all">${config.platformUrl}</div></div>
    </div>
    <hr class="divider">
    <div class="plugins-section">
        <h3>Installed Plugins</h3>
        <div id="plugins"><div style="color:var(--muted);font-size:.8rem">Loading...</div></div>
    </div>
</div>

<script>
const msg = document.getElementById('msg');
let polling = null;

async function startLogin() {
    const btn = document.getElementById('loginBtn');
    btn.disabled = true;
    showMsg('', '');
    try {
        const r = await fetch('/auth/start', { method: 'POST' });
        const d = await r.json();
        if (d.already_connected) {
            showMsg('Already connected! Reload to see your account.', 'ok');
            setTimeout(() => location.reload(), 1200);
            return;
        }
        // Browser was opened by the runtime — now poll for auth completion
        document.getElementById('waitMsg').style.display = 'block';
        btn.textContent = 'Waiting for login...';
        polling = setInterval(pollAuthStatus, 2000);
    } catch (e) {
        showMsg('Failed to start login: ' + e.message, 'err');
        btn.disabled = false;
    }
}

async function pollAuthStatus() {
    try {
        const r = await fetch('/auth/status');
        const d = await r.json();
        if (d.connected) {
            clearInterval(polling);
            showMsg('✓ Connected! Reloading...', 'ok');
            setTimeout(() => location.reload(), 1000);
        }
    } catch {}
}

async function disconnect() {
    if (!confirm('Disconnect this runtime from your account?')) return;
    try {
        await fetch('/auth/disconnect', { method: 'POST' });
        location.reload();
    } catch (e) {
        showMsg('Error: ' + e.message, 'err');
    }
}

function showMsg(text, type) {
    msg.textContent = text;
    msg.className = 'msg ' + type;
    msg.style.display = text ? 'block' : 'none';
}

// Load plugins
fetch('/plugins').then(r => r.json()).then(data => {
    const el = document.getElementById('plugins');
    const plugins = data.plugins || [];
    if (!plugins.length) {
        el.innerHTML = '<div style="color:var(--muted);font-size:.8rem">No plugins installed yet — connect your account to sync</div>';
        return;
    }
    el.innerHTML = plugins.map(p =>
        '<div class="plugin-row">' +
        '<span class="badge ' + (p.runtime === 'python' ? 'py' : 'node') + '">' +
            (p.runtime === 'python' ? 'PY' : 'JS') + '</span>' +
        '<span>' + p.name + '</span>' +
        '<span style="margin-left:auto;color:var(--muted);font-size:.72rem">v' + p.version + '</span>' +
        '</div>'
    ).join('');
}).catch(() => {});
</script>
</body>
</html>`;
}

module.exports = { setupPage };
