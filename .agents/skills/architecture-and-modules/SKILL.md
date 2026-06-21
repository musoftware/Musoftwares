---
name: architecture-and-modules
description: System architecture rules, tenant isolation, technology stack rules, and module design.
---

# Multi-Currency System Rules

## Purpose
Defines how financial transactions handle multiple currencies correctly based on the ERP financial rules.

## Core Rules
1. **Client Currency Boundary**: Invoices and projects use the Client's currency (`client->currency_id`).
2. **Business Currency Normalization**: Store the transaction in the Client's currency (`amount`) AND the converted base currency (`business_amount`).
3. **Daily Rates**: Use `\App\Models\CurrenciesExchange::RateByDate()` to convert. Never use live dynamic rates for past transactions.

## Anti-patterns
- Hardcoding `USD` or `EGP`.
- Summing local `amount` across different currencies. Always sum `business_amount`.



---


# Tenant Isolation Rules

## Purpose
Ensures that a user (Tenant) can only see and modify their own data. Prevents cross-tenant data leaks.

## When to Use
Every time a new database query, model, or route is created.

## Best Practices
1. Apply `TenantScope` globally to models that belong to a tenant, OR explicitly filter by `where('tenant_id', auth()->id())`.
2. Validate IDs in Requests to ensure the resource belongs to the current user.

## Anti-patterns
- `Client::find($id)` (Vulnerable to IDOR. Use `auth()->user()->clients()->findOrFail($id)`).



---


# Feature Module Structure

## Best Practices
1. **Keep Controllers Thin**: Delegate complex business logic to `Services/` or `Actions/`.
2. **Form Requests**: Always use FormRequests in `Http/Requests/` for validation.
3. **API Resources**: Use `Transformers/` or `Http/Resources/` to format JSON responses for Inertia or APIs.



---


# Laravel Module Patterns (nwidart)

## Purpose
Standardizes how business domains are structured as independent Laravel Modules.

## Structure
`Modules/{ModuleName}/`
- `Http/Controllers`: Module-specific logic.
- `Models`: Eloquent models (namespaced `Modules\{ModuleName}\Models`).
- `resources/js`: Sometimes frontend assets are kept here, but usually Inertia pages go to root `resources/js/Pages/{ModuleName}`.

## Rules
- Register Event Listeners and Policies in the Module's `Providers/{ModuleName}ServiceProvider.php`.
- **Cross-Module Communication**: Modules must communicate via events. NEVER import models directly from another module.
- **Cross-Module Listeners**: When listening to an event from another module, wrap the listener registration in `class_exists(ForeignEvent::class)` inside your `ServiceProvider->boot()` so your module doesn't crash if the foreign module is disabled.
- **Defensive Reads**: If you MUST query a foreign module's model directly for a dashboard widget, wrap the query in: `if (class_exists(ForeignModel::class) && auth()->user()->hasModuleSubscription('foreign-slug')) { try { ... } catch { ... } }`.



---


# Technology Stack

## Frontend
- React 18
- Inertia.js (React Adapter)
- Tailwind CSS v4
- Shadcn UI (Radix UI)
- TypeScript

## Backend
- Laravel 12.x
- PHP 8.2+
- MySQL / MariaDB
- Redis (Caching / Queues)



---


