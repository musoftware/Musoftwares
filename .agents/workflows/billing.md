---
description: The financial engine, subscription models, and wallet credit system.
---


# Billing System

This skill covers the rules for handling money, subscriptions, and wallet credits within Musoftware.

## Activation Conditions
This skill automatically applies when you are:
- Integrating Stripe, PayPal, or crypto payment gateways.
- Modifying the Wallet ledger system.
- Implementing subscription tiers.
- Handling webhooks from payment providers.

## 1. Single Source of Truth
- The payment gateway (e.g., Stripe) is the ultimate source of truth for subscription status.
- Musoftware caches this status locally for performance but must rely on Webhooks to stay synchronized.

## 2. Wallet Ecosystem
- Users purchase plugins or usage time using a unified "Credits" system.
- Credits represent real money. Any deduction must be wrapped in a database transaction (`DB::transaction`).

## 3. Points & Virtual Currencies
- Points packages (e.g., `PointPackage`) often define a fixed `price` in a local currency (e.g., EGP).
- When purchasing custom amounts, `tiers` must be provided (containing `min`, `max`, `price_per_point`, `discount_percent`) to the frontend to calculate pricing dynamically.
- When deducting from a user's `Wallet`, the cost MUST be converted to the wallet's native currency (e.g., using `CurrenciesExchange`) before checking for sufficient balance.
- If the wallet balance is insufficient, gracefully fallback to an external payment gateway (e.g., Kashier via `Inertia::location`).

## 3. Webhook Handling
- Webhook endpoints must be completely decoupled from the UI.
- They must verify the signature of the incoming payload.
- Webhook jobs should be dispatched to a dedicated `billing` queue to avoid slowing down the main application queue.

## Summary Checklist
- [ ] Are wallet deductions wrapped in database transactions?
- [ ] Is the webhook signature verified?
- [ ] Are billing jobs isolated to their own queue?
- [ ] Do Point purchases correctly calculate prices based on tiers before deducting from the wallet?
- [ ] Is currency conversion properly applied between the point package currency (e.g., EGP) and the user's wallet currency?
