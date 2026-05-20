# Musoftware Platform — Backend Architecture

> **Framework**: Laravel 12 + PHP 8.2+  
> **Module System**: nwidart/laravel-modules v11/13  
> **Auth**: Laravel Sanctum + Spatie Permission  
> **Search**: Laravel Scout + MeiliSearch

---

## 1. Module Architecture

### 8 Modules + Core App

```
app/                    → Core application (User, Auth, Admin, Finance)
Modules/
  ├── Core/            → Shared platform services (wallets, messaging, activity)
  ├── ERP/             → Business OS (clients, invoices, tasks, projects)
  ├── Freelance/       → Job marketplace (jobs, proposals, contracts)
  ├── Marketplace/     → Service marketplace (services, orders, reviews)
  ├── Booking/         → Calendar & appointment system
  ├── Intelligence/    → Market intelligence (ad tracking, competitor analysis)
  ├── Tools/           → Software distribution & runtime plugin catalog
  └── Shared/          → Shared utilities across modules
```

### Module Structure Standard

```
Modules/{Name}/
  ├── module.json         → Module metadata + provider registration
  ├── Http/
  │   ├── Controllers/    → Request handlers
  │   └── Middleware/     → Module-specific middleware
  ├── Models/             → Eloquent models
  ├── Database/
  │   └── Migrations/    → Module-scoped migrations
  ├── Services/           → Business logic services
  ├── Providers/          → ServiceProvider, RouteServiceProvider, EventServiceProvider
  ├── routes/             → web.php, api.php (module routes)
  ├── resources/          → Module-specific frontend assets (some modules)
  ├── Jobs/               → Queue jobs
  └── Console/            → Artisan commands
```

---

## 2. Service Layer Map

### Core App Services (`app/Services/`)

| Service | Responsibility |
|---------|---------------|
| `SubscriptionService` | Subscription status check, active plan lookup |
| `FinanceService` | Wallet operations, deposit, withdrawal orchestration |
| `BalanceService` | Balance calculations, locked balance management |
| `WalletTransferService` | P2P transfer logic, fee calculation, recipient lookup |
| `AdminUserService` | User management operations, block/unblock |

### ERP Module Services (`Modules/ERP/Services/`)

```php
// ActivityLogger — Audit trail for all ERP actions
ActivityLogger::log(
  action: 'invoice_paid',
  description: "Invoice #INV-001 was paid ($500)",
  subject: $invoice,      // polymorphic morphable
  clientId: $client->id
);
```

ERP Services (inferred from usage):
- `ActivityLogger` — ERP activity event recording
- `InvoiceService` (inline in controller) — Invoice number generation
- `RecurringService` — Recurring entry execution engine

---

## 3. Controller Architecture

### Controller Hierarchy

```
Controller (base)
  └── Most controllers extend this

Notable Large Controllers (potential refactoring targets):
  - SubscriptionController     (447 lines) — billing, Kashier, webhook, manage
  - ERPDashboardController     (22KB)      — dashboard + client CRUD
  - InvoiceController          (20KB)      — full invoice lifecycle
  - TaskController             (15KB)      — task + todo items management
  - WalletTransferController   (8KB)       — P2P transfer logic
  - WalletController           (13KB)      — ERP client wallet management
  - WithdrawalController       (11KB)      — ERP withdrawal management
  - AdminUsersController       (13KB)      — full user management
  - UserFileController         (10KB)      — admin file management per user
```

### Admin Controller Namespace

```
app/Http/Controllers/Admin/
  ├── DashboardController       → Platform-wide admin stats
  ├── ClientController          → Thin ERP-linked user view
  ├── KycController             → KYC document review
  ├── UsersController           → Full user management (CRUD + impersonation)
  ├── UserNoteController        → Admin notes on users
  ├── UserFileController        → Files uploaded for users
  ├── ReportController          → P&L reports
  ├── SerialSoftwareController  → Serial software registry
  ├── SerialDeviceController    → Hardware device registry
  ├── SerialUserDeviceController → User-device assignment
  └── Tools/
      └── AdminToolController   → Tool catalog management
```

### API Controller Namespace

```
app/Http/Controllers/Api/
  └── SerialDeviceController   → POST /api/serial/device (no auth, throttled)
```

---

## 4. Middleware System

### Global Middleware (app/Http/Middleware/)

| Middleware | Purpose |
|-----------|---------|
| `EncryptCookies` | Cookie encryption |
| `VerifyCsrfToken` | CSRF protection |
| Standard Laravel | Session, errors, etc. |

### Route Middleware

| Alias | Class | Purpose |
|-------|-------|---------|
| `auth` | Sanctum/Breeze auth | Requires login |
| `verified` | EnsureEmailIsVerified | Requires email verification |
| `onboarding` | Custom | Redirects to onboarding wizard if incomplete |
| `subscription:module` | Custom | Checks active module subscription |
| `role:admin` | Spatie Permission | Requires admin role |

### Subscription Middleware Logic

```php
// subscription:erp middleware checks:
UserSubscription::where('client_id', auth()->id())
    ->whereHas('plan', fn($q) => $q->where('module', 'erp'))
    ->where('status', 'active')
    ->where(fn($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
    ->exists();
// If false → redirect to /subscriptions/plans?module=erp
```

---

## 5. Model Architecture

### Base Model Patterns

```php
// TenantModel (Modules/ERP/Models/TenantModel.php)
// Auto-scopes to current authenticated user's tenant
abstract class TenantModel extends Model {
    protected static function booted() {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', auth()->user()->tenant->id ?? 0);
            }
        });
    }
}

// TenantAwareModel — alternative base (used in some ERP models)
// Similar scoping but different implementation

// All ERP data models extend TenantModel:
Invoice, Client, Project, ERPTask, TenantNote, RecurringEntry, etc.
```

### Key Model Features

| Model | Notable Features |
|-------|-----------------|
| `User` | HasRoles (Spatie), Searchable (Scout), morphOne wallet, points balance computed |
| `Invoice` | billInvoice(), partiallyBillInvoice(), cancelInvoice(), referral commissions |
| `Tool` | SoftDeletes, categories array, runner_component, metadata JSON |
| `Booking` | Status state machine, overlap detection |
| `Service` | Status workflow (draft→pending→active), escrow integration |
| `WaAccount` | WhatsApp account management, Playwright session state |

### Polymorphic Relationships

```
wallets: owner_type + owner_id  → User, TenantClient
conversations: conversable_type + conversable_id → ServiceOrder, FreelanceContract, SupportTicket
audit_logs: auditable_type + auditable_id → any model
activity_events: subject_type + subject_id → Invoice, Client, Task, etc.
```

---

## 6. Events & Listeners System

### App Events (`app/Events/`)

```
InvoicePaid  → fired after Invoice::billInvoice() completes
(+ Laravel broadcast events for notifications)
```

### App Listeners (`app/Listeners/`)

Listeners respond to events — specific handlers not fully mapped but follow standard Laravel patterns.

### Broadcasting (`routes/channels.php`)

```php
// Private user notification channel
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return $user->id === (int) $userId;
});

// Conversation channel (messaging)
Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Check user is participant
    return ConversationParticipant::where('conversation_id', $conversationId)
        ->where('user_id', $user->id)->exists();
});
```

---

## 7. Queue System

### Freelance Module Jobs (`Modules/Freelance/Jobs/`)
- Job execution jobs (background processing for freelance contracts)

### Tools Module Jobs (`Modules/Tools/Jobs/`)
- Likely: tool download processing, license sync

### ERP Console Commands (`Modules/ERP/Console/`)
- Recurring entry execution scheduler
- Subscription expiry checks

### Queue Configuration
```bash
# Dev startup (composer.json scripts.dev)
php artisan queue:listen --tries=1

# Queue driver: database (queue_jobs table)
```

---

## 8. Authentication System

### Session Auth (Web)
```php
// Laravel Breeze handles:
// POST /login, POST /register, POST /logout
// POST /forgot-password, POST /reset-password
// GET /verify-email, POST /email/verification-notification
// routes/auth.php
```

### API Auth (Sanctum)
```php
// Personal access tokens (personal_access_tokens table)
// Used by Runtime Agent after device auth
// Tokens issued via platform (not directly documented in visible code)
```

### Role System (Spatie Permission)
```php
// Roles defined: 'admin', 'user' (implicit)
// Guard: web

// Usage:
$user->hasRole('admin');
$user->can('manage-users');

// Route middleware:
Route::middleware('role:admin')->group(...)
```

---

## 9. Search Architecture (Laravel Scout + MeiliSearch)

### Searchable Models

```php
// Models using Searchable trait:
User (app/Models/User.php) - searchable
Invoice (Modules/ERP/Models/Invoice.php) - searchable
// Others may be added
```

### Search Endpoint

```
GET /search?q={query}  → SearchController::index
  └── Searches across: Users, Invoices (+ future models)
  └── Returns unified results
```

---

## 10. PDF Generation

```php
// barryvdh/laravel-dompdf

// Invoice PDF:
GET /erp/invoices/{invoice}/pdf  → InvoiceController::downloadPdf
  └── Renders Blade view → converts to PDF → download
```

---

## 11. File Storage Architecture

### Storage Providers (Tenant-level)

```php
// TenantStorageProvider model
// Tenant can configure their storage:
// - local (default)
// - s3 (AWS S3 credentials per tenant)
// - other cloud providers

// TenantFile model
// Manages files uploaded for/by tenant:
// { tenant_id, path, disk, size, mime_type, original_name, folder }
```

### Admin File Management Per User

```php
// Admin uploads files for platform users:
// GET /admin/users/{userId}/files → list files with folder structure
// POST /admin/users/{userId}/files/upload → upload to user's S3 space
// POST /admin/users/{userId}/files/folder → create folder
// GET /admin/users/{userId}/files/download → download file
// POST /admin/users/{userId}/files/rename → rename file/folder
// POST /admin/users/{userId}/files/move → move file between folders
// DELETE /admin/users/{userId}/files → delete file
```

---

## 12. Backend Technical Debt

### Critical Issues

1. **God Classes**: `ERPDashboardController` (22KB), `InvoiceController` (20KB), `TaskController` (15KB) — doing too much. Should be split by action or use dedicated Action classes.

2. **Business Logic in Models**: `Invoice::billInvoice()`, `Invoice::partiallyBillInvoice()` — while somewhat justified, they bypass service layer isolation and make testing harder.

3. **No Command Bus/CQRS**: All business operations are controller-direct. For complex operations (payment processing), a Command/Handler pattern would improve testability.

4. **Subscription middleware lacks caching**: Every request to `/erp/*` hits the database to check subscription status. Should be cached in session.

5. **KashierHelper::validatePayload()** — Called without passing request object (line 189 in SubscriptionController). Likely reads from global `$_POST` or uses a singleton — potential security concern.

6. **Recurring entry execution** — Console commands exist but the actual scheduling is not documented. No clear cron job setup found.

7. **No API versioning** — API routes at `/api/*` have no version prefix. Will cause breaking changes on evolution.

8. **Impersonation lacks session restoration** — ImpersonateController stores impersonated session but the mechanism to "exit" impersonation and return to admin session needs verification.
