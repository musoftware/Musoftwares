# Subscription Handling Rules

## Rules
1. **Abolish legacy plan_id**: Never use `$user->plan_id`.
2. Use `$user->hasModuleSubscription('module-name')`.
3. Protect routes, controllers, and UI using this check.
4. Pass feature flags via Inertia props (`hasBackupFeature => ...`).
