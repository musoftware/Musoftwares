---
name: Billing & Monetization
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

## 3. Webhook Handling
- Webhook endpoints must be completely decoupled from the UI.
- They must verify the signature of the incoming payload.
- Webhook jobs should be dispatched to a dedicated `billing` queue to avoid slowing down the main application queue.

## Summary Checklist
- [ ] Are wallet deductions wrapped in database transactions?
- [ ] Is the webhook signature verified?
- [ ] Are billing jobs isolated to their own queue?
