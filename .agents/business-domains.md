# Business Domains & Logic Rules

## 1. Multi-Tenant Architecture
- The system is multi-tenant. The `User` is the business owner (Tenant).
- Data must always be scoped to the Tenant.

## 2. ERP Financial Engine
- **Client-Centric Currency**: Invoices and transactions use the Client's currency.
- **Business Amount**: All transactions normalize to the Tenant's base currency via daily exchange rates in the `business_amount` column.
- **Income Calculation**: Net income = SUM(Received transactions) + SUM(Refunded + Sent). The `Used` type is strictly excluded.

## 3. Subscriptions & Addons
- Replaced legacy `plan_id`. Uses `user_subscriptions` table (`module_id`, `status`).
- Access is checked via `$user->hasModuleSubscription('addon-name')`.
