# Musoftware Platform — Database Intelligence

> **Type**: Complete Schema Map, ERD, and Integrity Analysis  
> **Database Driver**: SQLite (dev) / MySQL-compatible (prod)  
> **Migration Count**: 23 base + 6 ERP + 7 Core + 5 Booking + 1 Freelance + 1 Intelligence = 43+ migrations

---

## 1. Schema Ownership Map

```
┌────────────────────────────────────────────────────────────────┐
│ Namespace              │ Tables Owned                          │
├────────────────────────┼───────────────────────────────────────┤
│ database/migrations/   │ users, cache, jobs, notifications,    │
│ (root)                 │ permissions, kyc_documents,           │
│                        │ user_financials, wallet_transfers,    │
│                        │ tenant_files, user_notes, projects,   │
│                        │ serial_* tables, personal_access_tokens│
│                        │ tenant_notes                          │
├────────────────────────┼───────────────────────────────────────┤
│ Modules/Core           │ currencies, exchange_rates,           │
│                        │ site_settings, support_tickets,       │
│                        │ conversations, conversation_participants│
│                        │ messages, message_attachments,        │
│                        │ ledgers, accounts, journal_entries,   │
│                        │ journal_entry_lines, wallets,         │
│                        │ wallet_transactions, audit_logs,      │
│                        │ impersonation_logs, admin_notes,      │
│                        │ point_transactions (Core), referrals, │
│                        │ referral_earnings (Core global),      │
│                        │ activity_events                       │
├────────────────────────┼───────────────────────────────────────┤
│ Modules/ERP            │ tenants, tenant_clients, client_wallets│
│                        │ invoices, invoice_items, invoice_costs │
│                        │ payment_methods, recurring_entries,   │
│                        │ recurring_entry_logs,                 │
│                        │ recurring_execution_logs,             │
│                        │ user_subscriptions, module_plans,     │
│                        │ subscription_invoices, erp_tasks,     │
│                        │ erp_todo_items, client_notes,         │
│                        │ withdrawals, withdrawal_requests,     │
│                        │ erp_wallet_transactions,              │
│                        │ referrals (ERP), referral_earnings(ERP)│
│                        │ expense_transactions, timer_sessions  │
├────────────────────────┼───────────────────────────────────────┤
│ Modules/Freelance      │ freelance_jobs, job_skills, proposals,│
│                        │ contracts, skills, freelance_user_skills│
│                        │ point_packages, point_transactions(FL)│
├────────────────────────┼───────────────────────────────────────┤
│ Modules/Marketplace    │ service_categories, services,         │
│                        │ service_packages, service_orders,     │
│                        │ service_reviews, marketplace_escrows  │
├────────────────────────┼───────────────────────────────────────┤
│ Modules/Booking        │ booking_event_types,                  │
│                        │ booking_availability_rules, bookings, │
│                        │ booking_blocked_dates                 │
├────────────────────────┼───────────────────────────────────────┤
│ Modules/Intelligence   │ intelligence_tracked_assets,          │
│                        │ intelligence_competitors,             │
│                        │ intelligence_ads,                     │
│                        │ intelligence_swipe_collections,       │
│                        │ intelligence_swipe_items,             │
│                        │ intelligence_ugc_creators,            │
│                        │ intelligence_landing_page_snapshots,  │
│                        │ intelligence_activities               │
├────────────────────────┼───────────────────────────────────────┤
│ Modules/Tools          │ tools, tool_versions, tool_pricing_plans│
│                        │ tool_subscriptions, tool_licenses,    │
│                        │ tool_downloads, tool_screenshots,     │
│                        │ wa_accounts, wa_campaigns, wa_models  │
│                        │ activated_devices                     │
└────────────────────────┴───────────────────────────────────────┘
```

---

## 2. Core Entity Relationships

### Users Table (Central Identity)

```sql
users
  id               bigint PK
  name             string
  email            string UNIQUE
  password         string (bcrypt)
  role             string
  avatar           string nullable
  phone            string nullable
  -- Onboarding
  onboarding_completed  boolean default false
  -- Product Tour
  tour_completed   boolean
  tour_skipped     boolean
  current_tour_step integer
  -- KYC
  kyc_verified     boolean default false
  kyc_verified_at  datetime nullable
  kyc_verified_by  FK → users.id nullable
  kyc_provider     string nullable
  kyc_reference_id string nullable
  kyc_notes        text nullable
  -- Admin Control
  is_blocked       boolean
  block_reason     text nullable
  blocked_at       datetime nullable
  -- Temp License
  temp_valid_until datetime nullable
  -- Finance
  preferred_currency     string default 'USD'
  preferred_currency_locked_at datetime nullable
  email_verified_at datetime nullable
  timestamps
```

### Wallet System (Polymorphic)

```sql
wallets
  id              bigint PK
  owner_type      string  -- polymorphic (App\Models\User, etc.)
  owner_id        bigint  -- polymorphic
  context         string  default 'default'
  balance         decimal(20,8) default 0
  earned_balance  decimal(20,8) -- for freelance earnings
  locked_balance  decimal(20,8) -- added in migration
  currency        char(3)
  timestamps

wallet_transactions
  id              bigint PK
  wallet_id       FK → wallets.id CASCADE
  type            enum(credit, debit)
  direction       enum(credit, debit)  -- duplicate! schema inconsistency
  amount          decimal(20,8)
  amount_currency char(3)
  business_amount decimal(20,8)   -- platform base currency
  business_currency char(3)
  exchange_rate   decimal(20,8)
  exchange_rate_date date
  balance_before  decimal(20,8)
  balance_after   decimal(20,8)
  reference_type  string nullable
  reference_id    string nullable
  note/description string
  created_by      FK → users nullable
  timestamps
```

### ERP Core Structure

```sql
tenants
  id              bigint PK
  user_id         FK → users.id UNIQUE  (1:1 per user)
  business_name   string
  currency        char(3) default 'USD'
  timestamps

tenant_clients
  id              bigint PK
  tenant_id       FK → tenants.id
  user_id         FK → users.id nullable  (added 2026-05-20 fix migration)
  name            string
  email           string nullable
  phone           string nullable
  address         text nullable
  referred_by     FK → tenant_clients.id nullable (self-referential)
  status          enum(active, inactive, blocked)
  timestamps

client_wallets
  id              bigint PK
  tenant_id       FK → tenants.id
  client_id       FK → tenant_clients.id
  balance         decimal(20,2) default 0
  currency        char(3)
  timestamps

invoices
  id              bigint PK
  tenant_id       FK → tenants.id
  invoice_number  string UNIQUE
  client_id       FK → tenant_clients.id
  project_id      FK → projects.id nullable
  status          enum(draft, sent, partial, paid, cancelled)
  amount          decimal(15,2)
  amount_currency char(3)
  business_amount decimal(15,2)
  business_currency char(3)
  exchange_rate   decimal(15,6)
  exchange_rate_date date
  discount_amount decimal(15,2)
  tax_rate        decimal(5,2)
  tax_amount      decimal(15,2)
  paid_amount     decimal(15,2) default 0  -- added migration 2026-05-17
  due_date        date nullable
  issued_at       datetime nullable
  paid_at         datetime nullable
  notes           text nullable
  created_by      FK → users.id nullable
  timestamps

invoice_items
  id              bigint PK
  invoice_id      FK → invoices.id CASCADE
  description     string
  quantity        decimal
  unit_price      decimal
  total           decimal
  timestamps

invoice_costs
  id              bigint PK
  invoice_id      FK → invoices.id CASCADE
  description     string
  amount          decimal
  timestamps
```

---

## 3. Subscription & Billing Schema

```sql
module_plans
  id              bigint PK
  name            string
  module          string  (erp, freelance, marketplace, booking, intelligence)
  billing         enum(monthly, yearly)
  price           decimal(10,2)
  currency        char(3)
  features        json
  is_active       boolean
  sort_order      integer
  timestamps

user_subscriptions
  id              bigint PK
  client_id       FK → users.id  (subscriber)
  plan_id         FK → module_plans.id
  status          enum(active, cancelled, expired)
  started_at      datetime
  expires_at      datetime nullable
  auto_renew      boolean default true
  timestamps

subscription_invoices
  id              bigint PK
  user_id         FK → users.id
  plan_id         FK → module_plans.id
  invoice_number  string UNIQUE
  amount          decimal(10,2)
  currency        char(3)
  status          enum(pending, paid, failed)
  payment_method  enum(wallet, kashier)
  transaction_reference string nullable
  paid_at         datetime nullable
  timestamps
```

---

## 4. Serial License System

```sql
serial_softwares
  id              bigint PK
  name            string
  slug            string UNIQUE
  default_status  enum(active, inactive)
  timestamps

serial_devices
  id              bigint PK
  software_id     FK → serial_softwares.id
  device_id       string UNIQUE  (hardware fingerprint)
  hostname        string nullable
  mac_address     string nullable
  status          enum(active, inactive, banned)
  last_seen_at    datetime nullable
  timestamps

serial_user_devices
  id              bigint PK
  user_id         FK → users.id
  device_id       FK → serial_devices.id
  software_id     FK → serial_softwares.id
  status          enum(active, inactive, suspended)
  timestamps

-- users.temp_valid_until: datetime nullable
-- Override for temporary license extension (admin only)
```

---

## 5. Tools Marketplace Schema

```sql
tools
  id              bigint PK SOFT DELETE
  title           string
  slug            string UNIQUE
  description     text
  short_description text
  icon            string nullable
  category        enum(scraper, automation, whatsapp, ocr, ai, ...)
  supported_os    json  (['windows', 'mac', 'linux'])
  current_version string
  is_active       boolean
  is_featured     boolean
  is_free         boolean
  download_count  integer
  features        json
  requirements    json
  runner_component string  (React component name for frontend)
  metadata        json  (includes 'runtime' key for runtime agent)
  timestamps

tool_versions
  id              bigint PK
  tool_id         FK → tools.id
  version         string
  release_notes   text nullable
  is_latest       boolean
  download_url    string nullable
  file_size       bigint nullable
  timestamps

tool_pricing_plans
  id              bigint PK
  tool_id         FK → tools.id
  name            string
  price           decimal
  billing_period  enum(monthly, yearly, lifetime)
  features        json
  sort_order      integer
  timestamps

tool_subscriptions
  id              bigint PK
  user_id         FK → users.id
  tool_id         FK → tools.id
  plan_id         FK → tool_pricing_plans.id
  status          enum(active, cancelled, expired)
  expires_at      datetime nullable
  timestamps

tool_licenses
  id              bigint PK
  user_id         FK → users.id
  tool_id         FK → tools.id
  license_key     string UNIQUE
  status          enum(active, expired, suspended)
  expires_at      datetime nullable
  timestamps
```

---

## 6. Freelance Module Schema

```sql
freelance_jobs
  id              bigint PK
  user_id         FK → users.id  (poster)
  title           string
  description     text
  budget_min      decimal nullable
  budget_max      decimal nullable
  budget_type     enum(fixed, hourly)
  status          enum(open, in_progress, completed, cancelled)
  timestamps

proposals
  id              bigint PK
  job_id          FK → freelance_jobs.id
  freelancer_id   FK → users.id
  cover_letter    text
  price           decimal
  duration_days   integer nullable
  status          enum(pending, accepted, rejected, withdrawn)
  timestamps

contracts
  id              bigint PK
  job_id          FK → freelance_jobs.id
  client_id       FK → users.id
  freelancer_id   FK → users.id
  proposal_id     FK → proposals.id
  amount          decimal
  status          enum(active, completed, disputed, cancelled)
  started_at      datetime nullable
  completed_at    datetime nullable
  timestamps

skills + job_skills + freelance_user_skills (pivot tables)

point_packages (Freelance)
  id, name, points, price, currency, is_active

point_transactions (Freelance)
  id, user_id, type(earned|spent|credit|debit), points, reference_type, reference_id
```

---

## 7. Intelligence Module Schema

```sql
intelligence_tracked_assets
  id, user_id, type (domain|ad_account|social_profile), value, label, timestamps

intelligence_competitors
  id, user_id, name, domain, notes, timestamps

intelligence_ads
  id, user_id, competitor_id nullable, platform, ad_type, thumbnail_url,
  headline, body, cta, impression_start, impression_end, is_active, tags json, timestamps

intelligence_swipe_collections
  id, user_id, name, description, is_public, timestamps

intelligence_swipe_items
  id, collection_id, type, url, title, notes, tags json, timestamps

intelligence_ugc_creators
  id, user_id, platform, handle, followers, engagement_rate, niche, timestamps

intelligence_landing_page_snapshots
  id, user_id, url, title, screenshot_path, metadata json, timestamps

intelligence_activities
  id, user_id, type, subject_type, subject_id, description, timestamps
```

---

## 8. Schema Issues & Technical Debt

### ⚠️ Critical Issues

1. **Duplicate `type` + `direction` in wallet_transactions**  
   The `wallet_transactions` table has both `type` (enum: credit/debit) and `direction` (enum: credit/debit). These are redundant. The ERP WalletTransaction has additional types (invoice_paid, commission_earned, etc.) stored in `type`.

2. **tenant_clients.user_id FK added post-launch**  
   `user_id` FK was added in `2026_05_20_000001` fix migration. Older records use email matching (`User::resolveClient()` fallback). This creates a dual-resolution pattern that could break with email changes.

3. **Two parallel referral/earning tables**  
   - `referral_earnings` in Modules/Core (global)  
   - `referral_earnings` in Modules/ERP (ERP-scoped with tenant_id)  
   This is duplicate schema with different scoping — risk of confusion.

4. **Two parallel wallet_transaction tables**  
   - `wallet_transactions` (Core — polymorphic wallets)  
   - ERP `wallet_transactions` (scoped to tenant, used in client wallets)  
   Different schemas, same conceptual purpose.

### ⚠️ Missing Items

5. **No soft-delete on invoices** — cancelled invoices stay in the DB.
6. **No index on `wallet_transactions.reference_type + reference_id`** — performance risk at scale.
7. **No composite index on `user_subscriptions(client_id, status)`** — subscription check is unindexed.
8. **module_plans.module is a string** — was a JSON field, converted to string in migration `2026_05_17_234214`. No enum constraint enforced at DB level.

---

## 9. Migration Timeline

| Date | Migration | Domain |
|------|-----------|--------|
| 0001-01-01 | users, cache, jobs tables | Core |
| 2026-05-15 | Core tables (wallets, messages, ledgers) | Core |
| 2026-05-15 | ERP tables (invoices, clients, etc.) | ERP |
| 2026-05-15 | Freelance tables | Freelance |
| 2026-05-15 | Permissions (Spatie) | Auth |
| 2026-05-16 | Notifications, admin_notes, business_amount on wallet_tx | Core/ERP |
| 2026-05-17 | Onboarding fields, KYC fields, user_financials, subscription_invoices | User/Finance |
| 2026-05-17 | wallet_transfers, module_plans string fix | Finance |
| 2026-05-18 | Booking tables, Intelligence tables | Booking/Intelligence |
| 2026-05-18 | ERP workflow tables (tasks, notes), admin control fields | ERP/Admin |
| 2026-05-18 | tenant_files, tenant_storage_providers | Files |
| 2026-05-18 | serial system tables, activity_events | Serial/Core |
| 2026-05-19 | personal_access_tokens (Sanctum) | Auth |
| 2026-05-20 | tenant_notes, invoice fix (paid_amount + user_id FK) | ERP |
