---
description: Guidelines for creating, enforcing, and integrating a la carte addons and feature flags across Musoftware modules.
---


# Features & Addons Creation

The Musoftware SaaS system relies on an "a la carte" capability model where users subscribe to base modules (like ERP, CRM, Booking) and then purchase specific "addons" to unlock additional functionality.

Follow these strict guidelines when creating a new feature or addon.

## 1. Defining the Addon (Configuration)

All addons are centrally defined in the SaaS configuration file:
`config/saas.php`

When adding a new feature, append it to the `addons` array. You must provide:
- `price`: The price in EGP/Year.
- `name`: A user-friendly name (e.g., 'WhatsApp Reminders').
- `desc`: A short, descriptive text.
- `icon`: A valid Lucide React icon name (e.g., 'MessageSquare').
- `parent`: The parent module this addon belongs to (e.g., 'booking', 'crm', 'erp').

**Example:**
```php
'booking-wa-reminders' => [
    'price' => 500,
    'name' => 'WhatsApp Reminders',
    'desc' => 'Reminder, confirmation, & reschedule links',
    'icon' => 'MessageSquare',
    'parent' => 'booking'
],
```

## 2. Backend Enforcement (Laravel)

### Route Middleware Standard
Modules and Addons must be protected at the routing layer using the standard `subscription:` middleware. **Never use the legacy `feature:` middleware.**

**Route Definition Example:**
```php
// Protect a whole route group with the subscription middleware
Route::middleware(['auth', 'verified', 'subscription:booking-wa-reminders'])
    ->prefix('wa-reminders')
    ->group(function () {
        Route::get('logs', [WaReminderLogController::class, 'index']);
    });
```

*(Note: The `feature('addon-key')` helper is still used inside controllers/services for inline boolean checks, but routing should use the standard middleware).*

### Usage Limits
If your feature relies on usage limits (e.g., number of SMS messages, number of team members), use the `canUse()` helper.

```php
if (!canUse('sms_limit', 1)) {
    // Abort or handle limit reached
}
```

## 3. Frontend Integration (React/Inertia)

Features and their statuses are automatically shared with the frontend via Inertia's shared properties in `app/Http/Middleware/HandleInertiaRequests.php`.

The user's active features are typically available in:
`usePage().props.auth.crm_features`

*(Note: Despite the name `crm_features`, it generally applies to features available to the active user's workspace/tenant).*

### Conditionally Rendering UI
Use the shared `auth` properties to hide, show, or lock UI elements based on the addon's availability.

**React Example:**
```tsx
import { usePage } from '@inertiajs/react';
import { PageProps } from '@/types';

export default function BookingSettings() {
    const { auth } = usePage<PageProps>().props;
    
    // Check if the feature is present and true/active
    const hasWaReminders = auth.crm_features?.['booking-wa-reminders'] === true;

    return (
        <div>
            {hasWaReminders ? (
                <WaReminderConfig />
            ) : (
                <div className="locked-feature-banner">
                    <p>Unlock WhatsApp Reminders to automate your confirmations!</p>
                    <UpgradeButton feature="booking-wa-reminders" />
                </div>
            )}
        </div>
    );
}
```

## 4. Modular Architecture (Directory Structure)

To keep the monolith organized and maintainable, addons should encapsulate their logic inside the specific module they belong to, grouped by the "Feature" name. 

Addon or feature logic (like Controllers, Models, Events, Services) should be located in:
`Modules/{ModuleName}/app/Features/{FeatureName}/`

**Example Structure:**
```text
Modules/Booking/app/Features/Reminders/
  ├── Events/
  ├── Http/
  │   └── Controllers/
  ├── Jobs/
  ├── Listeners/
  ├── Models/
  ├── Repositories/
  └── Services/
```

This enforces a Domain-Driven approach where feature-specific logic doesn't clutter the main module directory.

## 5. Best Practices

- **Never Hardcode Logic:** Do not rely on hardcoded subscription logic. Always route capability checks through `feature('addon-key')` or `canUse('limit-key')`.
- **Graceful Degradation:** When a feature is missing, provide a premium, Apple-level UI upgrade prompt rather than a broken page or a raw 403 error page.
- **Fail Closed:** If you cannot verify a feature's status, default to denying access.
