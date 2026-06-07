---
name: backend-development
description: Comprehensive backend development patterns including API formats, queue jobs, service classes, migrations, and auth rules.
---

# API Response Format Rules

## Rules
1. Never leak Laravel database exception messages or stack traces.
2. Return standard JSON structures: `{ "status": "success|error", "message": "...", "data": {} }`.
3. Catch all model not found exceptions gracefully.



---


# Queue & Job Rules

## When to Use
Sending emails, generating large PDFs, communicating with external third-party APIs.

## Rules
1. Implement `ShouldQueue`.
2. Use `InteractsWithQueue`, `Queueable`, `SerializesModels`.
3. Handle failures via `failed(\Throwable $exception)`.



---


# Service Class Patterns

## Purpose
Decouples business logic from HTTP Controllers to allow reuse in console commands, jobs, and other controllers.

## Examples
```php
class InvoiceService {
    public function generatePdf(Invoice $invoice) { ... }
    public function calculateTotals(Invoice $invoice) { ... }
}
```



---


# Database Migration Rules

## Rules
1. Always define `down()` methods to drop tables/columns cleanly.
2. Use constrained foreign keys: `$table->foreignId('client_id')->constrained('erp_tenant_clients')->cascadeOnDelete();`
3. Never drop columns without confirming it won't break existing live data.



---


# Spatie Model States Rules

## Purpose
Manage complex status transitions (e.g., Invoice `Draft` -> `Sent` -> `Paid`).

## Rules
1. Extend `Spatie\ModelStates\State`.
2. Define allowed transitions in the Model's `registerStates()` method.
3. Check `canTransitionTo()` before applying state changes.



---


# Permissions & Roles Rules

## Rules
1. Use Spatie Permission.
2. Guard backend routes using middleware: `middleware('permission:edit_invoices')`.
3. Pass `can` or `permissions` array to frontend Inertia props to conditionally hide UI buttons.



---


# Test Writing Rules

## Rules
1. Write Feature tests for all HTTP endpoints.
2. Assert database state, response status, and session flashes.
3. Use Factories to generate mock data.
4. Clean the database using `RefreshDatabase` trait.



---


