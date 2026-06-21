---
description: Authoritative rules for keeping every SaaS module (Booking, CRM, ERP, AffiliatePos, Freelance) independently deployable. Defines the cross-module event bus pattern and forbids direct model imports across module boundaries.
---


# SaaS Module Separation Rules

## The Core Law

Every module is an **independent SaaS product**. It provides a **service to the user**, not a component to another module. Removing Module A must never break Module B.

> [!CAUTION]
> **ABSOLUTE PROHIBITION — Direct Cross-Module Model Imports**
> Never import or instantiate a model from another module directly inside a Controller, Service, or Command.
> This is the #1 coupling violation in this codebase and MUST NEVER happen.

```php
// ❌ FORBIDDEN — Booking importing ERP models
$tenant = \Modules\ERP\Models\Tenant::where('user_id', $host->id)->first();
$client = \Modules\ERP\Models\TenantClient::firstOrCreate([...]);

// ❌ FORBIDDEN — CRM querying ERP Invoice/Project directly
$invoices = \Modules\ERP\Models\Invoice::where('tenant_id', $tenantId)->get();
$projects = \Modules\ERP\Models\Project::where('tenant_id', $tenantId)->get();

// ❌ FORBIDDEN — ERP querying Booking without subscription guard
$bookings = \Modules\Booking\Models\Booking::where(...)->get();
```

---

## 1. The Allowed Cross-Module Pattern: Events

Modules communicate **only via Laravel Events**. The emitting module fires an event and has zero knowledge of what other modules do with it.

### How It Works

```
Module A fires:  event(new ModuleAThingHappened($data))
Module B listens: class ModuleBListenerForA { handle(ModuleAThingHappened $e) }
Module B registers: Event::listen(ModuleAThingHappened::class, ModuleBListenerForA::class)
```

Module B's listener is **registered inside Module B's ServiceProvider**, not Module A's.
Module B's listener registration is **wrapped in `class_exists()`** so Module B works even if Module A is absent.

### Canonical Example — Booking → ERP

**Booking fires the event** (knows nothing about ERP):
```php
// Modules/Booking/Events/BookingConfirmed.php
class BookingConfirmed {
    public function __construct(public Booking $booking) {}
}

// Inside BookingController::handlePostBookingOperations()
event(new BookingConfirmed($booking)); // ← That's it. Booking is done.
```

**ERP listens and acts** (completely isolated):
```php
// Modules/ERP/Listeners/SyncBookingClientToErpListener.php
class SyncBookingClientToErpListener {
    public function handle(BookingConfirmed $event): void {
        $tenant = Tenant::where('user_id', $event->hostUserId)->first();
        if (!$tenant) return; // ERP not active for this user — fine.
        TenantClient::firstOrCreate([...]);
    }
}

// Modules/ERP/Providers/ERPServiceProvider.php — boot()
if (class_exists(\Modules\Booking\Events\BookingConfirmed::class)) {
    Event::listen(
        \Modules\Booking\Events\BookingConfirmed::class,
        SyncBookingClientToErpListener::class
    );
}
```

---

## 2. The Defensive Read Pattern (Dashboard Widgets)

When a module's dashboard wants to **display data from another module** (e.g., ERP dashboard showing upcoming bookings), use the Defensive Read Pattern:

```php
// ✅ CORRECT — Three guards in order
$upcomingBookings = collect();
if (
    $ownerUser->hasModuleSubscription('booking') &&   // 1. User subscribes to that module
    class_exists(\Modules\Booking\Models\Booking::class) // 2. Module is loaded
) {
    try {
        $upcomingBookings = \Modules\Booking\Models\Booking::...->get(); // 3. Try-catch
    } catch (\Throwable $e) {
        Log::debug('[ERP] Booking widget unavailable: ' . $e->getMessage());
        $upcomingBookings = collect(); // Graceful degradation
    }
}
```

**Three required guards:**
1. `$user->hasModuleSubscription('module-slug')` — only query if user actually pays for it
2. `class_exists(ModelClass::class)` — only query if module is loaded at all
3. `try/catch (\Throwable $e)` — never let cross-module failure crash the host module

---

## 3. Route Middleware Standard

All module routes MUST use `subscription:` middleware, NOT `feature:` middleware.

```php
// ✅ CORRECT — standard SaaS subscription gate
Route::middleware(['auth', 'verified', 'subscription:booking'])->group(...)
Route::middleware(['auth', 'verified', 'subscription:erp'])->group(...)
Route::middleware(['auth', 'verified', 'subscription:crm'])->group(...)
Route::middleware(['auth', 'verified', 'subscription:affiliate_pos'])->group(...)
Route::middleware(['auth', 'verified', 'subscription:freelance'])->group(...)

// ❌ WRONG — non-standard, inconsistent
Route::middleware(['auth', 'verified', 'feature:affiliate_pos'])->group(...)
```

**Addon routes** inside a module can use `feature:addon-slug` for addon-level gating, but the **module-level gate is always `subscription:`**.

---

## 4. Route URL Prefix Standard

Every module owns ONE unified URL namespace. Role-based sub-sections go inside that namespace.

```
✅ CORRECT:
/booking/*              — All Booking routes
/erp/*                  — All ERP routes
/crm/*                  — All CRM routes
/pos/merchant/*         — AffiliatePos merchant section
/pos/vendor/*           — AffiliatePos vendor section
/pos/affiliate/*        — AffiliatePos affiliate section
/freelance/*            — All Freelance routes

❌ WRONG — role names in root URL, fragmented namespaces:
/admin/affiliate-pos/*
/vendor/affiliate-pos/*
/affiliate/affiliate-pos/*
```

Never use role names (`admin`, `vendor`, `affiliate`) as root URL segments. Role determines what the user sees, not the URL path.

---

## 5. Module Events Registry

This is the canonical list of cross-module events. Maintain it whenever a new event is added.

| Event | Fired By | Who Listens | Data Carried |
|---|---|---|---|
| `BookingConfirmed` | Booking | ERP (sync TenantClient) | `booking`, `hostUserId`, `guestName`, `guestEmail`, `guestPhone`, `currencyId` |
| `BookingStatusChanged` | Booking | Booking own (WA reminders) | `booking`, `status`, `isRescheduled` |
| `BookingCapacityUpdated` | Booking | Booking own | `eventType`, `capacity` |

When adding a new cross-module integration, add the event to this table.

---

## 6. Checklist — Before Any Cross-Module Code

Before writing any code that touches another module's data, ask:

- [ ] Can I fire an event from Module A and let Module B listen? (preferred)
- [ ] If I must read cross-module data (dashboard widget), do I have all 3 guards: `hasModuleSubscription` + `class_exists` + `try/catch`?
- [ ] Am I using `subscription:module` middleware (NOT `feature:module`) on all route groups?
- [ ] Does my module's ServiceProvider register its cross-module listeners with a `class_exists` guard?
- [ ] Does removing Module B cause Module A to crash? (answer must be NO)
- [ ] Are fake/hardcoded data values (like `'roi' => '125%'`) replaced with real data or `null`?

---

## 7. Anti-Patterns Quick Reference

| Anti-Pattern | Why Forbidden | Correct Alternative |
|---|---|---|
| `\Modules\ERP\Models\Tenant::where(...)` inside Booking | Hard dependency — Booking crashes without ERP | Fire `BookingConfirmed` event; ERP listens |
| `\Modules\ERP\Models\Invoice::where(...)` inside CRM | Hard dependency — CRM crashes without ERP | Defensive Read Pattern with `hasModuleSubscription('erp')` |
| `\Modules\Booking\Models\Booking::where(...)` without guard | ERP runs Booking queries for non-Booking users | `hasModuleSubscription('booking') && class_exists(...)` |
| `class_exists()` without `hasModuleSubscription()` | Skips subscription check — unauthorized cross-module data access | Always pair both guards |
| `feature:module_slug` on module-level routes | Non-standard, bypasses subscription system | `subscription:module_slug` |
| `/admin/module-name/*` prefix | Exposes role in URL, fragmented namespace | `/module/role-section/*` |
| Hardcoded metric values (`'roi' => '125%'`) | Misleading fake data | `null` or real computed value |
| `str_random()` | Removed since Laravel 8 | `Str::random()` |
