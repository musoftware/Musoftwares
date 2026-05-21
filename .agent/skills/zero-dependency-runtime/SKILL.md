---
name: Zero-Dependency End User Environment
description: The end user's machine has NOTHING installed. No Node.js, no Python, no Redis, no external databases. All runtime code must be fully self-contained and bundled.
---

# Zero-Dependency End User Environment

This skill defines the absolute, non-negotiable constraint that **end users have NOTHING installed** on their machines beyond a standard Windows/macOS installation. Every piece of infrastructure the runtime or any plugin needs must be bundled, embedded, or gracefully absent.

## Activation Conditions
This skill automatically applies when you are:
- Writing any runtime plugin code (`plugins/` directory).
- Modifying the runtime core (`core/` directory).
- Adding dependencies to `package.json` or `requirements.txt`.
- Writing any code that connects to external services (Redis, databases, APIs).
- Building installers, packagers, or distribution scripts.
- Designing architecture that involves infrastructure dependencies.

---

## 1. The Golden Rule: The User Installs ONE Thing

The user installs the **Musoftware Runtime installer** and NOTHING else. Ever.

This means the end user's machine has:
- ❌ No Node.js
- ❌ No Python
- ❌ No Redis
- ❌ No PostgreSQL / MySQL / MongoDB
- ❌ No Docker
- ❌ No npm / pip / brew / chocolatey
- ❌ No Git
- ❌ No Java / .NET SDK
- ❌ No build tools (gcc, make, Visual Studio Build Tools)
- ❌ No command-line proficiency

The runtime EXE is a **fully self-contained binary** (compiled via `pkg` or equivalent). It bundles its own Node.js runtime. Plugins are pre-packaged `.msp` archives with all dependencies baked in.

---

## 2. External Service Rules

### 2.1 NEVER Default to External Services
Code must NEVER assume an external service is available. Specifically:

```javascript
// ❌ ABSOLUTELY FORBIDDEN — assumes Redis is installed
const redisUrl = process.env.REDIS_URL || 'redis://127.0.0.1:6379';
this.redis = new Redis(redisUrl);

// ✅ CORRECT — only connect if explicitly configured
const redisUrl = process.env.REDIS_URL;
if (!redisUrl) {
    console.log('[Module] No REDIS_URL configured — running in local-only mode.');
    this.redis = null;
    return;
}
```

### 2.2 External Services Are OPT-IN, Not OPT-OUT
- Redis, PostgreSQL, RabbitMQ, Elasticsearch — these are **enterprise add-ons** that users explicitly configure via environment variables.
- The system MUST work perfectly without them.
- When absent, the system runs in **local-only mode** using embedded alternatives (SQLite, in-process queues, in-memory caches).

### 2.3 Graceful Degradation Hierarchy
Every feature that could use an external service must follow this cascade:

| Feature          | Enterprise (Configured)   | Default (Nothing Installed) |
|------------------|---------------------------|-----------------------------|
| Data Storage     | PostgreSQL / MySQL        | SQLite (embedded)           |
| Caching          | Redis                    | In-memory Map / LRU         |
| Message Queues   | Redis / RabbitMQ          | In-process queue / SQLite   |
| Distributed Lock | Redis SETNX              | Always grant (single node)  |
| Pub/Sub Events   | Redis Pub/Sub            | In-process EventEmitter     |
| Search           | Elasticsearch            | SQLite FTS5                 |
| Session Store    | Redis                    | File-based / SQLite         |

---

## 3. Database Rules

### 3.1 SQLite Is The Only Default Database
- All plugins MUST use **SQLite** for local persistent storage.
- SQLite requires zero installation — it's a single file embedded in the Node.js binary via `better-sqlite3` or `sqlite3`.
- The database file lives in the plugin's data directory.

### 3.2 No External Database Assumptions
Never write code that assumes MySQL, PostgreSQL, MongoDB, or any external database is available on the end user's machine. The Laravel backend on the cloud uses its own database — that's the cloud's concern, not the runtime's.

---

## 4. Plugin Dependency Rules

### 4.1 All Dependencies Must Be Bundled
When building `.msp` packages for distribution:
- All `node_modules` dependencies must be pre-installed and included in the package.
- Native modules (`.node` files) must be pre-compiled for each target platform.
- Python dependencies must be vendored (not installed via pip at runtime).

### 4.2 No Runtime `npm install` or `pip install`
The end user cannot run `npm install` because they don't have npm. The end user cannot run `pip install` because they don't have Python. Plugin packages must be complete and self-contained.

Exception: Legacy ZIP-format plugins in development mode may run `npm install` since developers DO have Node.js. But production `.msp` packages must never require this.

### 4.3 Bundled Runtime Dependencies
The runtime core bundles common dependencies (the `BUNDLED_DEPS` set in `plugin-loader.js`). Any dependency that multiple plugins need should be added here rather than duplicated across plugin packages.

When adding a new commonly-used dependency:
1. Add it to the runtime's `package.json`
2. Add it to the `BUNDLED_DEPS` set in `core/plugin-loader.js`
3. Add it to the `BUNDLED_DEPS` set in `core/runtime/bootstrap.js`

---

## 5. Binary & Native Module Rules

### 5.1 Browser Automation
- Playwright/Puppeteer browsers must be downloaded on first use by the runtime itself — never assume they're pre-installed.
- Store downloaded browsers in the runtime's own data directory, not system-wide.

### 5.2 Native Modules
- Native modules (`better-sqlite3`, `sqlite3`, etc.) must have pre-built binaries for the target platform included in the distribution.
- Never require the end user to have Visual Studio Build Tools, Python (for node-gyp), or any compilation toolchain.

---

## 6. Error Handling for Missing Services

When an optional external service is unavailable:

```javascript
// ✅ CORRECT — Clean single-line log, graceful fallback
console.log('[Module] No REDIS_URL configured — running in local-only mode (SQLite).');

// ❌ WRONG — Scary error, infinite retry spam
console.error('[Module] Redis connection error: connect ECONNREFUSED 127.0.0.1:6379');
// (repeating every 2 seconds forever)
```

Rules:
- Log ONCE at `info` or `warn` level.
- Never spam errors for expected conditions (no Redis = expected on consumer machines).
- Never use `console.error` for a missing optional service.
- Always use `lazyConnect: true` for ioredis when Redis is opt-in (prevents unhandled error events).

---

## 7. Environment Variable Convention

Optional infrastructure must be configured via clearly named environment variables:

| Variable          | Purpose                         | Required? |
|-------------------|---------------------------------|-----------|
| `REDIS_URL`       | Redis connection for scaling    | No        |
| `DATABASE_URL`    | External DB (enterprise)        | No        |
| `ELASTICSEARCH_URL` | Search engine (enterprise)    | No        |

If the variable is not set, the feature runs in local/embedded mode. No fallback to `localhost:defaultPort`.

---

## Summary Checklist

Before merging any code, verify:
- [ ] Does this code work on a fresh Windows PC with only the Musoftware installer run?
- [ ] Are there any `|| 'localhost:PORT'` or `|| '127.0.0.1:PORT'` fallbacks? (Remove them)
- [ ] Does any code call `npm install`, `pip install`, `apt-get`, or any package manager at user runtime?
- [ ] Are all native modules pre-compiled for the target platform?
- [ ] Do error messages for missing optional services log once and degrade gracefully?
- [ ] Is SQLite the default and only required database?
- [ ] Can a non-technical user operate this without ever opening a terminal?
