# Musoftware Platform — Business Logic Intelligence

> **Type**: Hidden Business Rules, Pricing Logic, Operational Constraints  
> **Source**: Model methods, controllers, migration constraints

---

## 1. Subscription Business Rules

### Module Access Gates
```
module_plans.module ∈ { erp, freelance, marketplace, booking, intelligence }

Access Rules:
  - Each module requires its own active subscription
  - Subscriptions are per-user, per-module
  - Multiple modules require multiple subscriptions
  - Subscription check middleware applied per-module route group
  
Billing Types:
  - monthly → expires in 1 month
  - yearly  → expires in 12 months
  
Auto-Renew:
  - New subscriptions default to auto_renew = true
  - Cancel sets auto_renew = false AND status = 'cancelled'
  - Renewal extends from expires_at (not now) if still active
  - Renewal extends from now if already expired
```

### Subscription Invoice Numbering
```
INV-SUB-{MODULE}-{timestamp}-{userId}   → new subscription
INV-REN-{MODULE}-{timestamp}-{userId}   → renewal
```

### Subscription → ERP Onboarding Bridge
```
When user subscribes to ERP module:
  IF no Tenant record exists → redirect to /erp/onboarding (setup wizard)
  IF Tenant exists → redirect to /erp/dashboard

This means every ERP subscriber must create a "Tenant" workspace.
The Tenant is the user's business identity within the ERP.
```

---

## 2. Invoice Business Rules

### Invoice Lifecycle Rules
```
Status Transitions (immutable rules in Invoice model):
  draft  → can only be sent (not paid directly)
  sent   → can be marked paid, partial payment, cancelled
  partial → can be fully paid, cancelled
  paid   → TERMINAL — no transitions (cannot cancel after full payment)
  cancelled → TERMINAL

Invoice Number:
  - Auto-generated per tenant (sequential)
  - Format: not fully visible but likely INV-{sequence}

Partial Payment Logic:
  - If partial amount equals remaining unpaid amount → treated as full payment
  - Threshold: abs(amount - maxPayable) < 0.01 (floating point tolerance)
  - Each partial payment records a WalletTransaction

Invoice Cancellation with Refund:
  - If any amount was paid → full paid_amount refunded to client wallet
  - Refund creates WalletTransaction (type: invoice_refund, direction: credit)
  - Related referral earnings cancelled
  - Paid invoices CANNOT be cancelled (business rule)
```

### Multi-Currency Support
```
Each invoice has two currency dimensions:
  - amount + amount_currency  → client-facing amount (their currency)
  - business_amount + business_currency → tenant's base currency equivalent

exchange_rate + exchange_rate_date → conversion rate used

This enables:
  - Invoicing clients in their local currency
  - Reporting in tenant's base currency
  - Historical exchange rate tracking
```

---

## 3. Referral Commission System

### L1 Referral Rules
```
Trigger: Invoice fully paid (billInvoice())
Condition: client.referred_by is set

Commission Rate: config('erp.referral_commission_l1', 5)  → default 5%

Amount Calculation:
  commission_amount = invoice.amount × commission_rate / 100

Business Amount:
  business_commission = invoice.business_amount × commission_rate / 100

Flow:
  1. Create ReferralEarning record (status: pending)
  2. Credit referrer's ClientWallet with commission_amount
  3. Create WalletTransaction (type: commission_earned, direction: credit)

Cancellation:
  Invoice cancelled → referralEarnings → status = 'cancelled'
  Note: wallet credit is NOT automatically reversed on cancellation

Architecture Note:
  - ERP referrals are client-to-client (TenantClient refers TenantClient)
  - Core referrals (Modules/Core) are platform-level (User refers User)
  - These are two separate referral systems
```

---

## 4. Wallet Business Rules

### Platform Wallet (User)
```
Created lazily: User::getWallet() uses firstOrCreate()
Default context: 'default'
Currency: user.preferred_currency (locked after first transaction)

Earned Balance:
  - Separate from regular balance
  - Used for freelance earnings tracking
  - May have different withdrawal rules

Locked Balance:
  - Funds held pending withdrawal approval
  - Not available for spending

Operations (all create WalletTransaction records):
  - Deposit via Kashier → credit
  - Subscription purchase → debit
  - Subscription renewal → debit
  - Marketplace escrow → debit (lock)
  - Marketplace completion → credit (release)
  - Freelance contract payment → credit/debit
  - P2P transfer → debit sender, credit recipient
  - Withdrawal → debit (with locked phase)
```

### Client Wallet (TenantClient within ERP)
```
Created on first billing need
Currency: set at creation

Operations (all create ERP WalletTransaction records):
  - Manual credit (admin/tenant adds funds)
  - Manual debit (admin/tenant removes funds)
  - Lock/unlock funds
  - Invoice payment → debit
  - Invoice refund → credit
  - Referral commission → credit
```

### P2P Transfer Rules
```
WalletTransferService controls transfer logic:
  - Sender must have sufficient balance
  - Recipient lookup by: email | username | phone (configurable)
  - Fee calculation (percentage/flat, configurable)
  - Creates WalletTransfer record (immutable)
  - Minimum transfer amount: configurable
  - Both wallets updated atomically in DB transaction
```

---

## 5. Serial License System Business Rules

### Device Registration Flow
```
Client software calls POST /api/serial/device on every startup

Registration logic:
  1. Look up software by slug
  2. Find or create SerialDevice (by device_id)
  3. Update: hostname, mac_address, last_seen_at
  4. Find SerialUserDevice (device ↔ user assignment)
  5. Determine effective status:

Status Resolution Hierarchy:
  a. If user.temp_valid_until is in future → return 'active' (temporary override)
  b. Else if SerialUserDevice.status = 'active' → return 'active'
  c. Else if SerialDevice.status = 'active' AND software.default_status = 'active' → return 'active'
  d. Otherwise → return 'inactive'
```

### Serial License Admin Controls
```
Admin can:
  - Set software.default_status (global default)
  - Set device.status (per-device override)
  - Set serial_user_device.status (per-user per-device)
  - Set user.temp_valid_until (temporary license extension)
  
Temp license takes priority over all other status checks.
This enables emergency license extension without full assignment workflow.
```

---

## 6. Tools Marketplace Business Rules

### Tool Availability
```
Tool visible in catalog IF:
  - is_active = true
  - NOT soft-deleted

Tool in Runtime Plugin API IF:
  - is_active = true  
  - metadata->runtime is not null

Tool categories:
  scraper | automation | whatsapp | ocr | ai | intelligence | analytics | data | browser | monitoring
```

### Tool Version Management
```
Multiple versions per tool (tool_versions table)
  - is_latest flag (only one latest per tool)
  - download_url → where runtime downloads the plugin zip

Tool update flow (from runtime):
  POST /plugins/{slug}/update { downloadUrl }
  → Delete existing plugin dir
  → Download + extract zip
  → npm/pip install dependencies
  → Reload plugin
```

### License vs Subscription
```
Two monetization models available:
  1. ToolSubscription → recurring access (monthly/yearly)
  2. ToolLicense → one-time/perpetual license key

License verification flow (runtime):
  - Check local SQLite cache first
  - If stale → verify with platform API
  - Platform returns: license_status, expires_at
  - Cache result locally for offline tolerance
```

---

## 7. Points System (Freelance)

### Points Economics
```
Point Packages:
  - Users buy points via Kashier
  - Points used to: feature jobs, boost proposals, unlock features

Transaction Types:
  earned | spent | credit | debit

Balance Calculation:
  balance = sum(earned + credit) - sum(spent + debit)

User model exposes:
  $user->points_balance (computed attribute)
  $user->pointTransactions (relationship)
```

---

## 8. Marketplace Escrow Rules

```
Order placed → Escrow created (funds locked from buyer wallet)

Escrow lifecycle:
  pending → held → released | refunded | disputed

Release conditions:
  - Buyer marks order complete → release to seller
  
Refund conditions:
  - Admin resolves dispute in buyer's favor → refund buyer
  - Cancellation within grace period → auto-refund

MarketplaceEscrow tracks:
  - amount + currency
  - buyer_id + seller_id
  - service_order_id
  - status + released_at
```

---

## 9. Activity Engine Business Rules

```
ActivityEvent table tracks all significant platform actions:
  - invoice_created, invoice_sent, invoice_paid, invoice_cancelled
  - client_created, client_status_changed
  - task_created, task_completed
  - payment_received
  - (extensible via ActivityLogger::log())

Dashboard widget:
  GET /api/activity → paginated feed, filterable by client_id, type

Full page:
  GET /activity → complete activity log

Architecture: subject morphable (Invoice, Client, Task, etc.)
              client_id → which TenantClient this pertains to
```

---

## 10. ERP Task Business Rules

### Task States
```
pending → in_progress → completed → archived
         ↓
       cancelled → archived
```

### Todo Item States
```
pending → in_progress → completed
         ↓
       paused → in_progress (resumable)
```

### Timer Integration
```
TimerSession tracks time spent on ERP items
GET /api/timer/{id} → current timer state

Timer sessions linked to tasks (ERPTask or TenantClient tasks)
Real-time updates via polling (no WebSocket for timers)
```

---

## 11. Pricing Intelligence (Hidden Rules)

### Subscription Pricing Architecture
```
module_plans:
  - module: which feature set
  - billing: monthly | yearly (no lifetime subscription support at module level)
  - price: decimal
  - currency: can vary per plan
  - features: JSON array of feature strings (marketing)
  - sort_order: display ordering

No discount/coupon system currently implemented.
No trial period system currently implemented.
No usage-based pricing — pure fixed recurring.
```

### Tool Pricing Architecture
```
tool_pricing_plans:
  - billing_period: monthly | yearly | lifetime
  - Lifetime option available (tools only, not modules)

No freemium features gating within tools (tools are is_free or paid).
```

---

## 12. Currency & Exchange Rate System

```
currencies table:
  - code (3-char), name, symbol, is_active

exchange_rates table:
  - from_currency, to_currency, rate, effective_date, source(manual|api_auto)
  - UNIQUE on (from_currency, to_currency, effective_date)
  - Historical rates preserved — not overwritten

Usage:
  - Invoice uses exchange_rate + exchange_rate_date snapshot
  - Rate snapshotted at invoice creation time
  - Historical rate lookups possible for reporting

Note: No automated rate fetching visible — likely manual entry or background job
```

---

## 13. Onboarding & KYC Business Rules

```
Onboarding gate:
  - 'onboarding' middleware blocks ALL authenticated routes until
    onboarding_completed = true
  - Applies to: dashboard, ERP, marketplace, all functional areas

KYC Verification:
  - NOT blocking — optional identity verification
  - Affects: potentially withdrawal limits, marketplace seller verification
  - kyc_verified stored on users table
  - Documents in kyc_documents table (linked to S3/local storage)

Preferred Currency Locking:
  - user.preferred_currency_locked_at → set when currency preference is first used
  - After locking, currency cannot easily be changed (business rule)
  - Prevents currency arbitrage in wallet system
```
