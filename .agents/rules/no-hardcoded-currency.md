---
trigger: always_on
glob: "**/*.{php,ts,tsx,js,jsx}"
description: Never use hardcoded currency in invoices, expenses, or transactions. Always use dynamic currency models or configuration.
---

# Rule: Never Use Hardcoded Currency in Invoices, Expenses, or Transactions

## Problem Statement
Hardcoding currency codes (e.g., `'USD'`, `'EGP'`, `'SAR'`) or symbols (e.g., `'$'`, `'£'`) in invoices, expenses, or transaction logic violates the multi-currency architecture. It leads to incorrect dashboard calculations, broken formatting when local currencies change, and hard-to-maintain code.

## Rules & Guidelines

### 1. Database & Model Level
- **Do NOT hardcode currency codes** as fallback values in queries, migrations, or database updates.
- **Always use `currency_id`** referencing the `currencies` table instead of raw currency strings.
- Rely on the `currency` relationship of the model (`$invoice->currency`, `$transaction->currency`, etc.).

### 2. Controller & Backend Calculations
- When computing business-level aggregates (e.g., total expenses, total sales), never assume a default currency is `'USD'` or `'EGP'`.
- Retrieve the configured business currency using:
  ```php
  \App\Models\AdminSettings::business_currency()
  ```
  or the appropriate settings helper.
- For local transactions, use the transaction's specific `currency_id` or the user's associated wallet/client currency.

### 3. Frontend & UI Display
- **Never** prefix or suffix amounts with hardcoded symbols or codes (e.g., `value + " EGP"` or `"$ " + value`).
- Always pass the currency object (containing `currency` and `symbol`) from the backend to the frontend.
- Format all monetary values dynamically using currency formatters/helpers that accept the currency model/property as an argument:
  ```jsx
  // ❌ INCORRECT (Hardcoded)
  <span>$ {amount}</span>
  <span>{amount} EGP</span>

  // ✅ CORRECT (Dynamic)
  <span>{formatCurrency(amount, currency)}</span>
  ```

### 4. Transactions, Invoices, and Expenses Normalization
- Always map both layers (Client currency and Business currency) to the frontend:
  ```php
  [
      'amount' => $this->amount,
      'currency' => $this->currency_id ?? $this->currency,
      'business_amount' => $this->business_amount ?? $this->business_total(),
      'business_currency' => \App\Models\AdminSettings::business_currency(),
  ]
  ```

### 5. Never Use Fallback Currencies (No Fallbacks Policy)
- **Do NOT provide silent default fallbacks** (e.g. `?? 'USD'` or `?? 'EGP'`) in controllers, models, resources, database migrations, or helper functions.
- If a currency configuration or model association is missing or null, the application **MUST NOT** silently fall back to a hardcoded code/symbol. Instead, let the operation fail loudly (throw an exception or return a validation error) so the configuration issue is identified and resolved immediately.
- Never write code like:
  ```php
  // ❌ INCORRECT (Silent Fallback)
  $currencyName = $transaction->currency?->currency ?? 'USD';
  ```
  Instead, ensure the relation is required or explicitly handle the missing state:
  ```php
  // ✅ CORRECT (No Fallback / Fail Loud)
  if (!$transaction->currency) {
      throw new \Exception("Transaction {$transaction->id} is missing an associated currency relation.");
  }
  $currencyName = $transaction->currency->currency;
  ```

