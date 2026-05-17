# Database Schema Dictionary

## Overview

The ERP System schema is organized into four active domain modules: Core, ERP, Freelance, and Marketplace. All monetary amounts across the platform maintain strict double-currency conversion snapshots to prevent historical mutation when exchange rates change.

---

## 1. Core Module Tables (`Modules/Core`)

### `currencies`
Manages supported fiat currencies across the system.
- `id` (bigint, pk)
- `code` (varchar 3, unique) — e.g., "USD", "EGP"
- `name` (varchar) — e.g., "US Dollar"
- `symbol` (varchar nullable) — e.g., "$"
- `is_active` (boolean) — default true
- `timestamps`

### `exchange_rates`
Stores date-locked exchange rate snapshots between currency pairs.
- `id` (bigint, pk)
- `from_currency` (varchar 3)
- `to_currency` (varchar 3)
- `rate` (decimal 15,6)
- `effective_date` (date)
- `source` (enum: manual, api_auto)
- `created_by` (foreign key to users)
- `timestamps`
- *Composite Unique Index:* `(from_currency, to_currency, effective_date)`

### `site_settings`
Global system key-value configuration.
- `id` (bigint, pk)
- `key` (varchar, unique)
- `value` (text nullable)
- `group` (enum: general, currency, referral, service, withdrawal, points)
- `timestamps`

### `support_tickets`
Client helpdesk inquiries.
- `id` (bigint, pk)
- `client_id` (foreign key to users)
- `subject` (varchar)
- `status` (enum: open, pending, resolved, closed)
- `priority` (enum: low, medium, high, urgent)
- `timestamps`

### `conversations` & `conversation_participants`
Centralized polymorphic real-time messaging engine.
#### `conversations`
- `id` (bigint, pk)
- `conversable_type`, `conversable_id` (morphs) — links to marketplace orders, freelance contracts, support tickets
- `type` (enum: marketplace_order, freelance_contract, support_ticket)
- `status` (enum: open, closed, archived)
- `timestamps`

#### `conversation_participants`
- `id` (bigint, pk)
- `conversation_id` (foreign key to conversations)
- `user_id` (foreign key to users)
- `role` (enum: client, freelancer, seller, buyer, admin)
- `last_read_at` (timestamp nullable)
- `timestamps`

### `messages` & `message_attachments`
#### `messages`
- `id` (bigint, pk)
- `conversation_id` (foreign key to conversations)
- `sender_id` (foreign key to users)
- `body` (text nullable)
- `created_at` (timestamp)

#### `message_attachments`
- `id` (bigint, pk)
- `message_id` (foreign key to messages)
- `type` (enum: image, file)
- `path` (varchar)
- `mime_type` (varchar)
- `size_bytes` (bigint)
- `original_name` (varchar)
- `timestamps`

### Double-Entry Accounting Ledgers (`ledgers`, `accounts`, `journal_entries`, `journal_entry_lines`)
#### `ledgers`
- `id` (bigint, pk)
- `name` (varchar)
- `type` (varchar) — asset, liability, equity, revenue, expense
- `timestamps`

#### `accounts`
- `id` (bigint, pk)
- `ledger_id` (foreign key to ledgers)
- `name` (varchar)
- `code` (varchar, unique)
- `balance` (decimal 20,8)
- `currency_code` (varchar 3)
- `timestamps`

#### `journal_entries` & `journal_entry_lines`
- `journal_entries`: `id` (uuid pk), `reference_type`, `reference_id`, `description`, `date`, `timestamps`
- `journal_entry_lines`: `id` (bigint pk), `journal_entry_id` (uuid fk), `account_id` (fk), `debit` (20,8), `credit` (20,8), `amount`, `amount_currency`, `business_amount`, `business_currency`, `exchange_rate`, `exchange_rate_date`, `timestamps`

### `wallets` & `wallet_transactions`
Global platform stored-value accounts and immutable ledger history.
#### `wallets`
- `id` (bigint, pk)
- `owner_type`, `owner_id` (morphs)
- `context` (varchar) — default 'default'
- `balance` (decimal 20,8)
- `currency` (varchar 3)
- `timestamps`

#### `wallet_transactions` (Immutable Append-Only)
- `id` (bigint, pk)
- `wallet_id` (foreign key to wallets)
- `type` (enum: credit, debit)
- `amount` (decimal 20,8)
- `balance_before` (decimal 20,8)
- `balance_after` (decimal 20,8)
- `reference_type`, `reference_id` (polymorphic nullable)
- `description` (varchar)
- `created_at`, `updated_at` (timestamps)

### `audit_logs` & `impersonation_logs`
#### `audit_logs`
- `id`, `user_id`, `action`, `auditable_type`, `auditable_id`, `old_values` (json), `new_values` (json), `ip_address`, `user_agent`, `timestamps`

#### `impersonation_logs`
- `id`, `impersonator_id`, `impersonated_id`, `started_at`, `ended_at`, `ip_address`, `timestamps`

---

## 2. ERP Module Tables (`Modules/ERP`)

### `module_plans` & `user_subscriptions`
Subscribable plans for tenant accounting features.
#### `module_plans`
- `id`, `module` (enum: erp, freelance, marketplace), `name`, `price`, `billing` (monthly, yearly), `features` (json), `is_active`, `timestamps`

#### `user_subscriptions`
- `id`, `client_id`, `plan_id`, `status` (active, cancelled, expired), `started_at`, `expires_at`, `auto_renew`, `timestamps`

### `tenants` & `tenant_clients`
Multi-tenant core isolation tables.
#### `tenants`
- `id`, `user_id` (fk to users), `name`, `status` (active, suspended, cancelled), `trial_ends_at`, `subscription_ends_at`, `timestamps`

#### `tenant_clients`
- `id`, `tenant_id` (fk), `name`, `email`, `phone`, `address`, `currency` (varchar 3), `country_code` (varchar 2), `referral_code` (unique), `referred_by` (self-referencing fk), `timestamps`

### `invoices`, `invoice_items`, `timer_sessions`, `invoice_costs`
#### `invoices`
- `id` (bigint pk), `tenant_id` (fk), `invoice_number` (varchar), `client_id` (fk to tenant_clients), `status` (enum: draft, sent, partial, paid, cancelled, refunded)
- `amount`, `amount_currency`, `business_amount`, `business_currency`, `exchange_rate`, `exchange_rate_date`
- `discount_amount`, `tax_rate`, `tax_amount`, `due_date`, `issued_at`, `paid_at`, `notes`, `created_by`, `timestamps`
- *Composite Unique Index:* `(tenant_id, invoice_number)`

#### `invoice_items`
- `id`, `invoice_id`, `tenant_id`, `type` (enum: simple, quantity, timer), `title`, `description`, `unit_price`, `quantity`, `total`, `sort_order`, `timestamps`

#### `timer_sessions`
- `id`, `invoice_item_id`, `started_at`, `stopped_at`, `duration_seconds`, `started_by`, `stopped_by`, `note`, `timestamps`

#### `invoice_costs`
- `id`, `invoice_id`, `tenant_id`, `title`, `description`, `amount`, `amount_currency`, `business_amount`, `business_currency`, `exchange_rate`, `exchange_rate_date`, `payment_status` (unpaid, paid), `payment_source`, `paid_at`, `paid_by`, `note`, `timestamps`

### `client_wallets` & `client_wallet_transactions`
Isolated stored-value wallets specifically for tenant clients.
#### `client_wallets`
- `id`, `tenant_id`, `client_id` (fk to tenant_clients), `balance` (decimal 15,2), `currency` (varchar 3), `timestamps`
- *Composite Unique Index:* `(tenant_id, client_id)`

#### `client_wallet_transactions` (Immutable Append-Only)
- `id`, `tenant_id`, `wallet_id`, `type` (enum: invoice_issued, invoice_paid, invoice_refund, commission_earned, commission_paid, manual_credit, manual_debit, withdrawal_requested, withdrawal_paid, withdrawal_cancelled, cost_deducted), `direction` (debit, credit), `amount`, `amount_currency`, `business_amount`, `business_currency`, `exchange_rate`, `exchange_rate_date`, `balance_before`, `balance_after`, `reference_type`, `reference_id`, `note`, `created_by`, `created_at` (immutable)

### `expense_transactions` (Immutable Append-Only)
- `id`, `tenant_id`, `invoice_cost_id`, `invoice_id`, `client_id`, `type`, `direction`, `amount`, `amount_currency`, `business_amount`, `business_currency`, `exchange_rate`, `exchange_rate_date`, `balance_before`, `balance_after`, `note`, `created_by`, `created_at`

### `client_referrals` & `client_referral_earnings`
Multi-tier affiliate referral commission tracking.
#### `client_referrals`
- `id`, `tenant_id`, `referrer_id` (fk to tenant_clients), `referee_id` (fk to tenant_clients), `level` (tinyint 1 or 2), `status` (pending, active, cancelled), `timestamps`

#### `client_referral_earnings`
- `id`, `tenant_id`, `invoice_id`, `referrer_id`, `referee_id`, `level`, `amount`, `amount_currency`, `business_amount`, `business_currency`, `exchange_rate`, `exchange_rate_date`, `commission_rate`, `status` (pending, paid, cancelled), `timestamps`

### `recurring_entries` & `recurring_execution_logs`
#### `recurring_entries`
- `id`, `tenant_id` (nullable), `type` (income, expense), `title`, `description`, `amount`, `amount_currency`, `business_amount`, `business_currency`, `exchange_rate`, `exchange_rate_date`, `frequency` (daily, weekly, monthly, yearly), `starts_at`, `ends_at`, `next_run_at`, `last_run_at`, `status`, `is_active`, `timestamps`

#### `recurring_execution_logs` (Immutable)
- `id`, `recurring_entry_id`, `executed_at`, `amount`, `amount_currency`, `business_amount`, `business_currency`, `exchange_rate`, `exchange_rate_date`, `status` (success, failed), `note`, `created_at`

### `payment_methods` & `withdrawals`
#### `payment_methods`
- `id`, `tenant_id`, `client_id`, `type` (enum: bank_transfer), `is_default`, `status` (pending_review, approved, rejected), `rejection_note`, `reviewed_by`, `reviewed_at`, `bank_name`, `account_holder_name`, `account_number`, `iban`, `swift_code`, `bank_country`, `bank_currency`, `branch_name`, `notes`, `timestamps`

#### `withdrawals`
- `id`, `tenant_id`, `client_id`, `payment_method_id`, `status` (pending, approved, paid, rejected, cancelled), `amount`, `currency_code` (default USD), `business_amount`, `business_currency`, `exchange_rate`, `exchange_rate_date`, `balance_at_request`, `reviewed_by`, `reviewed_at`, `rejection_note`, `paid_by`, `paid_at`, `reference`, `proof_path`, `admin_notes`, `timestamps`

---

## 3. Freelance Module Tables (`Modules/Freelance`)

### `freelance_skills` & `freelance_user_skills`
- `freelance_skills`: `id`, `name`, `description`, `timestamps`
- `freelance_user_skills`: `id`, `user_id`, `skill_id`, `timestamps`

### `point_packages`
- `id`, `name`, `points` (int), `price` (decimal 20,8), `currency_code` (varchar 3), `timestamps`

### `freelance_jobs` & `freelance_job_skills`
#### `freelance_jobs`
- `id`, `client_id` (fk to users), `title`, `description`, `budget` (decimal 20,8), `currency_code`, `type` (fixed, hourly), `duration`, `status` (open, in_progress, completed, cancelled), `softDeletes`, `timestamps`

#### `freelance_job_skills`
- `id`, `job_id`, `skill_id`, `is_required` (boolean), `timestamps`

### `freelance_proposals` & `freelance_contracts`
#### `freelance_proposals`
- `id`, `job_id`, `freelancer_id`, `cover_letter`, `bid_amount`, `currency_code`, `status` (pending, accepted, rejected), `timestamps`

#### `freelance_contracts`
- `id`, `job_id`, `proposal_id`, `client_id`, `freelancer_id`, `amount`, `currency_code`, `status` (active, completed, disputed), `started_at`, `completed_at`, `timestamps`

### `point_transactions` (Immutable Append-Only)
- `id`, `user_id`, `points` (int), `type` (earned, spent, credit, debit), `description`, `reference_type`, `reference_id`, `timestamps`

---

## 4. Marketplace Module Tables (`Modules/Marketplace`)

### `marketplace_service_categories` & `marketplace_services`
- `marketplace_service_categories`: `id`, `name`, `slug` (unique), `description`, `timestamps`
- `marketplace_services`: `id`, `seller_id` (fk to users), `category_id`, `title`, `description`, `status` (draft, active, paused, banned), `is_featured`, `softDeletes`, `timestamps`

### `marketplace_packages`
- `id`, `service_id`, `name`, `description`, `price` (decimal 20,8), `currency_code` (varchar 3), `delivery_days`, `timestamps`

### `marketplace_orders` & `marketplace_reviews`
#### `marketplace_orders`
- `id`, `buyer_id`, `seller_id`, `package_id`, `amount`, `currency_code`, `commission_amount`, `status` (pending, processing, delivered, completed, cancelled, disputed), `delivered_at`, `completed_at`, `timestamps`

#### `marketplace_reviews`
- `id`, `order_id`, `reviewer_id`, `reviewee_id`, `rating` (int 1-5), `comment`, `timestamps`

### `marketplace_escrows`
Secure escrow vaults locking funds during active orders.
- `id`, `order_id` (fk to marketplace_orders)
- `buyer_wallet_transaction_id` (nullable bigint)
- `seller_wallet_transaction_id` (nullable bigint)
- `amount`, `amount_currency`, `business_amount`, `business_currency`, `exchange_rate`, `exchange_rate_date`
- `status` (enum: pending, held, released, refunded, disputed)
- `released_at`, `refunded_at`, `timestamps`
