# ADR 001 — Column-Based Multi-Tenancy

## Status: Accepted

## Context

Need to support multiple isolated ERP business instances (tenants) within a single SaaS platform while guaranteeing absolute data security between tenants.

Options considered:
1. **Separate Database per Tenant:** Each business subscriber receives an isolated MySQL database instance.
2. **Separate Schema per Tenant (Postgres Schemas):** A single database instance with isolated schemas for every tenant.
3. **Column-Based Isolation:** All tenants share a single MySQL database instance and tables, with data isolated using a mandatory `tenant_id` foreign key column.

## Decision

Use **Column-Based Tenancy**.

## Rationale

- **Simplicity & Speed:** Simple to implement and maintain using standard MySQL 8.0+ databases. Eliminates the operational nightmare of running hundreds of separate database connection pools.
- **Cost Efficiency:** Allows us to host thousands of small-to-medium ERP tenants on shared cloud database clusters without multiplying infrastructure costs.
- **Super-Admin Oversight:** System administrators can effortlessly run platform-wide financial aggregations and analytics across all tenant records simultaneously.
- **Easy Debugging:** Developers and support engineers can instantly identify data ownership by inspecting the explicit `tenant_id` column on any database row.

## Consequences

- **Developer Vigilance Required:** All queries against tenant tables must remember to filter by `tenant_id`. 
- **Mitigation Strategy:** We enforce global database scoping at the Eloquent ORM level. All tenant models extend a base `TenantModel` class that automatically applies a `where('tenant_id', auth()->user()->tenant_id)` scope on every query during authenticated client sessions.

## Alternatives

### Separate Database per Tenant
- **Pros:** Absolute physical data isolation. Zero risk of cross-tenant data leaks.
- **Cons:** Scaling nightmare. Running migrations across 500+ databases takes hours and introduces severe failure points during schema upgrades. Backups become extremely complex and costly.

### Schema-Based Tenancy (PostgreSQL)
- **Pros:** Clean logical isolation without managing separate physical servers.
- **Cons:** High connection overhead. Difficult to migrate individual tenant schemas between database clusters if a tenant outgrows shared hosting.

## See Also

- `Modules/ERP/Models/TenantModel.php`
- `Modules/ERP/Providers/ERPServiceProvider.php` (Global Scope Registration)
