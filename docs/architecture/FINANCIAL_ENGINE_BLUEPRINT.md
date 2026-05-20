# Financial Engine Blueprint

MuSoftware ERP handles finances via a strict Double-Entry Bookkeeping architecture to ensure funds are never magically generated or lost.

## Core Components

1. **Wallets (`Modules\Core\Models\Wallet`)**
   - Polymorphic entities that hold balances.
   - Owner types: `App\Models\User` (platform-level), `Modules\ERP\Models\Tenant` (agency/business), `Modules\ERP\Models\TenantClient` (end-customers).
   - Balance columns: `balance` and `locked_balance`.

2. **LedgerService (`Modules\Core\Services\LedgerService`)**
   - The heart of the financial engine.
   - It records transactions into `journal_entries` and `journal_entry_lines`.
   - Each transaction balances Debits and Credits against the Chart of Accounts.
   - When `LedgerService` logs a transaction mapped to a Wallet, it automatically updates the Wallet's balance.

3. **Invoices & Payments**
   - Invoices live in `Modules\ERP\Models\Invoice`.
   - When an invoice is paid (e.g., via `InvoicePaymentController`), funds move from the Client's Wallet or Payment Method to the Tenant's Wallet via the `LedgerService`.
   - Domain event `InvoicePaid` is fired to trigger Notifications and Activity Logging.

4. **Recurring Entries (`Modules\ERP\Models\RecurringEntry`)**
   - Scheduled income/expenses/invoices that auto-generate daily.
   - Processed by `ProcessRecurringEntries` command calling `RecurringService`.

## Flow of a Transaction
1. **Trigger:** User pays a $100 invoice.
2. **Controller:** Validates payment and calls `$walletService->payInvoice($invoice)`.
3. **Ledger:** `$ledgerService->recordTransaction()` calculates the Debits (Client Wallet down $100) and Credits (Tenant Revenue up $100).
4. **Database:** `journal_entries` created. 2 `journal_entry_lines` created. `Wallet` balances atomically updated.
5. **Events:** `WalletDebited`, `WalletCredited`, `InvoicePaid` fired.
6. **Side-effects:** `ActivityEventListener` writes audit logs; `NotificationEventListener` emails the receipt.
