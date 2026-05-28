---
name: Subscription & Feature Access System
description: Authoritative rules for the module-based subscription architecture, feature access checking, pricing, and frontend integration. Covers UserSubscription, FeatureManager, SubscriptionService, PricingService, and Inertia shared props.
---

# Subscription & Feature Access System

## 1. Core Architecture — No More `plan_id`

> **CRITICAL**: The old `plan_id`-based subscription system is ABOLISHED. Never use `plan_id`, `Plan` model, or `module_plans` table for new code.

### The New Model: `user_subscriptions` Table

Every subscription (module or addon) is a row in `user_subscriptions`:

```
| Column      | Type     | Description                                    |
|-------------|----------|------------------------------------------------|
| id          | int      | Primary key                                    |
| client_id   | int      | FK to users.id (the subscriber)                |
| object      | string   | Slug identifier (e.g. 'erp', 'erp-backup')     |
| status      | string   | 'active', 'cancelled', 'expired'               |
| started_at  | datetime | When subscription began                        |
| expires_at  | datetime | When subscription expires                      |
| auto_renew  | boolean  | Whether to auto-renew                          |
```

### Key Rules

1. **`object` is the slug** — It matches keys in `config/saas.php` (modules section keys or addons section keys).
2. **No `plan_id` column** — The `user_subscriptions` table does NOT have `plan_id`. Never reference it.
3. **One row per feature** — A user with ERP + ERP Backup has TWO rows: `object='erp'` and `object='erp-backup'`.
4. **Expiry matters** — Always check `expires_at > now()` alongside `status = 'active'`.

---

## 2. User Model Methods

File: `app/Models/User.php`

### `hasSubscription(): bool`
Checks if user has ANY active subscription (module or addon).

```php
public function hasSubscription(): bool
{
    // Check new module-based subscriptions first
    if ($this->subscriptions()->where('status', 'active')->where('expires_at', '>', now())->exists()) {
        return true;
    }
    // Legacy fallback (plan_id + subscription_date)
    if ($this->plan_id && $this->subscription_date) {
        return Carbon::parse($this->subscription_date)->isFuture();
    }
    return false;
}
```

### `hasModuleSubscription(string $module): bool`
Checks if user has an active subscription to a SPECIFIC module or addon.

```php
public function hasModuleSubscription(string $module): bool
{
    return $this->subscriptions()
        ->where('object', $module)
        ->where('status', 'active')
        ->where('expires_at', '>', now())
        ->exists();
}
```

### Rules
- **Use `hasModuleSubscription()` for specific feature checks** (e.g., `$user->hasModuleSubscription('erp-backup')`).
- **Use `hasSubscription()` only for "does user have anything at all?"** checks.
- **Never use `$user->plan` or `$user->plan_id`** for new logic.
- **Always include `expires_at > now()`** — an `active` status with past `expires_at` is effectively expired.

---

## 3. SubscriptionService

File: `app/Services/SubscriptionService.php`

### `hasActiveSubscription(User $user, string $module): bool`

This is the **primary service method** for checking module access. Used by `HandleInertiaRequests` to populate `active_modules`.

```php
public function hasActiveSubscription(User $user, string $module): bool
{
    // Admins bypass
    if ($user->hasRole(['admin', 'Admin', 'moderator', 'Moderator'])) {
        return true;
    }
    // Check specific module in user_subscriptions
    if ($user->hasModuleSubscription($module)) {
        return true;
    }
    // Legacy fallback
    if ($user->plan_id && $user->subscription_date) {
        return Carbon::parse($user->subscription_date)->isFuture();
    }
    return false;
}
```

### Rules
- **Must check the specific module**, not just "any subscription".
- **Admin/Moderator roles always return true**.
- **Legacy fallback** is kept for old users who haven't migrated.

---

## 4. FeatureManager

File: `Modules/CRM/app/Core/FeatureManager.php`

### Purpose
Provides the `feature('slug')` helper and populates `auth.crm_features` shared Inertia prop.

### Key Methods

| Method | Purpose |
|--------|---------|
| `has(string $feature): bool` | Check if a feature is active |
| `getAll(): array` | Get all active features (auto-resolves user from guards) |
| `getAllForUser(User $user): array` | Get all active features for a specific user (preferred) |
| `flush()` | Clear cached features |

### How It Works
`getAllForUser($user)` queries `user_subscriptions` where `client_id = $user->id`, `status = 'active'`, and `expires_at > now()`, then plucks `object` as an array of strings.

**Example return**: `['erp', 'erp-backup', 'crm']`

### Rules

1. **Always use `getAllForUser($user)` when you have a user reference** — Don't rely on `getAll()` which guesses the user from auth guards.
2. **The return is an array of strings**, NOT a key-value map — Check with `in_array()` or `includes()`, not bracket access.
3. **Multi-guard awareness** — The ERP module uses `erp_team` guard. The default `auth()->user()` may return null on ERP pages. `getAllForUser()` avoids this issue entirely.
4. **Caching** — Results are cached in `$this->activeFeatures`. Call `flush()` after subscription changes.

### Common Pitfall: `feature()` Helper
The `feature('erp-backup')` helper internally calls `FeatureManager::has()`. If the FeatureManager doesn't have the correct user context, it returns `false`. **For controllers, pass the feature status explicitly as a prop instead of relying on the helper.**

---

## 5. HandleInertiaRequests — Shared Props

File: `app/Http/Middleware/HandleInertiaRequests.php`

### User Resolution

```php
$user = $request->user(); // Default guard
if (auth('erp_team')->check()) {
    $user = auth('erp_team')->user()?->tenant?->user; // ERP team → owner
}
```

> **CRITICAL**: The resolved `$user` may differ from `auth()->user()`. Always pass `$user` explicitly to any service that needs it.

### `auth.active_modules`
```php
'active_modules' => [
    'erp' => $service->hasActiveSubscription($user, 'erp'),
    'crm' => $service->hasActiveSubscription($user, 'crm'),
    // ...
]
```
This is a **key-value map** (`{erp: true, crm: false}`).

### `auth.crm_features`
```php
'crm_features' => function () use ($user) {
    return app(FeatureManager::class)->getAllForUser($user);
}
```
This is an **array of strings** (`['erp', 'erp-backup']`).

### Rules
- **`active_modules`** = module-level access (key-value boolean map)
- **`crm_features`** = specific feature slugs (flat string array)
- **Always pass `$user`** via `use ($user)` closure — never let services resolve auth themselves.
- **These are lazy props** — Inertia only evaluates them when the frontend requests them.

---

## 6. Frontend Feature Checking

### Checking Module Access (TSX)
```tsx
const { auth } = usePage().props as any;

// Module access (key-value map)
const hasErp = auth?.active_modules?.erp === true;
const hasCrm = auth?.active_modules?.crm === true;
```

### Checking Specific Features (TSX)
```tsx
// crm_features is an ARRAY of strings, NOT a key-value map
const hasBackupFeature = Array.isArray(auth?.crm_features)
    ? auth.crm_features.includes('erp-backup')
    : auth?.crm_features?.['erp-backup'] === true; // legacy fallback
```

### Rules
- **Never use `auth.crm_features['feature'] === true`** alone — `crm_features` is a string array.
- **Use `.includes('slug')`** for feature checks.
- **Keep the legacy fallback** (`?.['key'] === true`) for backward compatibility during transition.
- **Best practice**: Pass feature status as a controller prop instead of relying on shared props.

### Best Practice: Pass from Controller
```php
// In your controller:
return Inertia::render('ERP/Backup/Index', [
    'hasBackupFeature' => $user->hasModuleSubscription('erp-backup'),
]);
```
```tsx
// In your component:
export default function BackupIndex({ hasBackupFeature }: { hasBackupFeature: boolean }) {
    // Direct, reliable, no guard ambiguity
}
```

---

## 7. PricingService — Source of Truth for Prices

File: `app/Services/PricingService.php`

### `getServiceItems(): array`
Returns all modules and addons with pricing info from `config/saas.php`.

Each item has:
```php
[
    'id' => 'erp',              // slug
    'name' => 'ERP',            // display name
    'type' => 'module',         // 'module' or 'addon'
    'parent_id' => null,        // addon's parent module slug
    'monthly_price' => 500,     // yearly_price / 10
    'yearly_price' => 5000,     // from config/saas.php
]
```

### Rules
- **Monthly price = yearly / 10** (not / 12). This is the business rule.
- **Use `monthly_price` key**, not `price` — `getServiceItems()` returns `monthly_price`.
- **Never hardcode prices** — Always read from `PricingService` or `config/saas.php`.
- **Addons have `parent_id`** — e.g., `erp-backup` has `parent_id = 'erp'`.

---

## 8. Subscribe Flow

File: `app/Http/Controllers/SubscriptionController.php`, method `subscribe()`

### Request Format
```php
POST /subscriptions/subscribe
{
    'items' => ['erp', 'erp-backup'],  // array of slugs
    'billing_cycle' => '1_month',       // '1_month', '6_month', '1_year'
    'is_new_system' => false            // true creates tenant, false extends
}
```

### Rules
1. **Addons require parent** — Can't buy `erp-backup` without owning `erp`.
2. **Sufficient balance required** — Check `user_balance >= total_cost`.
3. **Extending existing** — If user already owns a module, the new purchase extends `expires_at` (doesn't create duplicate).
4. **`is_new_system = true`** creates a new tenant — Only use for first-time module purchase. Uses `tenant_id` column which must exist.
5. **Deduct from wallet** — `$user->user_balance -= $totalCost`.

---

## 9. Config: `config/saas.php`

### Structure
```php
return [
    'modules' => [
        'erp' => 5000,           // yearly price in EGP
        'crm' => 5000,
        'booking' => 3000,
        // ...
    ],
    'addons' => [
        'erp-backup' => [
            'price' => 500,       // yearly price in EGP
            'name' => 'ERP Backup',
            'desc' => '...',
            'icon' => 'HardDrive',
            'parent' => 'erp',    // parent module slug
        ],
        // ...
    ],
];
```

### Rules
- **Module keys** are simple slug → price mappings.
- **Addon keys** are slug → config array with `price`, `name`, `desc`, `icon`, `parent`.
- **`parent`** links addon to its parent module.
- **All prices are yearly in EGP**.

---

## 10. Database Migration Rules

### SQLite Compatibility (for Testing)
Tests use SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`).

**MySQL-specific SQL is FORBIDDEN in migrations without a driver guard:**

```php
// ❌ WRONG — breaks SQLite tests
DB::statement('SET FOREIGN_KEY_CHECKS=0;');

// ✅ CORRECT — driver-aware
$driver = DB::getDriverName();
if ($driver === 'mysql') {
    DB::statement('SET FOREIGN_KEY_CHECKS=0;');
} elseif ($driver === 'sqlite') {
    DB::statement('PRAGMA foreign_keys = OFF');
}
```

### Alter Table Guards
If altering a table that might not exist in testing:
```php
// ✅ Guard with hasTable
if (!Schema::hasTable('some_legacy_table')) return;
Schema::table('some_legacy_table', function (Blueprint $table) {
    // ...
});
```

### Currency Seeding
The `create_currencies_table` migration already seeds USD, EGP, EUR, GBP, AED. **Do NOT manually create currencies in test setUp** — they already exist from the migration.

---

## 11. Common Pitfalls & Anti-Patterns

### ❌ Using `plan_id` for subscription checks
```php
// WRONG
if ($user->plan_id) { /* has subscription */ }
$user->plan->features; // broken relationship
```

### ❌ Using `whereHas('plan')` in queries
```php
// WRONG — plan relationship is legacy
UserSubscription::where('client_id', $user->id)
    ->whereHas('plan', fn($q) => $q->where('module', 'crm'))
    ->first();
```

### ❌ Checking `crm_features` as key-value map in frontend
```tsx
// WRONG — crm_features is an array, not a map
const has = auth.crm_features?.['erp-backup'] === true;

// CORRECT
const has = auth.crm_features?.includes?.('erp-backup') ?? false;
```

### ❌ Relying on `auth()->user()` in services shared across guards
```php
// WRONG — returns null when erp_team guard is active
$user = auth()->user();

// CORRECT — pass user explicitly
public function getAllForUser(User $user): array { ... }
```

### ❌ Hardcoding prices
```php
// WRONG
'amount' => 0,
'amount' => 500,

// CORRECT
$item = collect($serviceItems)->firstWhere('id', $sub->object);
'amount' => $item['monthly_price'] ?? 0,
```

### ❌ Using `price` instead of `monthly_price`
```php
// WRONG — PricingService returns monthly_price, not price
$erp['price']; // null

// CORRECT
$erp['monthly_price']; // 500
```

---

## 12. Testing Checklist

When modifying subscription logic, these tests MUST pass:

```bash
php artisan test --filter=SubscriptionModuleTest
```

Test file: `tests/Feature/SubscriptionModuleTest.php`

Coverage:
- `User::hasSubscription()` — active, none, expired
- `User::hasModuleSubscription()` — specific module, different module, expired, addon
- `SubscriptionService::hasActiveSubscription()` — subscribed, unsubscribed, module-specific
- `FeatureManager::getAllForUser()` — active features, expired exclusion
- `PricingService::getServiceItems()` — module exists, addon exists, correct prices
- Subscribe flow — addon with parent, addon without parent, module+addon, insufficient balance, no items
- Manage page — correct prices from PricingService
- Plans page — owned features
- `active_modules` shared prop — true when subscribed, false when not
- `crm_features` shared prop — contains active, excludes expired
- Schema — no `plan_id` column, correct table name

---

## 13. File Reference

| File | Purpose |
|------|---------|
| `app/Models/User.php` | `hasSubscription()`, `hasModuleSubscription()`, `subscriptions()` relationship |
| `app/Models/UserSubscription.php` | The subscription model (`user_subscriptions` table) |
| `app/Services/SubscriptionService.php` | Module access checking service |
| `app/Services/PricingService.php` | Price calculation from config/saas.php |
| `Modules/CRM/app/Core/FeatureManager.php` | Feature flag system, `feature()` helper backend |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shares `active_modules` and `crm_features` to frontend |
| `app/Http/Controllers/SubscriptionController.php` | Subscribe, manage, plans pages |
| `config/saas.php` | Module and addon pricing definitions |
| `tests/Feature/SubscriptionModuleTest.php` | 32 tests, 111 assertions |
