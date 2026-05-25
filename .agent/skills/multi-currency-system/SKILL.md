---
name: Multi-Currency System
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
