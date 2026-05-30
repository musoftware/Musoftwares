# ERP Domain Business Rules

## Core Operational Logic
1. **Transactions as Source of Truth**: Compute income ONLY from the transactions table, never directly from invoices.
2. **Locked Balance**: Computed dynamically by summing unpaid/pending invoices. Do not persist locked balance in DB.
3. **No Intermediate Wallets**: Link transactions and expenses directly to the Client/Project using foreign keys.
