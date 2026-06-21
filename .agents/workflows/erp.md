---
description: Enterprise Resource Planning architecture, lifecycle continuity, and financial operational logic.
---


# ERP & Operational Workflows

This skill outlines the philosophy and implementation requirements for the Enterprise Resource Planning (ERP) features of Musoftware. It dictates how financial entities, company resources, and client operations must be handled.

## Activation Conditions
This skill automatically applies when you are:
- Working on the Billing, Invoicing, or Wallet systems.
- Implementing Client / Company resource allocation.
- Developing subscription or licensing logic.
- Building complex operational dashboards that tie into company finances.

## 1. ERP Philosophy: Workflows Over Dashboards
The ERP is the nervous system of the company. It is NOT a collection of database tables displayed on web pages.
- **Strict Auditing**: Every financial or operational change must leave an audit trail.
- **Workflow-Driven**: An invoice is not just "created" or "deleted." It moves through a lifecycle: `Draft` -> `Sent` -> `Viewed` -> `Partially Paid` -> `Paid` -> `Reconciled`. Your code must enforce these transitions.

## 2. Core Entities & Rules

### Client Lifecycle
- Clients are organizations, not just user accounts.
- Onboarding a client involves provisioning their workspace, wallet, and initial subscription state. This must be an atomic operation handled by a Service class.

### Invoice Lifecycle
- Invoices are immutable once finalized (Sent). Modifications require credit notes or explicit revision tracking.
- Do not perform simple `delete()` calls on finalized financial documents. They must be voided or archived.

### Wallet & Credits System (Client Ledger)
- **No Client Wallet Database Layer**: Do not use any client wallet table or model. Track client ledger balances entirely using direct transaction records.
- **Direct Transactions**: Link all transactions and cost transactions (expenses) directly to the Client (`client_id`) and/or Project (`project_id`).
- **Dynamic Balance**: Calculate the client's current balance on-the-fly as `Credits - Debits`.
- **Computational Locked Balance**: Locked balance is purely computational. Do not use manual lock/unlock transaction structures or persist locked funds in the database. Calculate it dynamically as the sum of `unpaidAmount()` for all outstanding/pending invoices (status is `sent` or `partial`) for that client.

## 3. Implementation Constraints
> [!WARNING]
> **No Direct Database Manipulation for Financials.** Never increment/decrement client balances via raw database columns. All financial adjustments must be recorded as explicit ledger transaction entries.

> [!IMPORTANT]
> **Idempotency.** Actions like "Process Payment" or "Charge Subscription" must be idempotent. If a queue worker fails halfway through, retrying the job must not result in a double charge.

> [!CAUTION]
> **Cross-Module Boundaries.**
> - **Write Operations**: ERP must NEVER import models from Booking, CRM, or other modules to create or update data. Always use the Event Bus (e.g., listen to `BookingConfirmed` instead of being called directly).
> - **Read Operations**: If the ERP dashboard must display data from another module (e.g., upcoming bookings), you MUST use the Defensive Read Pattern: `if (class_exists(\Modules\Booking\Models\Booking::class) && $user->hasModuleSubscription('booking')) { try { ... } catch { ... } }`.

## Summary Checklist
- [ ] Are financial transactions handled via ledger entries rather than raw balance updates?
- [ ] Is the client's locked balance calculated dynamically as the sum of unpaid amounts on their pending invoices?
- [ ] Does the UI reflect the entity's current lifecycle state (e.g., showing a "Void" button instead of "Delete" for a sent invoice)?
- [ ] Are state transitions enforced by the backend service layer?
- [ ] Are all cross-module data reads guarded by `class_exists()` and `hasModuleSubscription()`?
- [ ] Are cross-module write operations handled strictly via the Event Bus?
