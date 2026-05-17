# Architecture Overview

## System Design

```text
┌────────────────────────────────────────────────────────────────────────┐
│ Frontend (Inertia 2.0 + React 18 TypeScript + shadcn/ui + Tailwind v4) │
│ - AdminLayout, AuthenticatedLayout, ClientLayout, PublicLayout         │
│ - Real-time synchronization via Laravel Echo / WebSockets              │
└────────────┬───────────────────────────────────────────────────────────┘
             │ HTTP + WebSocket
┌────────────▼───────────────────────────────────────────────────────────┐
│ Laravel 12 (Modular Monolith)                                          │
│ ├── Modules/Core/                                                      │
│ │   ├── Double-Entry Accounting Ledgers (ledgers, accounts, journals)  │
│ │   ├── Currencies + Daily Exchange Rates                              │
│ │   ├── Global Wallets + Immutable Transactions                        │
│ │   ├── Polymorphic Chat (conversations, messages, attachments)        │
│ │   └── Audit & Impersonation Logging (audit_logs, impersonation_logs) │
│ ├── Modules/ERP/                                                       │
│ │   ├── Multi-tenancy (Column-based tenant_id on tenants, invoices)    │
│ │   ├── Tenant Clients (isolated client lists with client_wallets)     │
│ │   ├── Invoices (simple, quantity, live timer_sessions, costs)        │
│ │   ├── Referrals (2-level commission in client_referral_earnings)     │
│ │   ├── Recurring entries (cron scheduled recurring_execution_logs)    │
│ │   └── Withdrawals (bank_transfer payment_methods)                    │
│ ├── Modules/Freelance/                                                 │
│ │   ├── Skills + Job matching (freelance_skills, freelance_jobs)       │
│ │   ├── Points system (point_packages, immutable point_transactions)   │
│ │   └── Proposals & Contracts (freelance_proposals, contracts)         │
│ └── Modules/Marketplace/                                               │
│     ├── Services + Packages (marketplace_services, packages)           │
│     ├── Orders & Reviews (marketplace_orders, reviews)                 │
│     └── Escrow Vaults (marketplace_escrows locking wallet funds)       │
└────────────┬───────────────────────────────────────────────────────────┘
             │
┌────────────▼───────────────────────────────────────────────────────────┐
│ MySQL Database                                                         │
│ - Column-based multi-tenancy (tenant_id)                               │
│ - Immutable ledger tables (no updates/deletes)                         │
│ - Historical exchange rates (effective_date)                           │
│ - Double-entry UUID journal lines (journal_entry_lines)                │
└────────────────────────────────────────────────────────────────────────┘
```

## Multi-Tenancy Strategy

**Column-based isolation** (MySQL compatible)

Every ERP table (`tenant_clients`, `invoices`, `invoice_items`, `client_wallets`, `withdrawals`) has a `tenant_id` field:

```sql
invoices (id, tenant_id, invoice_number, ...)
-- Admin: tenant_id = null
-- Tenant: tenant_id = 5
```

Global Scope (automatically applied on all queries during authenticated tenant sessions):
```php
if (auth()->check() && auth()->user()->isTenant()) {
    $builder->where('tenant_id', auth()->user()->tenant->id);
}
```

**Benefits:**
- Simple to implement and maintain within a single database instance.
- Fully compatible with standard relational databases (MySQL 8.0+).
- Easy to audit and debug.
- Allows super-admins to run cross-tenant aggregations and oversight effortlessly.

**Gotchas & Rules:**
- All tenant-scoped models extend a base `TenantModel` or use a global scope trait.
- When an admin intentionally needs to query across tenants, use `withoutGlobalScope()`.

## Multi-Currency & Double-Entry Accounting Architecture

The platform enforces absolute financial rigor through a dual accounting system:
1. **Core Accounting Ledgers (`Modules/Core`):** Implements standard double-entry bookkeeping (`ledgers`, `accounts`, `journal_entries`, `journal_entry_lines`). Journal entry lines use UUIDs and track both debit and credit amounts alongside multi-currency snapshots.
2. **ERP Invoicing Snapshots (`Modules/ERP`):** Every invoice, cost, and transaction stores complete conversion snapshots.

Every monetary transaction across the platform (`invoices`, `journal_entry_lines`, `client_wallet_transactions`, `marketplace_escrows`) includes 6 fields to ensure auditability and prevent historical mutation when live exchange rates fluctuate:

```text
amount              ← Original currency amount (e.g., 5000.00)
amount_currency     ← Original currency code (e.g., "EGP")
business_amount     ← Converted to base/business currency (e.g., 103.09)
business_currency   ← USD (default base currency)
exchange_rate       ← Rate frozen at exact transaction timestamp (e.g., 48.50)
exchange_rate_date  ← Date when the exchange rate was fetched/applied
```

## Wallet & Escrow Ledger System

**Immutable transaction ledgers**

The platform maintains strict separation between global wallets and tenant client wallets:
- **Global Wallets (`Modules/Core`):** `wallets` and `wallet_transactions` manage general platform accounts.
- **Tenant Wallets (`Modules/ERP`):** `client_wallets` and `client_wallet_transactions` manage balances specifically for tenant clients.
- **Escrow Vaults (`Modules/Marketplace`):** `marketplace_escrows` securely locks wallet funds during active marketplace orders until released or refunded upon dispute.

```text
client_wallet_transactions table:
  - NEVER update or delete records under any circumstances.
  - Every transaction stores both balance_before and balance_after.
  - Type categories: invoice_issued, invoice_paid, commission_earned, withdrawal_requested, manual_credit, manual_debit.
  - Reference: Polymorphic association pointing to the triggering entity (Invoice, Withdrawal, Order).
  - created_at ONLY (no updated_at column exists in the schema).
```

**Correction Workflow:**
If a transaction was recorded erroneously, do not execute a SQL `DELETE` or `UPDATE`. Instead, create a compensating reversal transaction (e.g., credit reversal) with a detailed audit note linking to the original transaction ID.

## Real-time Polymorphic Chat

Powered by Laravel Echo and WebSockets.

**Architecture:**
The `conversations` table in Core dynamically morphs to any entity via `conversable_type` and `conversable_id`.
- Supported types: `marketplace_order`, `freelance_contract`, `support_ticket`.
- Participants (`conversation_participants`) are explicitly assigned roles: `client`, `freelancer`, `seller`, `buyer`, `admin`.
- Messages (`messages`) and files (`message_attachments`) are securely synchronized across participants.

## Authentication & Authorization Flow

1. **Authentication:** Managed via Laravel Breeze using secure HTTP-only session cookies.
2. **Authorization:** Governed by Spatie Laravel Permission (`spatie/laravel-permission`) for fine-grained role and permission management.
3. **State Transitions:** Governed by Spatie Laravel Model States (`spatie/laravel-model-states`) for rigorous state machine validation across invoices and orders.
4. **Impersonation:** Super-admins can temporarily log into any client account for support, with all actions strictly recorded in `impersonation_logs`.

## Request & Response Lifecycle

```text
User Interaction in React 18 (TypeScript .tsx)
       │
       ▼
useForm / Inertia::visit() sends HTTP POST/PATCH/GET
       │
       ▼
Laravel 12 Route & Middleware (Auth, Ziggy, TenantScope, Impersonate)
       │
       ▼
FormRequest Validation (Server-side) ──(Invalid)──► Redirect back with `errors` prop
       │
   (Valid)
       ▼
Controller Action & Domain Service Execution
       │
       ▼
Database Mutation & Audit / Impersonation Logging
       │
       ▼
Inertia::render() / Redirect
       │
       ▼
React Component Re-renders with updated Page Props
```

## Module Communication

Modules are logically separated in `Modules/` but run in the same runtime monolith. They share services via Laravel's Service Container (Dependency Injection).

```php
// Example: Marketplace module securely debiting client wallet via ERP module service
namespace Modules\Marketplace\Services;

use Modules\ERP\Services\WalletService;

class OrderEscrowService
{
    public function __construct(protected WalletService $walletService) {}

    public function lockEscrow($buyer, $totalAmount, $order)
    {
        // Inter-module invocation
        $this->walletService->debit(
            $buyer->client_wallet_id, 
            $totalAmount, 
            'USD', 
            'Marketplace order escrow hold #'.$order->id, 
            $order
        );
    }
}
```

## Task Scheduling & Background Processing

Configured in `routes/console.php` or `app/Console/Kernel.php` running via standard cron:

```php
Schedule::command('currency:fetch-rates')->dailyAt('00:00');
Schedule::command('erp:process-recurring')->dailyAt('01:00');
Schedule::command('freelance:expire-jobs')->dailyAt('02:00');
```

## Admin Impersonation & Audit Trail

To ensure absolute security and accountability when administrators assist clients:
- When an admin initiates impersonation, a permanent entry is written to `impersonation_logs` storing `impersonator_id`, `impersonated_id`, `started_at`, and `ip_address`.
- All significant database mutations across the platform automatically write an immutable audit trail record to `audit_logs` capturing `user_id`, `action`, `auditable_type`, `old_values`, and `new_values`.
