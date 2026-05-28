---
description: Rules for calculating and formatting wallet balances to avoid discrepancies between total balance and available balance.
---


# Wallet Balance Calculation

When returning user balances in APIs (Resources, Controllers) or rendering them in React components, ALWAYS follow these strict rules to avoid reporting a `$0.00` balance or showing money that is practically blocked:

## 1. Always Use `available_balance()`
Never use `$user->user_balance` directly unless you specifically need the "Total Raw Wallet" (which does not factor in unpaid invoices).
For all UI representations (e.g., DataTables, Profile Wallets, Available Funds), use `$user->available_balance()`.

This is critical because `available_balance()` automatically deduces unpaid invoices and schedules so the client doesn't overspend their wallet.

## 2. Flat API Structures
Do NOT return deeply nested objects for simple model attributes unless it's a relationship.

**Wrong (Anti-pattern):**
```php
// UserResource.php
'wallet' => [
    'balance' => $this->available_balance(),
    'currency' => $this->preferred_currency,
]
```

**Correct:**
```php
// UserResource.php
'available_balance' => (float) $this->available_balance(),
'currency' => $this->currency_name(),
```

## 3. Query Efficiency (Select Statements)
The `available_balance()` method internally calls `$this->balance()`, which relies on `$this->user_balance` and `$this->currency_id`.
If you are iterating over a paginated list of users (e.g., in an Index Controller), ensure your `select()` statement explicitly fetches these columns:
```php
User::query()
    ->select('id', 'name', 'user_balance', 'currency_id', ...) // REQUIRED!
    ->paginate();
```
Failure to select `user_balance` will result in the property being `null`, evaluating to `0.00` everywhere.

## 4. Frontend Rendering (React/Inertia)
Always access the flat structure with a fallback to `0` and provide the currency explicitly.

**Wrong:**
```javascript
{client.wallet ? formatCurrency(client.wallet.balance, client.wallet.currency) : formatCurrency(0, 'USD')}
```

**Correct:**
```javascript
{formatCurrency(client.available_balance || 0, client.currency || 'USD')}
```

## 5. Currency Formatting (Negative Balances)
When formatting negative currency values in JavaScript (e.g., using `formatMoney`), **the minus sign MUST always be placed OUTSIDE the currency symbol**.

**Wrong:** `E£-415.00`
**Correct:** `-E£ 415.00` (or `-e£415.00`)

### Duplicate Minus Issue
Never manually prepend `-` to a currency string if the underlying number might already be negative.
If you conditionally render `+` or `-` based on transaction types, ensure you format the absolute value:

**Wrong (Duplicate Minus):**
```javascript
{tx.type === 'credit' ? '+' : '-'}{formatCurrency(tx.amount, wallet.currency)} // Results in -e£-415.00
```

**Correct:**
```javascript
{formatCurrency(tx.type === 'credit' ? Math.abs(tx.amount) : -Math.abs(tx.amount), wallet.currency)}
```
The `formatMoney` helper (in `utils.ts`) is specifically designed to handle negative amounts correctly and place the minus sign before the symbol automatically.
