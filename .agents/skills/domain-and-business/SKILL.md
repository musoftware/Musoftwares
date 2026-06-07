---
name: domain-and-business
description: Business domain rules, ERP rules, addons registration, subscriptions, and translation workflows.
---

# Addon Registration Rules

## Rules
1. New addons must be defined in `modules_statuses.json` or the database subscription layer.
2. Validate parent module existence (an addon requires its parent module to function).



---


# ERP Domain Business Rules

## Core Operational Logic
1. **Transactions as Source of Truth**: Compute income ONLY from the transactions table, never directly from invoices.
2. **Locked Balance**: Computed dynamically by summing unpaid/pending invoices. Do not persist locked balance in DB.
3. **No Intermediate Wallets**: Link transactions and expenses directly to the Client/Project using foreign keys.



---


# Subscription Handling Rules

## Rules
1. **Abolish legacy plan_id**: Never use `$user->plan_id`.
2. Use `$user->hasModuleSubscription('module-name')`.
3. Protect routes, controllers, and UI using this check.
4. Pass feature flags via Inertia props (`hasBackupFeature => ...`).



---


# Translation & i18n Rules

## Rules
1. **Zero Hardcoded English**: Never hardcode text strings in views, controllers, or logs.
2. Use `__('module.key')` in PHP, and translation helpers/props in React.
3. Always update both English and Arabic lang files simultaneously.
4. Never mix languages in a single UI element or PDF.



---


