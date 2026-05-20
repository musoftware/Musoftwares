# Musoftware Platform — Plugin Development SDK

> **Target**: Developers creating plugins for the Musoftware Runtime Agent  
> **Runtime Version**: 1.0.0+  
> **Supported Runtimes**: Node.js 22+ | Python 3.8+

---

## 1. What Is a Plugin?

A plugin is a locally-executable tool that runs inside the Musoftware Runtime Agent on the user's machine. Plugins are:

- Installed automatically when a user subscribes to a Tool
- Executed via the local HTTP API (`POST /plugins/{slug}/run`)
- Monitored in real-time via WebSocket events
- License-verified against the Musoftware platform on each run

---

## 2. Plugin Structure

```
{tool-slug}/
  ├── manifest.json        ← REQUIRED: plugin identity + configuration
  ├── index.js             ← Entry point (nodejs runtime)
  │   OR main.py           ← Entry point (python runtime)
  ├── package.json         ← Optional: npm dependencies (auto-installed)
  ├── requirements.txt     ← Optional: pip dependencies (auto-installed)
  └── ... (any other files)
```

---

## 3. manifest.json — Full Reference

```json
{
  // ── REQUIRED FIELDS ──────────────────────────────────────────
  "id": "my-tool",
  // Unique identifier. Rules:
  //   - Lowercase alphanumeric + hyphens only
  //   - 1–64 characters
  //   - Must be globally unique across all plugins

  "name": "My Tool",
  // Human-readable display name

  "version": "1.0.0",
  // Semantic versioning (semver) required. Examples: "1.0.0", "2.1.3-beta"

  "runtime": "nodejs",
  // Execution environment. One of: "nodejs" | "python"

  "entry": "index.js",
  // Relative path to the entry file from plugin root
  // nodejs: path to .js file that module.exports the plugin
  // python: path to .py file (run as subprocess)

  "tool_slug": "my-tool",
  // Must match the 'slug' field of the Tool record on the Musoftware platform
  // Used for license verification and plugin lookup

  // ── OPTIONAL FIELDS ──────────────────────────────────────────
  "description": "Brief description of what this plugin does",

  "autoStart": false,
  // If true: plugin is started automatically when runtime boots
  // Useful for long-running services (e.g., WhatsApp session pool)

  "requires_runtime": ">=1.0.0",
  // Semver range that the runtime version must satisfy
  // Plugin rejected if runtime doesn't meet this requirement

  "permissions": ["browser", "network", "filesystem"],
  // Declared capabilities this plugin needs:
  //   "browser"    → Playwright browser automation
  //   "network"    → Outbound HTTP/HTTPS requests
  //   "filesystem" → File read/write access
  //   "database"   → Database access
  //   "ipc"        → Inter-process communication

  "capabilities": ["scraping", "messaging"],
  // What this plugin does (for categorization):
  //   "scraping" | "messaging" | "analytics" | "ai" | "automation" | "storage"

  "trustLevel": "trusted",
  // Security trust level:
  //   "trusted"    → First-party Musoftware plugins (highest access)
  //   "community"  → Vetted third-party plugins
  //   "unverified" → User-installed plugins (most restricted)

  "timeoutSeconds": 180,
  // Maximum execution time per task run (10–3600 seconds)
  // Task is killed if exceeded. Default: 180

  "maxMemoryMb": 512,
  // Maximum memory usage (64–8192 MB). Default: 512

  "maxConcurrentTasks": 3,
  // Maximum parallel task executions (1–20). Default: 3

  "requires_plugins": ["shared-lib"],
  // Plugin IDs that must be loaded before this plugin
  // Enforced by DependencyResolver during boot

  "optional_plugins": ["analytics-plugin"],
  // Plugin IDs that enhance this plugin if present (not required)

  "fsAllowlist": ["./", "/tmp"]
  // Filesystem paths this plugin is allowed to access
  // Default: ["./"] (plugin directory only)
}
```

---

## 4. Node.js Plugin API

### Entry Module Structure

```javascript
// index.js — Node.js plugin entry

'use strict';

module.exports = {
  // ── REQUIRED ───────────────────────────────────────────────────
  
  /**
   * Called when a user runs this plugin.
   * 
   * @param {object} params - Parameters passed from the platform frontend
   * @param {object} context - Runtime-provided context
   * @param {string} context.taskId - Unique task identifier
   * @param {function} context.broadcast - Send WS event to connected clients
   * @param {object} context.logger - Winston logger instance
   * @param {object} context.storage - Runtime SQLite storage
   * @param {AbortSignal} context.signal - Abort signal for cancellation
   * 
   * @returns {Promise<any>} - Task result (stored in SQLite, returned via /tasks/{id})
   */
  run: async (params, context) => {
    const { taskId, broadcast, logger, storage, signal } = context;
    
    logger.info(`[my-tool] Starting task ${taskId}`);
    broadcast('task.progress', { taskId, progress: 0, message: 'Starting...' });
    
    // ... do work ...
    
    broadcast('task.progress', { taskId, progress: 100, message: 'Done!' });
    
    return {
      success: true,
      result: { /* your output data */ }
    };
  },

  // ── OPTIONAL LIFECYCLE HOOKS ──────────────────────────────────

  /**
   * Called once when plugin is first loaded by PluginLoader.
   * Use for one-time setup.
   */
  onLoad: (manifest, runtimeContext) => {
    // runtimeContext.runtimeVersion — current runtime version
    console.log(`[${manifest.id}] Plugin loaded`);
  },

  /**
   * Called after plugin routes are successfully registered.
   * Use to start background services.
   */
  onReady: (manifest, runtimeContext) => {
    // Start background workers, WebSocket subscribers, etc.
  },

  /**
   * Called on graceful shutdown.
   */
  onUnload: () => {
    // Cleanup: close connections, flush data
  },

  // ── OPTIONAL: Custom HTTP Routes ─────────────────────────────

  /**
   * Register custom HTTP routes for this plugin.
   * Routes are mounted at: /plugins/{slug}/...
   * 
   * @param {express.Router} router - Express router
   */
  routes: (router) => {
    router.get('/status', (req, res) => {
      res.json({ status: 'ready', accounts: [] });
    });
    
    router.post('/action', async (req, res) => {
      // handle custom action
      res.json({ ok: true });
    });
  }
};
```

---

## 5. Python Plugin API

Python plugins run as **subprocesses**, communicating via stdin/stdout JSON.

### Entry File Structure

```python
# main.py — Python plugin entry

import sys
import json

def run(params: dict, context: dict) -> dict:
    """
    Main execution function.
    
    params: dict — parameters from the platform
    context: dict — { taskId, logger, ... }
    
    Returns dict result (JSON-serializable)
    """
    task_id = context.get('taskId')
    
    # Send progress updates via stdout
    progress_event = {
        'event': 'task.progress',
        'data': { 'taskId': task_id, 'progress': 50, 'message': 'Processing...' }
    }
    print(json.dumps(progress_event), flush=True)
    
    # ... do work ...
    
    return {
        'success': True,
        'result': { 'processed': True }
    }


if __name__ == '__main__':
    # Runtime passes params via stdin as JSON
    input_data = json.loads(sys.stdin.read())
    params = input_data.get('params', {})
    context = input_data.get('context', {})
    
    result = run(params, context)
    print(json.dumps({ 'result': result }))
```

### Python Plugin Configuration
```
# requirements.txt — auto-installed on first use
requests>=2.28.0
playwright>=1.60.0
```

---

## 6. Runtime Context API

### `context.broadcast(event, data)`
Send a WebSocket event to all connected platform clients.

```javascript
context.broadcast('my-tool.scraped', {
  taskId: context.taskId,
  url: 'https://example.com',
  data: { title: '...', price: '...' }
});
```

### `context.logger`
Winston logger instance — logs appear in runtime log files.

```javascript
context.logger.info('[my-tool] Processing item 1 of 100');
context.logger.warn('[my-tool] Rate limit detected, slowing down');
context.logger.error('[my-tool] Network error: ' + err.message);
```

### `context.storage`
Runtime SQLite storage for persistent plugin data.

```javascript
// Store task result
context.storage.saveTask(taskId, { result: myResult });

// Store plugin-specific data (custom methods may vary)
// Use better-sqlite3 directly for custom tables:
const db = context.storage.db;
db.exec(`CREATE TABLE IF NOT EXISTS my_data (id INTEGER PRIMARY KEY, value TEXT)`);
const stmt = db.prepare('INSERT INTO my_data VALUES (?, ?)');
stmt.run(null, JSON.stringify(myData));
```

### `context.signal` (AbortSignal)
Cancellation support for graceful task termination.

```javascript
// Check periodically for cancellation
for (const item of items) {
  if (context.signal.aborted) {
    throw new Error('Task cancelled');
  }
  await processItem(item);
}
```

---

## 7. WhatsApp Shared Library

Plugins that automate WhatsApp should use the shared library:

```javascript
// Access shared WhatsApp utilities (if installed alongside WA plugins)
const WaSessionPool = require('../_shared/wa-session-pool');
const AntiBanEngine = require('../_shared/anti-ban-engine');
const inboxStore = require('../_shared/wa-inbox-store');

module.exports = {
  run: async (params, context) => {
    const pool = WaSessionPool.getInstance({ broadcast: context.broadcast, log: context.logger });
    const antiBan = AntiBanEngine.getInstance({ broadcast: context.broadcast });
    
    // Get or create session for account
    const session = await pool.acquireByAccount(params.account_id, {
      proxy: params.proxy,
      headless: params.headless ?? true,
    });
    
    // Check anti-ban before sending
    const velocity = antiBan.getVelocityStats(params.account_id);
    if (velocity.riskScore > 0.8) {
      throw new Error('High ban risk detected — aborting');
    }
    
    // ... send messages using session.page (Playwright) ...
    
    // Release when done
    await pool.release(params.account_id);
  }
};
```

---

## 8. Plugin Lifecycle Events (WebSocket)

Plugins should emit standard events for UI integration:

```javascript
// Task started (runtime emits automatically)
broadcast('task.started', { taskId, pluginId: manifest.id, params });

// Custom progress events
broadcast('task.progress', { taskId, progress: 0-100, message: '...' });

// Custom domain events
broadcast('my-tool.item.found', { taskId, item: { ... } });
broadcast('my-tool.page.scraped', { taskId, url, count: 150 });

// Task result (runtime emits automatically on run() completion)
broadcast('task.completed', { taskId, result });

// Task error (runtime emits automatically on run() rejection)
broadcast('task.failed', { taskId, error: err.message });
```

---

## 9. Development & Testing

### Local Development Setup

```bash
# 1. Clone runtime repo
cd newmusoftwareTools/musoftware-runtime

# 2. Install dependencies
npm install

# 3. Copy .env.example → .env
# Set platformUrl=http://localhost:8000 for local platform

# 4. Create test plugin
mkdir -p plugins/my-test-plugin
# Create manifest.json + index.js

# 5. Start runtime in dev mode (hot reload)
npm run dev

# 6. Test via HTTP
curl http://127.0.0.1:18400/plugins
curl -X POST http://127.0.0.1:18400/plugins/my-test-plugin/run \
  -H "Content-Type: application/json" \
  -d '{"params": {"key": "value"}}'

# 7. Monitor via WebSocket
# Connect WS client to ws://127.0.0.1:18401/ws
```

### Testing Checklist
- [ ] manifest.json validates (no errors in runtime logs)
- [ ] Plugin appears in `GET /plugins`
- [ ] `POST /plugins/{slug}/run` returns taskId
- [ ] `GET /tasks/{taskId}` shows result
- [ ] WS events received during execution
- [ ] Cancellation via `POST /tasks/{taskId}/stop` works
- [ ] Plugin handles errors gracefully (no runtime crash)
- [ ] Memory usage stays within maxMemoryMb
- [ ] All cleanup runs in onUnload()

---

## 10. Packaging & Distribution

### Plugin Package Format
```
Plugins are distributed as ZIP files:
  my-tool.zip
    └── (plugin files without wrapping directory)
         ├── manifest.json
         ├── index.js
         └── package.json

IMPORTANT: Files must be at ZIP root, not inside a subfolder.
```

### Platform Registration
```
1. Admin creates Tool record in platform (/admin/tools)
2. Admin uploads version ZIP via /admin/tools/{tool}/upload-version
3. Platform stores download_url in tool_versions table
4. Runtime syncs via PluginSyncer → downloads + installs automatically
5. Users see tool in /tools listing
6. Users subscribe → license activated → tool runs on demand
```

### Build for Distribution
```javascript
// Ensure plugin ZIP structure:
// Plugin files at root level of ZIP
// No npm node_modules in ZIP (runtime auto-installs from package.json)

// Example: create zip excluding node_modules
// zip -r my-tool.zip . --exclude "node_modules/*" --exclude ".git/*"
```

---

## 11. Plugin Security Requirements

### What Plugins Must Not Do
- Access files outside `fsAllowlist` paths
- Open network connections to undeclared services
- Modify runtime configuration files
- Access other plugins' private data
- Spawn persistent background processes without `autoStart: true`

### What Trusted Plugins Can Do
- Access `plugins/_shared/` utilities (WA session pool, etc.)
- Use `browser` permission for Playwright automation
- Write to plugin-local directories
- Communicate back via broadcast events

### Isolation Model
```
nodejs plugins:
  ├── Loaded via require() in same process
  ├── Memory/concurrency limits enforced by Sandbox
  ├── NOT true process isolation (same Node.js process)
  └── Trust: rely on manifest permissions + SecurityManager

python plugins:
  ├── Run as child processes (subprocess)
  ├── Process isolation = OS-level separation
  ├── Communicate via stdin/stdout JSON
  └── Trust: inherits OS permissions of runtime process
```
