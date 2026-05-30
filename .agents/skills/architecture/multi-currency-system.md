# Multi-Currency System Rules

## Purpose
Defines how financial transactions handle multiple currencies correctly based on the ERP financial rules.

## Core Rules
1. **Client Currency Boundary**: Invoices and projects use the Client's currency (`client->currency_id`).
2. **Business Currency Normalization**: Store the transaction in the Client's currency (`amount`) AND the converted base currency (`business_amount`).
3. **Daily Rates**: Use `\App\Models\CurrenciesExchange::RateByDate()` to convert. Never use live dynamic rates for past transactions.

## Anti-patterns
- Hardcoding `USD` or `EGP`.
- Summing local `amount` across different currencies. Always sum `business_amount`.
