# Musoftware Platform — System Architecture Intelligence

> **Classification**: Engineering Blueprint | Living Document  
> **Last Reverse-Engineered**: 2026-05-20  
> **Architecture Type**: Modular Monolith (PHP/Laravel) + Standalone Runtime Agent (Node.js)

---

## 1. Platform Overview

Musoftware is a **multi-module iSAAS (intelligent SaaS) platform** with a deeply integrated local Runtime Agent. It operates as two distinct but tightly coupled systems:

| System | Technology | Role |
|--------|-----------|------|
| **Platform Web App** | Laravel 12 + React (Inertia.js) | Central SaaS hub, ERP, Marketplace, Freelance, billing |
| **Runtime Agent** | Node.js 22 (Electron-less desktop agent) | Local tool execution, plugin orchestration, WhatsApp automation |

---

## 2. Top-Level Architecture

```
┌──────────────────────────────────────────────────────────────────────┐
│                    MUSOFTWARE PLATFORM (Laravel 12)                  │
│                                                                      │
│  ┌───────────┐  ┌───────────┐  ┌────────────┐  ┌───────────────┐   │
│  │ Core      │  │ ERP       │  │ Freelance  │  │ Marketplace   │   │
│  │ Module    │  │ Module    │  │ Module     │  │ Module        │   │
│  └───────────┘  └───────────┘  └────────────┘  └───────────────┘   │
│  ┌───────────┐  ┌───────────┐  ┌────────────┐  ┌───────────────┐   │
│  │ Tools     │  │Intelligence│  │ Booking    │  │ Shared        │   │
│  │ Module    │  │ Module    │  │ Module     │  │ Module        │   │
│  └───────────┘  └───────────┘  └────────────┘  └───────────────┘   │
│                                                                      │
│  Frontend: React + Inertia.js + TailwindCSS v4                      │
│  Auth: Laravel Sanctum + Spatie Permissions                         │
│  Queue: Laravel Queue (database driver)                              │
│  Search: MeiliSearch + Laravel Scout                                 │
│  Storage: AWS S3 + Local (Flysystem)                                │
│  Payment: Kashier Payment Gateway                                    │
└────────────────────────────┬─────────────────────────────────────────┘
                             │  HTTP REST + WebSocket
                             │  Auth: Device token (Sanctum)
                             ▼
┌──────────────────────────────────────────────────────────────────────┐
│              MUSOFTWARE RUNTIME AGENT (Node.js 22)                   │
│                                                                      │
│  HTTP API: http://127.0.0.1:18400                                    │
│  WS Server: ws://127.0.0.1:18401/ws                                  │
│                                                                      │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐   │
│  │PluginLoader  │  │ TaskRunner   │  │ ProcessMonitor            │   │
│  │ManifestValid │  │ TaskRegistry │  │ CrashRecovery             │   │
│  │DepResolver   │  │ Sandbox      │  │ EventKernel               │   │
│  └──────────────┘  └──────────────┘  └──────────────────────────┘   │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────────────────┐   │
│  │ DeviceAuth   │  │ PluginSyncer │  │ RuntimeHealth             │   │
│  │ SecurityMgr  │  │ UpdateChecker│  │ Diagnostics               │   │
│  └──────────────┘  └──────────────┘  └──────────────────────────┘   │
│                                                                      │
│  Plugin Runtimes: nodejs | python                                    │
│  WhatsApp Engine: Playwright + AntiBan + Session Pool               │
│  Storage: better-sqlite3 (local task/license DB)                    │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 3. Architecture Style: Modular Monolith

### Platform (PHP)
- **NOT microservices** — single deployable Laravel application
- Modules are **code-level** boundaries using `nwidart/laravel-modules`
- All modules share the same database, queue, and process
- Cross-module communication via PHP imports (namespace crossing)
- Module isolation enforced by convention, not infrastructure

### Runtime Agent (Node.js)
- **Standalone process** — ships as a compiled `.exe` (via `pkg`)
- Communicates with the Platform via authenticated HTTP (Bearer token)
- Runs entirely on user's local machine (localhost)
- Plugins run as child processes OR as require()'d modules
- Custom `musoftware://` URL protocol registration on Windows

---

## 4. Module Dependency Graph

```
app/ (Core Application)
 ├── depends on → Modules/Core  (wallets, messaging, auth)
 ├── depends on → Modules/ERP   (clients, invoices, tasks)
 ├── depends on → Modules/Freelance (jobs, contracts, points)
 ├── depends on → Modules/Marketplace (services, orders)
 ├── depends on → Modules/Tools (tool catalog, licenses)
 ├── depends on → Modules/Intelligence (ad intel, competitor tracking)
 └── depends on → Modules/Booking (event types, availability)

Modules/ERP
 ├── depends on → Modules/Core (wallets, wallet transactions)
 └── depends on → App/Models/User (tenant ownership)

Modules/Freelance
 ├── depends on → Modules/Core (wallet, points)
 └── depends on → App/Models/User

Modules/Marketplace
 ├── depends on → Modules/Core (wallets, messaging)
 └── depends on → App/Models/User

Modules/Booking
 └── depends on → App/Models/User (owner)

Modules/Intelligence
 └── depends on → App/Models/User (tenant scoping)

Modules/Shared
 └── Base models, shared utilities (no cross-dependencies)

Modules/Tools
 └── depends on → App/Models/User (licensing)
```

---

## 5. Request Lifecycle (Web)

```
Browser Request
      │
      ▼
Laravel HTTP Server (php artisan serve / nginx)
      │
      ▼
Global Middleware Stack:
  - EncryptCookies
  - VerifyCsrfToken
  - StartSession
  - ShareErrorsFromSession
  - SubstituteBindings
      │
      ▼
Route-level Middleware:
  - auth (Sanctum session)
  - verified (email verification)
  - onboarding (checks onboarding_completed flag)
  - subscription:erp (checks UserSubscription for module)
  - role:admin (Spatie Permission)
      │
      ▼
Controller → Service → Model → Database
      │
      ▼
Inertia::render() → React Component (SSR-less SPA)
      │
      ▼
Vite-served React Bundle (in dev) / compiled assets (prod)
```

---

## 6. Multi-Tenancy Architecture

The platform implements **soft multi-tenancy**:

- Each user who subscribes to ERP becomes a **Tenant**
- A `Tenant` record is created post-onboarding (`tenants` table)
- All ERP data is scoped by `tenant_id`
- `TenantModel` base class auto-applies `where('tenant_id', auth()->id())`
- Clients of a Tenant are stored as `TenantClient` (separate from platform `User`)
- Admin can **impersonate** any user via Login-As to view their ERP workspace

```
Platform User (users table)
  │
  └─── owns ──► Tenant (tenants table)
                  │
                  ├─── has many ──► TenantClient (tenant_clients table)
                  │                   │
                  │                   └─── linked to ──► User via user_id FK
                  │
                  ├─── has many ──► Invoice
                  ├─── has many ──► Project
                  ├─── has many ──► ERPTask
                  ├─── has many ──► TenantNote
                  ├─── has many ──► RecurringEntry
                  └─── has many ──► WalletTransaction (ERP ledger)
```

---

## 7. Dual Wallet System

The platform runs **two parallel wallet systems**:

| Wallet | Owner | Table | Purpose |
|--------|-------|-------|---------|
| **Platform Wallet** | User (morph) | `wallets` | Subscription payments, P2P transfers, withdrawals |
| **Client Wallet** | TenantClient | `client_wallets` | ERP billing, invoice payments within a tenant |

Both are immutable ledgers — every change creates a `wallet_transactions` record.

---

## 8. Authentication Architecture

```
Session Auth (Laravel Sanctum)
  - Standard web login (email + password)
  - Used for all web routes
  - 2FA: not yet implemented

API Auth (Sanctum tokens)
  - personal_access_tokens table
  - Used by Runtime Agent after device auth

Runtime Device Auth (custom flow)
  - Platform generates one-time device_code
  - Opens user browser to /runtime/connect?code=XXX
  - User logs in normally on website
  - Website POSTs token back to runtime:18400/auth/callback
  - Token saved to config/runtime.json
  - Runtime uses Bearer token for all platform API calls

Role-Based Auth (Spatie Permission)
  - Roles: admin, user (implicit)
  - Permissions: granular (e.g., manage-users, kyc-review)
  - Applied via middleware: role:admin
```

---

## 9. Real-Time System (WebSockets)

Two parallel real-time systems:

### Platform (Laravel Echo + Pusher/Reverb)
- Broadcasting via `channels.php`
- Used for: notifications, chat messages
- Frontend connects via `echo.ts` → `laravel-echo` + `pusher-js`

### Runtime Agent (Native WebSocket Server)
- `ws://127.0.0.1:18401/ws`
- Custom binary WebSocket server (`ws` npm package)
- Used for: plugin status, task progress, WhatsApp events
- Events: `runtime.ready`, `plugin.updated`, `auth.connected`, `wa.event`

---

## 10. Queue System

- **Driver**: Database (Laravel queue)
- **Worker**: `php artisan queue:listen --tries=1`
- **Dev mode**: `npx concurrently` starts all processes simultaneously
- Queue used for: email sending, notification dispatching, background jobs
- **No Redis in current architecture** (ioredis present in runtime only)

---

## 11. Storage Architecture

| Storage | System | Used For |
|---------|--------|----------|
| S3/Local | Laravel Flysystem | KYC docs, tool versions, screenshots |
| TenantFiles | DB + S3 | Per-tenant file manager |
| SQLite | better-sqlite3 (runtime) | Runtime tasks, licenses, plugin state |
| Database | SQLite (dev) / MySQL (prod) | All platform data |

---

## 12. Key Architectural Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Frontend | Inertia.js + React | SPA feel without full API, avoids CORS issues |
| Modules | nwidart/laravel-modules | Code organization, not microservices |
| Runtime | Node.js compiled exe | Cross-platform, no install needed |
| Payment | Kashier | MENA market focus |
| Search | MeiliSearch | Full-text search across entities |
| Auth | Sanctum | Simple SPA auth without OAuth complexity |
| CSS | TailwindCSS v4 | Utility-first, Vite plugin integration |
| State | Inertia props + React local state | No Vuex/Redux — props-down, events-up |
