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
