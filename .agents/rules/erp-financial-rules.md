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
    $$\text{Income} = \sum \text{Transactions(Credit)} + \sum (\text{Transactions(Refund)} + \text{Transactions(Send)})$$
  - **Caution**: Since Refund and Send amounts are stored as negative values, adding them to the Credit sum correctly decreases the income. Do not subtract them directly (i.e. `Credit - (Refund + Send)`) because subtracting a negative value results in addition (`minus minus is plus`).
- **Exclusion of `Used`**: Do NOT sum or include transactions of type `Used` in the monthly/profit calculations. The `Used` type tracks internal wallet utilization (e.g. paying invoices using wallet balance) and including it would cause double-counting.

#### Income Calculation Example:
```php
$credits = Transaction::where('type', 'Credit')->whereMonth('created_at', $month)->sum('amount');

// Note: Refund and Send transactions have negative amounts (e.g., -150.00)
$deductions = Transaction::whereIn('type', ['Refund', 'Send'])->whereMonth('created_at', $month)->sum('amount');

// ✅ CORRECT: Adding the negative deductions correctly subtracts them
$monthlyIncome = $credits + $deductions;
```

---

### 4. Expense & Cost Summation
- **No Sub-types**: Expenses do not have sub-type classifications (like Credit, Refund, etc.) in the same transaction sense.
- **Cost Calculation**: Simply sum all entries in the expenses table (or transactions marked as expenses) to compute the total cost/expenditures:
  ```php
  $totalExpenses = Expense::whereMonth('created_at', $month)->sum('amount');
  ```
- **Net Profit**:
  $$\text{Net Profit} = \text{Monthly Income} - \text{Total Expenses}$$

---

### 5. Summary Checklist
- [ ] Does the Project/Invoice/Transaction use the `client->currency_id`?
- [ ] Is the User currency modification guarded by a `multi-currency` addon check?
- [ ] Are dashboard income metrics sourced directly from transaction sums?
- [ ] Are transactions of type `Used` excluded from profit/income calculations?
- [ ] Are expenses summed entirely without sub-type classification checks?
