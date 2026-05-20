# Musoftware Platform — Security Architecture

> **Type**: Security Review, Auth Flows, Vulnerability Analysis  
> **Classification**: Internal Engineering — Sensitive

---

## 1. Authentication Layers

### Layer 1: Session Authentication (Web)
```
Technology: Laravel Sanctum (session-based for web)
Implementation: Laravel Breeze authentication scaffolding

Flow:
  POST /login { email, password }
    → Laravel Authenticatable::attempt()
    → Session created (CSRF-protected)
    → remember_token stored if "remember me" checked

Token Storage: None — session cookie (HttpOnly, Secure)
CSRF Protection: VerifyCsrfToken middleware on all POST/PATCH/DELETE
Session Driver: File (dev) / Redis (recommended for prod)
```

### Layer 2: API Token Auth (Sanctum)
```
Technology: Laravel Sanctum personal access tokens
Table: personal_access_tokens (added 2026-05-19)

Usage:
  - Runtime Agent uses Bearer tokens
  - Tokens issued after device auth handshake
  - No expiry visible in schema (permanent until revoked)

Token Format: {id}|{plaintext_token}
Storage: Token hashed in DB (SHA256)
```

### Layer 3: Device Auth (Runtime)
```
Custom protocol — see docs/runtime/RUNTIME_ARCHITECTURE.md §Auth

Security properties:
  ✅ One-time device codes (crypto random, 48 hex chars)
  ✅ 10-minute TTL on device codes
  ✅ Codes invalidated after use
  ✅ Origin validation on callback endpoint
  ✅ Token stored server-side only (never in env or code)
  ⚠️ Token saved to config/runtime.json (plaintext on disk)
```

---

## 2. Authorization (RBAC)

### Role System
```
Technology: spatie/laravel-permission v6/7

Roles defined: 'admin' (explicit), 'user' (implicit default)
Permission guards: web (primary)

Permission storage: permission_tables migration (2026-05-15)
Tables: roles, permissions, model_has_roles, model_has_permissions, role_has_permissions
```

### Route Authorization Analysis

```
CRITICAL FINDING:
Most /admin/* routes use middleware(['auth', 'verified', 'onboarding'])
but NOT 'role:admin'.

Only these explicitly use role:admin:
  - /admin/marketplace/* routes (admin marketplace review)

This means ANY authenticated user who completes onboarding can access:
  - /admin/users → list, create, delete users
  - /admin/kyc → review KYC documents
  - /admin/serial-* → manage serial licenses
  - /admin/tools → manage tool catalog
  
RECOMMENDED FIX:
Add ->middleware('role:admin') to ALL /admin/* route groups.
```

### Subscription Gate
```
Custom middleware 'subscription:{module}':
  - Checks UserSubscription.status = 'active'
  - Checks expires_at > now OR expires_at IS NULL
  - Scoped to specific module (erp, freelance, etc.)

Applied to: /erp/* routes (subscription:erp)
```

---

## 3. Data Isolation

### Tenant Isolation
```
TenantModel global scope:
  - Auto-adds WHERE tenant_id = {current_user_tenant_id}
  - Applied to: Invoice, TenantClient, Project, ERPTask, etc.
  - Scope bypassed with withoutGlobalScope('tenant')

Risk: If tenant detection fails (no auth check), scope returns empty set
  - Code: where('tenant_id', auth()->user()->tenant->id ?? 0)
  - 0 as fallback means no data leakage (0 never matches real tenant)
  ✅ Safe fallback
```

### Client-Facing Route Isolation
```
/my/invoices:
  InvoicePaymentController::clientIndex
  └── Filters: resolves TenantClient from auth()->user()
               shows only invoices for that TenantClient

Risk: UUID used for public invoice access:
  GET /my/invoices/{uuid}/pay
  └── Invoices must use UUID (not auto-increment ID) to prevent enumeration
  ⚠️ Verify UUID is used, not integer ID
```

---

## 4. Input Validation

### Laravel Request Validation
```
Pattern: $request->validate([...]) in controllers
Form Requests: Used in some controllers (separate Request classes)

Observed validations:
  - plan_id: 'required|exists:module_plans,id'
  - id: 'required|exists:user_subscriptions,id'
  - amounts: likely 'numeric|min:0.01'

Areas needing verification:
  ⚠️ File upload validation (KYC docs, tool versions)
  ⚠️ Phone number format validation
  ⚠️ Amount precision validation (prevent overflow)
```

### XSS Protection
```
Laravel: Blade templates escape by default {{ $var }}
Inertia: React escapes all output by default
DOMPurify (v3.4): Used in frontend for HTML sanitization
marked (v18): Markdown rendering (admin notes, descriptions)
  └── Should be sanitized with DOMPurify before rendering
```

### SQL Injection
```
Eloquent ORM: All queries parameterized automatically
Raw queries: None observed in reviewed code
Scout search: Passes to MeiliSearch (search engine, not SQL)
✅ Low risk
```

---

## 5. File Upload Security

### KYC Document Uploads
```
POST /kyc/upload
  - Files stored in S3 or local storage
  - Accessible only via authenticated download endpoint
  - Admin reviews via /admin/kyc

⚠️ Verify:
  - MIME type validation (not just extension)
  - File size limits
  - Virus scanning (not observed)
  - Public URL not exposed (files behind auth)
```

### Tool Version Uploads (Admin)
```
POST /admin/tools/{tool}/upload-version
  - Only admin can upload
  - ZIP file containing plugin code
  - Extracted on runtime agent side

⚠️ Verify:
  - ZIP slip vulnerability protection (path traversal in archives)
  - No executable upload → execution on server
  - Signature verification of downloaded plugins
```

---

## 6. WhatsApp Automation Security

### AntiBan Engine
```
Purpose: Prevent WhatsApp account bans from aggressive automation

Controls:
  - Velocity tracking per account
  - Rate limiting enforcement
  - Ban risk scoring
  - getVelocityStats(accountId)

⚠️ Data persistence:
  - WhatsApp session data stored locally (Playwright profile)
  - Session cookies/credentials on user's machine
  - Not encrypted (potential credential theft if machine compromised)
```

### Browser Automation Risk
```
Playwright running headless browsers:
  ⚠️ Cross-site script injection if user-provided URLs are scraped
  ⚠️ Memory leaks from zombie browser instances
  ✅ Each session has health monitoring
  ✅ Release() frees browser resources
```

---

## 7. Runtime Security

### CORS Policy
```javascript
ALLOWED_ORIGIN = /^https?:\/\/(.*\.)?musoftware\.com$|^https?:\/\/(localhost|127\.0\.0\.1)(:\d+)?$/

Coverage:
  ✅ Production: *.musoftware.com
  ✅ Development: localhost:* and 127.0.0.1:*
  ✅ No wildcard (*) allowed
  
WebSocket: Origin checked on connection
HTTP: CORS middleware applied globally
```

### Plugin Sandboxing
```
Sandbox.js (core/runtime/Sandbox.js) provides:
  - Plugin isolation context
  - Memory limit enforcement (maxMemoryMb from manifest)
  - Concurrent task limits (maxConcurrentTasks from manifest)
  - Filesystem allowlist (fsAllowlist from manifest)

⚠️ Node.js plugins run in same process (require())
   True process isolation only for Python plugins (subprocess)
   Malicious nodejs plugin could access runtime internals
```

### Security Audit Log
```
GET /admin/audit
  → security.getAuditLog(100)

Events audited:
  - 'route.register' — plugin route mount
  - 'task.spawn' — plugin task execution
  - any SecurityManager.audit() calls

⚠️ Audit log is in-memory (not persisted to disk)
   Lost on runtime restart
```

### Trust Levels
```
Plugin trust levels affect spawn_task permission:
  'trusted'     → first-party Musoftware plugins
  'community'   → vetted community plugins
  'unverified'  → user-installed, unknown

SecurityManager.checkTrustLevel(plugin, 'spawn_task') enforces this
Implementation in SecurityManager.js — exact rules not fully reviewed
```

---

## 8. Payment Security

### Kashier Integration
```
KashierHelper::buildSubscriptionPaymentUrl() — builds redirect URLs
KashierHelper::validatePayload() — validates webhook signatures

⚠️ validatePayload() called without request object parameter:
   if (KashierHelper::validatePayload()) {
   This suggests it reads from $_POST globals — potential issue if
   called outside proper request context.

Idempotency: SubscriptionInvoice.where(transaction_reference, trxId).exists()
   ✅ Duplicate webhook protection implemented

⚠️ No Kashier IP whitelist check on webhook endpoint
   Any IP can POST to /subscriptions/kashier/webhook
   Only protection is signature validation
```

---

## 9. Impersonation Security

```
Admin impersonation flow:
GET /admin/users/{id}/login-as
  → ImpersonateController

Security measures:
  ✅ ImpersonationLog records each impersonation
  ✅ Records: impersonator_id, impersonated_id, started_at, ended_at, ip_address

⚠️ No role check on /admin/users/{id}/login-as route
   Any authenticated+onboarded user could impersonate others

⚠️ Session restoration mechanism not confirmed
   "Exit impersonation" flow should be verified
```

---

## 10. Production Security Checklist

### Critical (Must Fix Before Production)
- [ ] Add `role:admin` middleware to ALL `/admin/*` routes
- [ ] Validate Kashier webhook from Kashier IP ranges only
- [ ] Audit `/my/invoices/{id}/pay` — ensure UUID used, not integer

### High Priority
- [ ] Implement token expiry for Sanctum personal access tokens
- [ ] Persist runtime security audit log to SQLite database
- [ ] Add MIME type validation + file size limits for all uploads
- [ ] Verify admin-only access to impersonation (login-as) endpoint

### Medium Priority
- [ ] Encrypt runtime config/runtime.json (token at rest)
- [ ] Add virus scanning for file uploads (KYC docs, tool versions)
- [ ] Implement Kashier IP whitelist on webhook endpoints
- [ ] Add rate limiting to subscription purchase endpoints (prevent abuse)

### Low Priority
- [ ] Add 2FA option for admin accounts
- [ ] Implement DOMPurify sanitization on all markdown-rendered content
- [ ] Add ZIP slip protection check for tool version downloads
- [ ] Document and test session restoration after impersonation exit
