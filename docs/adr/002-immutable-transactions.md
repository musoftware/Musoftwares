# ADR 002 — Immutable Transaction Ledgers

## Status: Accepted

## Context

Financial accounting systems, client wallets, and marketplace escrow vaults require absolute auditability. Standard database designs frequently update a single `balance` column or delete erroneous records directly when adjustments are needed.

Options considered:
1. **Direct Balance Mutations:** Update the user's wallet balance directly (`UPDATE wallets SET balance = balance + 500 WHERE id = 1`).
2. **Standard Transaction Logging:** Maintain a history table where records can be edited or deleted if mistakes occur.
3. **Immutable Append-Only Transaction Ledgers:** Maintain an append-only ledger where rows can never be updated or deleted under any circumstances.

## Decision

Use **Immutable Append-Only Transaction Ledgers**.

## Rationale

- **Absolute Audit Compliance:** In financial and ERP environments, deleting or overwriting historical transactions destroys audit trails and violates international accounting standards (GAAP/IFRS).
- **Fraud Prevention:** Prevents rogue actors or compromised admin accounts from altering historical financial records or erasing evidence of unauthorized withdrawals.
- **Flawless Reconciliation:** Because every ledger row records both `balance_before` and `balance_after`, developers can programmatically recalculate and verify the exact mathematical integrity of any client wallet from day one to the present.

## Consequences

- **No `updated_at` Column:** Ledger tables (`client_wallet_transactions` and `expense_transactions`) are built without an `updated_at` column to physically prevent update queries in Eloquent.
- **Reversals Required:** If an admin issues an incorrect credit transaction, they cannot delete the row. They must issue a compensating debit reversal transaction with an audit note linking to the original transaction ID.
- **Database Growth:** Ledger tables grow continuously over time. We mitigate index degradation by enforcing strict integer foreign keys and partitioning high-volume tables by fiscal year.

## See Also

- `Modules/ERP/Models/ClientWalletTransaction.php`
- `Modules/ERP/Services/WalletService.php`
