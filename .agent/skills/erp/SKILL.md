---
name: ERP & Operational Workflows
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

### Wallet & Credits System
- The Wallet acts as a ledger.
- All additions and deductions to a user's wallet must be recorded as explicit Ledger Transactions (Credits / Debits) with reference IDs tying them back to the action that caused the transaction (e.g., "Purchased Plugin X").

## 3. Implementation Constraints
> [!WARNING]
> **No Direct Database Manipulation for Financials.** Never increment/decrement balances directly via Eloquent updates without writing a corresponding ledger entry. Use the dedicated Wallet/Ledger Services.

> [!IMPORTANT]
> **Idempotency.** Actions like "Process Payment" or "Charge Subscription" must be idempotent. If a queue worker fails halfway through, retrying the job must not result in a double charge.

## Summary Checklist
- [ ] Are financial transactions handled via ledger entries rather than raw balance updates?
- [ ] Does the UI reflect the entity's current lifecycle state (e.g., showing a "Void" button instead of "Delete" for a sent invoice)?
- [ ] Are state transitions enforced by the backend service layer?
