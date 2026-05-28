---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Enforce strict ERP guidelines for base currency, client currency overrides, transaction-based income metrics, and expense summation.
---

# Rule: ERP Currency and Transaction Rules

## Problem Statement
Calculating financial performance or configuring currencies incorrectly leads to data inconsistency, wrong profit-and-loss reports, and issues with user billing. In the ERP system, we must enforce a strict, client-centric currency flow and compute monthly income directly from ledger transactions using clear type exclusions.

---

## Rules & Guidelines

### 1. ERP Base Currency vs. User Currency
- The ERP has a default system-wide base currency.
- **User Default**: Every ERP User (Tenant/Business Owner) inherits this base currency by default.
- **Multi-Currency Addon Requirement**: A User can only change their default user currency if they have an active subscription/purchase of the `multi-currency` addon.
- **Code Check**: Before allowing a User to change their default currency, always check addon access:
  ```php
  if (!$user->hasModuleSubscription('multi-currency')) {
      throw new \Exception(__('errors.multi_currency_addon_required'));
  }
  ```

---

### 2. Client-Centric Currency Flow
In the ERP system, the **Client** (the client of the User, not the User/Tenant themselves) defines the currency boundary.
- **Projects**: Every project must use the client's currency.
- **Invoices**: Every invoice must use the client's currency.
- **Transactions**: Every client transaction (wallet payments, ledger adjustments) must use the client's currency.
- **Constraint**: Do not fallback to the User's default currency or the base currency if a Client currency is configured. Ensure the relationship is loaded and validated:
  ```php
  // Project creation example
  $project->currency_id = $client->currency_id;
  
  // Invoice creation example
  $invoice->currency_id = $client->currency_id;
  ```

#### Multi-Currency Transaction Conversions (Exchange Rates)
When the ERP base business currency is different from the Client's currency (e.g., ERP base currency is `EGP` and the Client's currency is `USD`):
- The transaction amount must be recorded in the Client's currency (`amount` in `USD`).
- The `business_amount` must be converted to the ERP system's base currency using the exchange rate corresponding to the **date of the transaction**.
- **Exchange Rates Table**: The system uses the `currencies_exchanges` table (represented by the `App\Models\CurrenciesExchange` model) to store daily historical rates.
- **Lookup Method**: Always fetch the exchange rate using `\App\Models\CurrenciesExchange::RateByDate($date, $amount, $fromCurrencyId, $toCurrencyId)`. This ensures that we lock in the rate at the time/date of the transaction rather than using a dynamic real-time live rate that would retroactively alter past accounting metrics.

```php
// Example: Converting transaction to business currency on save/boot event
$date = $transaction->created_at ?? now();
$transaction->business_amount = \App\Models\CurrenciesExchange::RateByDate(
    $date,
    $transaction->amount,
    $transaction->currency_id,
    \App\Models\AdminSettings::business_currency()
);
```

---

### 3. Transactions as the Single Source of Truth for Income
- **Metric Source**: Monthly income and overall income metrics must be compiled solely from the **transactions** system, never directly from invoices.
- **Transaction Types**: Transactions must belong to one of these types:
  - `Credit`
  - `Refund`
  - `Send`
  - `Used`
- **Income Formula**:
  - If Refund and Send transactions are stored as negative values:
    $$\text{Income} = \sum \text{Transactions(Credit).business\_amount} + \sum (\text{Transactions(Refund).business\_amount} + \text{Transactions(Send).business\_amount})$$
  - **Multi-Currency Normalization**: You **MUST** sum the `business_amount` (normalized base currency amount) rather than the local/client `amount`. Summing different raw currencies (e.g. adding EGP directly to USD) is strictly forbidden.
  - **Caution**: Since Refund and Send amounts are stored as negative values, adding them to the Credit sum correctly decreases the income. Do not subtract them directly (i.e. `Credit - (Refund + Send)`) because subtracting a negative value results in addition (`minus minus is plus`).
- **Exclusion of `Used`**: Do NOT sum or include transactions of type `Used` in the monthly/profit calculations. The `Used` type tracks internal wallet utilization (e.g. paying invoices using wallet balance) and including it would cause double-counting.

#### Income Calculation Example:
```php
// ✅ CORRECT: Summing business_amount (normalized base currency)
$credits = Transaction::where('type', 'Credit')
    ->whereMonth('created_at', $month)
    ->sum('business_amount');

// Note: Refund and Send transactions have negative amounts (e.g., -150.00 in business currency)
$deductions = Transaction::whereIn('type', ['Refund', 'Send'])
    ->whereMonth('created_at', $month)
    ->sum('business_amount');

// ✅ CORRECT: Adding the negative deductions correctly subtracts them
$monthlyIncome = $credits + $deductions;
```

---

### 4. Expense & Cost Summation
- **No Sub-types**: Expenses do not have sub-type classifications (like Credit, Refund, etc.) in the same transaction sense.
- **Cost Calculation**: Simply sum all entries using `business_amount` to compute the total cost/expenditures:
  ```php
  // ✅ CORRECT: Summing business_amount for currency consistency
  $totalExpenses = Expense::whereMonth('created_at', $month)->sum('business_amount');
  ```
- **Net Profit**:
  $$\text{Net Profit} = \text{Monthly Income} - \text{Total Expenses}$$

### 5. Client & Project Direct Ledger Linking
- **No Client Wallet Layer**: Do not use an intermediate client wallet model or table (such as `ClientWallet` or `erp_client_wallets`) to track funds or intermediate states.
- **Direct Foreign Keys**:
  - Every transaction (`Transaction`) and cost transaction (`CostTransaction` / expense) must be linked directly to the **Client** (using `client_id` referencing `erp_tenant_clients`) and/or **Project** (using `project_id` referencing `projects`).
  - Models must define direct relationships to the client:
    ```php
    public function client(): BelongsTo
    {
        return $this->belongsTo(\Modules\ERP\Models\Client::class, 'client_id');
    }
    ```
- **Dynamic Balance & Computational Locked Balance**:
  - Compute client or project financial health directly by summing their associated transactions rather than pulling a static `balance` from a wallet model.
  - **Locked Balance is purely computational**: Do not use manual lock/unlock transaction structures or persist locked funds in the database. Instead, calculate the locked balance dynamically as the sum of `unpaidAmount()` for all outstanding/pending invoices (status is `sent` or `partial`) for that client.
  - **Locked Balance Formula**:
    $$\text{Locked Balance} = \sum \text{Client's Invoices(Unpaid).unpaidAmount()}$$

---

### 6. Summary Checklist
- [ ] Does the Project/Invoice/Transaction use the `client->currency_id`?
- [ ] Is the User currency modification guarded by a `multi-currency` addon check?
- [ ] Are dashboard income metrics sourced directly from transaction sums?
- [ ] Are transactions of type `Used` excluded from profit/income calculations?
- [ ] Are expenses summed entirely without sub-type classification checks?
- [ ] Are all transactions and cost transactions linked directly to the client/project (without an intermediate wallet model)?
- [ ] Is the client's locked balance calculated dynamically as the sum of unpaid amounts on their pending invoices?

