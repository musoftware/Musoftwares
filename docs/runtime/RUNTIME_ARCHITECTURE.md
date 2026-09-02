# Musoftware Runtime Agent — Architecture & Protocol Documentation

> **Component**: musoftware-runtime (Node.js 22)  
> **Location**: `newmusoftwareTools/musoftware-runtime/`  
> **Build Output**: Cross-platform compiled binary via `pkg`  
> **Entry Point**: `core/index.js`

---

## 1. Runtime Overview

The Musoftware Runtime Agent is a **locally-installed background service** that:

1. Runs tool/plugin tasks on the user's local machine
2. Communicates with the Musoftware Platform via authenticated HTTP
3. Exposes a local HTTP API (port 18400) and WebSocket server (port 18401)
4. Manages plugin lifecycle, process orchestration, and license verification
5. Handles WhatsApp automation via Playwright browser sessions

```
User's Machine
  └── musoftware-runtime.exe (compiled)
        │
        ├── HTTP Server  → http://127.0.0.1:18400
        │    └── REST API (plugins, tasks, auth, admin)
        │
        ├── WS Server    → ws://127.0.0.1:18401/ws
        │    └── Real-time events to browser/platform
        │
        └── Outbound HTTP → https://tools.musoftwares.com (Platform)
              └── License verification, plugin sync, auth callback
```

---

## 2. Boot Sequence

```
main()
  │
  ├── 1. loadConfig()          → read core/config/runtime.json
  ├── 2. createLogger()        → winston logger
  ├── 3. registerProtocol()    → Windows registry: musoftware:// URL scheme
  │
  ├── 4. Storage.init()        → initialize SQLite (better-sqlite3)
  │
  ├── 5. PluginLifecycle()     → state machine registry
  ├── 6. PluginLoader.loadAll() → scan plugins dir, validate + load all plugins
  │    ├── Phase 1: ManifestValidator (schema check)
  │    ├── Phase 2: DependencyResolver (topological sort)
  │    └── Phase 3: Load in order → require() each plugin module
  │
  ├── 7. TaskRunner.init()     → job queue (in-memory)
  │
  ├── 8. WebSocket Server      → ws://127.0.0.1:18401/ws
  ├── 9. EventKernel()         → broadcast hub
  ├── 10. CrashRecovery()      → quarantine system
  │
  ├── 11. ProcessMonitor()     → health watchdog
  ├── 12. DeviceAuth()         → auth state manager
  │
  ├── 13. Express HTTP App     → http://127.0.0.1:18400
  │    ├── helmet + CORS (musoftware.com + localhost allowed)
  │    ├── SecurityManager.adminAuth() middleware
  │    ├── TaskRegistry (cleanup orphans)
  │    ├── Sandbox (start isolation context)
  │    ├── RouteRegistry (mount plugin routes)
  │    ├── RuntimeHealth (mount /health endpoints)
  │    └── Diagnostics (mount /diagnostics endpoints)
  │
  ├── 14. ProcessMonitor.start()        → begin watchdog loop
  ├── 15. ProcessMonitor.bootAutoStartPlugins() → launch autoStart plugins
  ├── 16. UpdateChecker.start()         → periodic update polls
  │
  ├── 17. If config.token:
  │    └── PluginSyncer.start()  → sync plugins from platform
  │   Else:
  │    └── broadcast 'auth.required' + open setup page
  │
  └── 18. diagnostics.printStartupReport()
```

---

## 3. Plugin System

### 3.1 Plugin Directory Structure

```
plugins/
  └── {tool-slug}/
       ├── manifest.json      (REQUIRED — schema validated)
       ├── index.js           (entry point for nodejs plugins)
       │   OR
       ├── main.py            (entry point for python plugins)
       ├── package.json       (optional — auto npm install)
       └── requirements.txt   (optional — auto pip install)
```

### 3.2 manifest.json Schema

```json
{
  "id": "my-plugin",              // REQUIRED: unique, lowercase, hyphens only
  "name": "My Plugin",            // REQUIRED: human-readable
  "version": "1.0.0",             // REQUIRED: semver
  "runtime": "nodejs",            // REQUIRED: "nodejs" | "python"
  "entry": "index.js",            // REQUIRED: relative path to entry file
  "tool_slug": "my-plugin",       // REQUIRED: must match platform tool slug

  // Optional fields:
  "description": "...",
  "autoStart": false,             // start on runtime boot
  "requires_runtime": ">=1.0.0", // semver range check against runtime version
  "permissions": ["browser", "network", "filesystem", "database", "ipc"],
  "capabilities": ["scraping", "messaging", "analytics", "ai", "automation", "storage"],
  "trustLevel": "trusted",        // "trusted" | "community" | "unverified"
  "timeoutSeconds": 180,          // 10–3600
  "maxMemoryMb": 512,             // 64–8192
  "maxConcurrentTasks": 3,        // 1–20
  "requires_plugins": [],         // plugin IDs this depends on
  "optional_plugins": [],         // soft dependencies
  "fsAllowlist": ["./"]           // filesystem access allowlist
}
```

### 3.3 Plugin Lifecycle State Machine

```
registered → validated → loaded → initialized → running → stopped
                                                        ↓
                                               quarantined (after 3 crashes)
```

State transitions:
- `PluginLoader.loadAll()` → transitions: `validated` → `loaded`
- `RouteRegistry.mount()` success → `initialized`
- `TaskRegistry.run()` triggers task → `running`
- `CrashRecovery.handleCrash()` → potentially `quarantined`

### 3.4 Plugin Entry Module API (Node.js)

A nodejs plugin must export:

```javascript
module.exports = {
  // REQUIRED: called when a task is run
  run: async (params, context) => {
    // context: { taskId, broadcast, logger, storage }
    return { result: 'done' };
  },

  // OPTIONAL lifecycle hooks
  onLoad: (manifest, runtimeContext) => {},
  onReady: (manifest, runtimeContext) => {},
  onUnload: () => {},

  // OPTIONAL: custom HTTP routes
  routes: (router) => {
    router.get('/my-endpoint', (req, res) => { ... });
  }
};
```

### 3.5 Plugin Auto-Install Flow

```
POST /plugins/:slug/update
  │
  ├── Delete existing plugin directory
  ├── Download .zip from downloadUrl
  ├── Extract to plugins/{slug}/
  ├── Read + validate manifest.json
  ├── If nodejs: npm install --production
  ├── If python: pip install -r requirements.txt
  ├── Register in PluginLoader
  └── Broadcast 'plugin.updated'
```

---

## 4. Runtime HTTP API Reference

### Status & Info
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /status | None | Runtime status, plugins, active tasks |
| GET | /system | None | OS info, CPU, memory |
| GET | /version | None | Runtime version, channel |
| GET | /setup | None | HTML setup page |

### Plugin Management
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /plugins | Admin | List all loaded plugins |
| POST | /plugins/reload | Admin | Reload all plugins from disk |
| POST | /plugins/:slug/run | Admin | Run a plugin task |
| POST | /plugins/:slug/update | Admin | Download + install plugin update |
| POST | /plugins/sync | Admin | Force sync from platform |

### Task Management
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /tasks | Admin | Recent tasks + registry summary |
| GET | /tasks/:taskId | Admin | Task details + logs + result |
| POST | /tasks/:taskId/stop | Admin | Cancel a running task |

### Auth Flow
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /auth/status | None | Is runtime connected to platform? |
| POST | /auth/start | None | Begin device login (opens browser) |
| POST | /auth/callback | Platform origin only | Receive token from platform |
| POST | /auth/disconnect | None | Clear token + disconnect |

### Admin
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | /admin/plugins/:id/reset | Admin | Reset quarantined plugin |
| GET | /admin/audit | Admin | Last 100 security audit events |

### Health & Diagnostics
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /health | None | Overall health status |
| GET | /health/plugins | None | Per-plugin health |
| GET | /health/runtime | None | Runtime subsystem health |
| GET | /metrics | None | Numeric metrics |
| GET | /routes | None | Registered plugin routes |
| GET | /diagnostics | Admin | Full diagnostic snapshot |
| POST | /events/replay | Admin | Replay last N events |

### Update
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| POST | /update/check | Admin | Check for runtime updates |

### WhatsApp Platform
| Method | Path | Auth | Description |
|--------|------|------|-------------|
| GET | /whatsapp/sessions | None | List all WA sessions |
| POST | /whatsapp/sessions/:accountId/connect | None | Connect a WA account |
| POST | /whatsapp/sessions/:accountId/disconnect | None | Disconnect session |
| GET | /whatsapp/sessions/:accountId/health | None | Session health |
| GET | /whatsapp/inbox | None | List conversations |
| GET | /whatsapp/inbox/:phone | None | Get conversation + history |
| POST | /whatsapp/inbox/:phone/reply | None | Send reply |
| PATCH | /whatsapp/inbox/:phone | None | Assign/label/resolve conversation |
| GET | /whatsapp/contacts | None | List contacts with filters |
| GET | /whatsapp/contacts/:phone | None | Get contact details |
| GET | /whatsapp/quality | None | Account quality + anti-ban stats |

---

## 5. WebSocket Protocol

### Connection
```
ws://127.0.0.1:18401/ws
```

### Authentication
CORS check on `origin` header — must match `*.musoftware.com` or `localhost/*`.

### On Connect (Server → Client)
```json
{
  "event": "runtime.ready",
  "data": {
    "version": "1.0.0",
    "plugins": [{ "id": "...", "slug": "...", "name": "...", "version": "...", "runtime": "..." }],
    "activeTasks": [...]
  },
  "ts": 1716200000000
}
```

### Client → Server Messages
```json
{ "type": "ping" }
{ "type": "stop", "payload": { "taskId": "..." } }
```

### Server → Client Events
| Event | Description |
|-------|-------------|
| `runtime.ready` | Initial state on connect |
| `pong` | Response to ping |
| `plugins.reloaded` | After /plugins/reload |
| `plugin.updated` | After plugin update |
| `plugin.installed` | After auto-install |
| `plugin.installing` | During install |
| `auth.connected` | Device auth success |
| `auth.disconnected` | Disconnect called |
| `auth.required` | Runtime not logged in |
| `wa.event.*` | WhatsApp passthrough events |
| `task.*` | Task lifecycle events |

---

## 6. Security Architecture

### CORS Policy
```javascript
ALLOWED_ORIGIN = /^https?:\/\/(.*\.)?musoftware\.com$|^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/
```

### Admin Auth Middleware
`SecurityManager.adminAuth()` — protects admin endpoints. Implementation in `core/runtime/SecurityManager.js`.

### License Verification Flow
```
Plugin run request received
  │
  ├── Check quarantine → reject if quarantined
  ├── Check local license cache (SQLite)
  │   ├── 'active' → proceed
  │   ├── 'expired' → reject
  │   └── 'cache_stale' or not found:
  │       ├── If no token → start device login
  │       └── If token → verify with platform:
  │           GET /api/tools/agent/plugins?slug={slug}
  │           ├── Valid license → upsert cache → proceed
  │           └── Invalid/expired → revoke cache → reject with buy_url
  │
  └── Run task
```

### Trust Levels
| Level | Description |
|-------|-------------|
| `trusted` | First-party Musoftware plugins |
| `community` | Vetted community plugins |
| `unverified` | User-installed, unknown origin |

---

## 7. Crash Recovery System

```
Plugin crashes during task execution
  │
  ├── CrashRecovery.handleCrash(pluginId, error)
  │   ├── Increment crash counter
  │   ├── If crashes >= 3 in 5 minutes → QUARANTINE
  │   │   ├── Set quarantine flag
  │   │   ├── Transition lifecycle → quarantined
  │   │   └── Broadcast 'plugin.quarantined'
  │   └── Otherwise → allow retry
  │
  └── Reset: POST /admin/plugins/:id/reset
```

---

## 8. Build & Distribution

```bash
# Development
npm run dev        # node --watch core/index.js

# Production builds
npm run build:win   # → dist/musoftware-runtime-win.exe
npm run build:mac   # → dist/musoftware-runtime-mac
npm run build:linux # → dist/musoftware-runtime-linux

# Uses: pkg (Node.js packager)
# Bundles: core/**, config/**
# Targets: node22-{win,macos,linux}-x64
```

### Update Distribution
- Runtime polls `GET /api/runtime/version` (platform API)
- Response: `{ version, minimum_supported, channel, downloads, changelog }`
- Downloads served from `public/downloads/runtime/latest.json`

---

## 9. WhatsApp Engine Architecture

```
WaSessionPool (singleton)
  │
  ├── Playwright browser sessions (headful or headless)
  ├── Per-account session state: connecting/connected/disconnected/error
  ├── Health monitor (periodic ping)
  └── acquireByAccount() / release()

AntiBanEngine (singleton)
  │
  ├── Velocity tracking per account
  ├── Rate limiting enforcement
  ├── Ban risk scoring
  └── getVelocityStats(accountId)

WaInboxStore (in-memory + SQLite)
  ├── listConversations() — filterable by accountId, status
  ├── getConversation() + getHistory()
  ├── markRead() / assignConversation() / resolveConversation()
  └── listContacts() — filterable by leadStage, language
```

**Location**: `plugins/_shared/` (shared plugin library)

---

## 10. Configuration

```javascript
// core/config/runtime.json (auto-created)
{
  "port": 18400,           // HTTP API port
  "wsPort": 18401,         // WebSocket port
  "platformUrl": "https://musoftware.com",
  "pluginsDir": "./plugins",
  "token": null,           // set after device auth
  "userId": null,          // set after device auth
  "updateChannel": "stable",
  "pythonBin": "python"   // python executable path
}
```
