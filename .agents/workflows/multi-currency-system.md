---
description: Core guidelines for handling dual-currency architecture (Client vs Business currencies) in transactions and invoices.
---


# Multi-Currency System Rules

Musoftware operates on a strictly multi-currency foundation. Every financial record (Transactions, Invoices, Wallet Balances, Costs) fundamentally operates with two distinct layers of currency simultaneously. 

**You MUST remember this duality whenever working with financial data.**

## 1. Client/Local Currency Layer
This is the currency associated directly with the user/client's wallet or specific transaction (e.g., EGP, SAR, AED).
- **`amount` / `total` / `paid`**: The raw numeric values in the local currency.
- **`currency` / `currency_id`**: The ID or code representing the local currency.
- **Frontend Display**: Always display this to the user, typically via the `formatCurrency(amount, currency)` helper in React, or the backend helpers.

## 2. Business Currency Layer
This is the base or global currency used for internal accounting, cross-currency aggregations, and platform-wide analytics (typically USD).
- **`business_amount` / `business_total()`**: The value computationally converted to the business currency at the time of the transaction based on historical exchange rates.
- **Business Currency ID**: Usually retrieved via `\App\Models\AdminSettings::business_currency()` or explicitly stored.
- **Frontend Display**: In Admin dashboards or lists, this is often displayed alongside or instead of the local currency, especially when summing values across different clients.

## Core Rules

### Rule 1: Never Hardcode Currency Strings
NEVER use hardcoded string values like `'currency' => 'EGP'` in your migrations, factories, or models. The main system has a `currencies` table and a corresponding `App\Models\Currency` model. You MUST always use a `currency_id` column that references the main currencies table instead of a raw string.

### Rule 2: Never Mix Currencies
Never sum raw `amount` values across different invoices or transactions without ensuring they share the same `currency_id`. If you need to sum data across multiple clients, you MUST use the `business_amount` or `business_total` fields.

### Rule 2: Always Expose Both Sets to the Frontend
When creating an API Resource (e.g. `TransactionResource` or `InvoiceResource`) or mapping data for the frontend, **always** provide both layers:
```php
return [
    // Client Currency Layer
    'amount' => $this->amount, // or $this->total() for invoices
    'currency' => $this->currency_id ?? $this->currency,
    
    // Business Currency Layer
    'business_amount' => $this->business_amount ?? $this->business_total(),
    'business_currency' => \App\Models\AdminSettings::business_currency(),
];
```

### Rule 3: Transactions always have two sets
The `Transaction` model automatically calculates `business_amount` and flags `business_calculated = true` via its boot events. Always rely on these pre-calculated fields rather than re-calculating them on the fly to preserve historical exchange rates.

### Rule 4: Always Display Currency Next to Every Monetary Amount (CRITICAL)
**Any number that represents money MUST always be displayed with its currency symbol or code directly next to it.** A bare number like `4,907.64` displayed without a currency label is FORBIDDEN in any UI — admin or client-facing.

**Why:** A number without a currency is ambiguous. `4,907.64` could be USD, EGP, EUR, or any other currency. This causes misreading of financial data and is a UX failure.

#### Frontend Rule
Never render a monetary amount using a plain number formatter:
```jsx
// ❌ WRONG — currency not shown
<span>{amount.toLocaleString()}</span>
<span>{new Intl.NumberFormat('en-US').format(amount)}</span>

// ✅ CORRECT — always include currency
<span>$ {amount.toLocaleString()}</span>
<span>{amount.toLocaleString()} EGP</span>
```

When building formatters, always accept and use the currency symbol/code:
```jsx
// ✅ CORRECT pattern for a shared formatter
function fmtBiz(amount, biz = null) {
    const formatted = new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(parseFloat(amount));
    const symbol = biz?.symbol ?? '';
    const code   = biz?.code   ?? '';
    // Symbol before number (e.g. "$ 4,907.64"), code after if no symbol (e.g. "4,907.64 EGP")
    return symbol ? `${symbol} ${formatted}` : code ? `${formatted} ${code}` : formatted;
}
```

#### Backend Rule
When passing financial data to the frontend via Inertia or API Resources, always include the currency info alongside the amount:
```php
// ✅ CORRECT — always send currency data with the amount
return [
    'amount'          => $this->amount,
    'currency_code'   => $this->currencyModel?->currency ?? '—',
    'currency_symbol' => $this->currencyModel?->symbol   ?? '',
    // For business-currency-normalized amounts, also send the business currency:
    'business_amount'   => $this->business_amount,
    'business_currency' => [
        'code'   => $currencyMap[$bizId]['code'],
        'symbol' => $currencyMap[$bizId]['symbol'],
    ],
];
```

#### Summary Checklist
- [ ] Is every monetary number accompanied by a currency symbol or code in the UI?
- [ ] Are aggregate/normalized amounts (e.g., page-wide KPIs in business currency) sent with `business_currency` containing `code` and `symbol`?
- [ ] Is the frontend formatter receiving the currency info, not just the raw number?
