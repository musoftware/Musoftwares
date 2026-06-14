# Rule: Always Enforce Module & Addon Subscription Checks

## Problem Statement
Using the legacy `plan_id` system or bypass checks for subscriptions leads to unauthorized feature access, broken permissions, database errors, and logic drift. All code must strictly integrate with the unified module/addon subscription layers.

## Rules & Guidelines

### 1. Abolition of Legacy Plan ID
- **NEVER** write code referencing `$user->plan_id`, `$user->plan`, or the `module_plans` table.
- Subscriptions are individual, independent feature rows in the `user_subscriptions` table (`UserSubscription` model).

### 2. Backend Access Checks
- To check module/addon access, always use the `User` helper or the `SubscriptionService`:
  ```php
  // Checking on user model directly
  $user->hasModuleSubscription('erp-backup') // Returns bool
  
  // Checking via service (automatically handles Admin/Moderator bypass)
  $service->hasActiveSubscription($user, 'erp-backup') // Returns bool
  ```
- **Admin/Moderator Bypass:** The `SubscriptionService::hasActiveSubscription()` method automatically allows Admins and Moderators. When writing custom checks, remember to allow `admin` or `moderator` roles to bypass checks if appropriate.

### 3. Backend Route & Controller Protection
- Always guard controller endpoints that belong to specific modules or addons. If a user does not have active access, abort immediately:
  ```php
  if (!$user->hasModuleSubscription('erp-backup')) {
      abort(403, 'Unauthorized. ERP Backup subscription required.');
  }
  ```

### 4. Passing Features to Frontend (Inertia)
- **Do NOT rely solely on global shared props** for page feature-toggling logic.
- Always check the access status in the controller and pass it down explicitly as a page prop:
  ```php
  // In Controller:
  return Inertia::render('ERP/Backup/Index', [
      'hasBackupFeature' => $user->hasModuleSubscription('erp-backup'),
  ]);
  ```

### 5. Frontend Feature Toggling (React/TSX)
- Read the explicit prop passed from the controller:
  ```tsx
  export default function BackupIndex({ hasBackupFeature }: { hasBackupFeature: boolean }) {
      if (!hasBackupFeature) return <UpgradeOverlay module="erp-backup" />;
      // ...
  }
  ```
- If checking against the shared `auth.crm_features` array:
  - Remember it is a **flat array of strings**, NOT a key-value map.
  - Check access using `.includes()`:
    ```tsx
    // ❌ INCORRECT (Object bracket check on array)
    const hasBackup = auth.crm_features['erp-backup'] === true;

    // ✅ CORRECT (Array include check)
    const hasBackup = auth.crm_features?.includes('erp-backup') ?? false;
    ```

### 6. Addon Validation & Purchase Rules
- Addons cannot be purchased or activated without their parent module (e.g. `erp-backup` requires `erp` to be active or purchased together).
- Always validate parent availability during cart validation or checkout.



---
