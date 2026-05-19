# Musoftware Node.js Agent

A general-purpose plugin host for Node.js-based automation tools.
Runs locally on the user's machine. The website at musoftware.com IS the UI — this agent just executes.

## Architecture

```
┌──────────────────────────────────┐
│      musoftware.com (website)    │  ← The UI
│      React/Inertia/Laravel       │
│                                  │
│  connects to ws://127.0.0.1:18400/ws
│  polls GET http://127.0.0.1:18400/status
└──────────────┬───────────────────┘
               │
               ▼
┌──────────────────────────────────┐
│     Node.js Agent (this)         │  ← Background service
│     http://127.0.0.1:18400       │
│                                  │
│  ┌─────────────────────────────┐ │
│  │   Plugin: WhatsApp Sender   │ │  ← Spawned as child_process
│  │   Plugin: TikTok Scraper    │ │
│  │   Plugin: ... (auto-synced) │ │
│  └─────────────────────────────┘ │
└──────────────────────────────────┘
```

## Quick Start

```bash
# Install agent dependencies
npm install

# Run in dev mode (auto-reload)
npm run dev

# Run in production
npm start

# Open setup page to configure token
open http://127.0.0.1:18400/setup
```

## API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET`  | `/status` | Agent health check (polled by website) |
| `GET`  | `/setup` | Local setup wizard (paste API token) |
| `GET`  | `/system` | Machine info (CPU, RAM, OS) |
| `GET`  | `/plugins` | List installed plugins |
| `POST` | `/plugins/:slug/run` | Start a plugin task |
| `POST` | `/tasks/:taskId/stop` | Stop a running task |
| `GET`  | `/tasks/:taskId/logs` | Get task log buffer |
| `POST` | `/auth` | Save auth token (called by website or setup) |
| `WS`   | `/ws` | Real-time events (task.log, task.progress, etc.) |

## WebSocket Events

**Agent → Browser:**
- `agent.ready` — initial greeting with plugin list
- `task.log` — `{ taskId, level, message }`
- `task.progress` — `{ taskId, pluginId, percent, message }`
- `task.done` — `{ taskId, pluginId, result }`
- `task.error` — `{ taskId, pluginId, error }`
- `plugin.installing` — auto-download in progress
- `plugin.installed` — auto-download complete

**Browser → Agent:**
- `ping` — keepalive
- `stop` — `{ taskId }`

## Plugin Structure

Each plugin lives in `plugins/<slug>/`:

```
plugins/
  whatsapp-sender/
    manifest.json     ← Plugin metadata
    package.json      ← npm dependencies (auto-installed)
    worker.js         ← Entry point (spawned as child_process)
  tiktok-scraper/
    manifest.json
    package.json
    worker.js
```

### manifest.json

```json
{
  "id": "whatsapp-sender",
  "name": "WhatsApp Bulk Sender",
  "version": "1.0.0",
  "entry": "worker.js",
  "tool_slug": "whatsapp-sender"
}
```

### Worker Protocol

Workers communicate via stdout (JSON lines):

```js
// Log
{ "type": "log", "level": "info", "message": "Starting..." }

// Progress (0-100)
{ "type": "progress", "percent": 42, "message": "Sending 5/12..." }

// Result (final)
{ "type": "result", "data": { "sent": 10, "failed": 2 } }

// Error (fatal)
{ "type": "error", "message": "Browser crashed" }
```

Workers receive parameters via `MUSOFTWARE_PARAMS` env variable (JSON string).

## Auto-Sync

After the user subscribes to a tool on musoftware.com, the agent automatically:
1. Polls `GET /api/tools/agent/plugins?agent=nodejs` every 2 minutes
2. Downloads the plugin ZIP from a signed URL
3. Extracts to `plugins/<slug>/`
4. Runs `npm install` if `package.json` exists
5. Plugin is immediately available

## Building for Distribution

```bash
# Build standalone exe (Windows)
npm run build:win

# Build for macOS
npm run build:mac

# Build for Linux
npm run build:linux
```

The output goes to `dist/musoftware-agent-node-{platform}`.
Upload to Laravel storage at `storage/app/agents/node/`.

## Config

Stored at `config/agent.json`:

```json
{
  "port": 18400,
  "platformUrl": "https://musoftware.com",
  "token": "user-auth-token",
  "userId": "123",
  "logLevel": "info"
}
```

Logs are written to `logs/agent.log` (5MB rotation, 3 files max).
