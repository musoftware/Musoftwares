# Musoftware Platform — Operational Workflow Intelligence

> **Type**: Reverse-Engineered Business Logic & Lifecycle Documentation  
> **Source**: Controller analysis, model methods, route definitions

---

## 1. User Lifecycle

### 1.1 Registration Flow

```
User visits /register
  │
  ├── Form: name, email, password
  ├── Laravel Breeze handles registration
  ├── Email verification sent (if MustVerifyEmail enabled)
  │
  └── Redirect → /dashboard
       │
       ├── Middleware 'onboarding' fires
       ├── If onboarding_completed = false → redirect /onboarding-wizard
       └── Else → show dashboard
```

### 1.2 Onboarding Wizard

```
GET /onboarding-wizard (OnboardingController::show)
  │
  └── Inertia render 'Auth/OnboardingWizard'
       │ Collects: country, city, phone, preferred_currency, avatar
       ▼
POST /onboarding-wizard (OnboardingController::store)
  │
  ├── Validate fields
  ├── Update user record
  ├── Set onboarding_completed = true
  ├── Create User Wallet (getWallet() auto-creates)
  └── Redirect → /dashboard
```

### 1.3 Product Tour

```
POST /product-tour/status
  │
  ├── 'mark_complete': tour_completed = true
  ├── 'skip': tour_skipped = true
  └── 'update_step': current_tour_step = N

ProductTourModal.tsx renders tour guide on first login
```

### 1.4 KYC Verification Workflow

```
User submits KYC:
GET /kyc → Upload documents
POST /kyc/upload → Store file (S3 or local)
POST /kyc/submit → Mark as submitted

Admin reviews:
GET /admin/kyc → List pending KYC
POST /admin/kyc/{id}/approve → kyc_verified = true, kyc_verified_at = now, kyc_verified_by = admin_id
POST /admin/kyc/{id}/reject  → kyc notes updated

States: pending → submitted → verified | rejected
```

---

## 2. Subscription Lifecycle

### 2.1 Module Subscription Purchase (Wallet)

```
User visits /subscriptions/plans?module=erp

Step 1: Select Plan
  │
  └── POST /subscriptions/subscribe { plan_id }
       │
       ├── Validate: plan exists, is_active
       ├── Check wallet balance >= plan.price
       ├── DB Transaction:
       │   ├── Deduct from user wallet (balance_before, balance_after recorded)
       │   ├── Create WalletTransaction (type: debit)
       │   ├── Expire old subscriptions for same module
       │   ├── Create UserSubscription (status: active, expires_at: +1month/+1year)
       │   └── Create SubscriptionInvoice (INV-SUB-ERP-{timestamp}-{userId})
       │
       └── Redirect:
           ├── ERP module + no tenant → /erp/onboarding
           ├── ERP module + tenant exists → /erp/dashboard
           └── Other modules → /subscriptions/manage
```

### 2.2 Module Subscription Purchase (Kashier)

```
POST /subscriptions/kashier/checkout { plan_id }
  │
  ├── Build Kashier payment URL (KashierHelper::buildSubscriptionPaymentUrl)
  │   Includes metadata: { user_id, plan_id, source: 'subscription-purchase' }
  └── Redirect user to Kashier payment page

User pays on Kashier → Kashier webhooks back:
POST /subscriptions/kashier/webhook (NO AUTH)
  │
  ├── Validate Kashier payload signature
  ├── Check status === 'SUCCESS'
  ├── Extract metadata: userId, planId, source, trxId
  ├── Idempotency check: SubscriptionInvoice.where(transaction_reference, trxId).exists()
  └── DB Transaction (same as wallet flow above)

User lands on success/failure pages:
GET /subscriptions/kashier/success → redirect to ERP or manage
GET /subscriptions/kashier/failure → back to plans
```

### 2.3 Subscription States

```
active    → normal usage (module accessible)
cancelled → auto_renew = false, access until expires_at
expired   → no module access (subscription middleware blocks)
```

### 2.4 Subscription Middleware (`subscription:erp`)

```
Middleware: subscription:erp (applied to /erp/* routes)
  │
  ├── Get user's active UserSubscription for module='erp'
  ├── If no active subscription → redirect /subscriptions/plans?module=erp
  └── If active → pass through to controller
```

---

## 3. Invoice Lifecycle (ERP)

### 3.1 Invoice States

```
draft → sent → partial → paid
              ↓
           cancelled (from any non-paid state)
```

### 3.2 Full Invoice Lifecycle

```
CREATE:
POST /erp/invoices { client_id, items, due_date, ... }
  │
  ├── InvoiceController::store
  ├── Generate invoice_number (auto-increment per tenant)
  ├── Status = 'draft'
  └── ActivityLogger::log('invoice_created')

SEND:
POST /erp/invoices/{invoice}/send
  │
  ├── Status = 'draft' required
  ├── Status → 'sent'
  └── ActivityLogger::log('invoice_sent')

FULL PAYMENT (Wallet):
POST /erp/invoices/{invoice}/mark-paid  [admin/tenant]
  │
  └── Invoice::billInvoice()
       ├── Validate: status in (sent, partial)
       ├── Validate: client has wallet with sufficient balance
       ├── DB Transaction:
       │   ├── ClientWallet lockForUpdate
       │   ├── Create WalletTransaction (type: invoice_paid, direction: debit)
       │   ├── Update wallet balance
       │   ├── Invoice status → 'paid', paid_amount = amount, paid_at = now
       │   └── processReferralCommissions()
       └── ActivityLogger::log('invoice_paid')

CLIENT PAY VIA WALLET:
GET /my/invoices → client sees their invoices
GET /my/invoices/{uuid}/pay → show payment form
POST /my/invoices/{uuid}/pay/wallet
  │
  └── InvoicePaymentController::processWalletPayment
       └── Same billInvoice() flow but initiated by client

PARTIAL PAYMENT:
POST /erp/invoices/{invoice}/partial-payment { amount }
  │
  └── Invoice::partiallyBillInvoice(amount)
       ├── Status → 'partial'
       └── paid_amount += amount

CANCEL:
POST /erp/invoices/{invoice}/cancel
  │
  └── Invoice::cancelInvoice()
       ├── If paid_amount > 0 → create WalletTransaction (type: invoice_refund, credit)
       ├── Status → 'cancelled'
       └── referralEarnings → status = 'cancelled'
```

### 3.3 Referral Commission Trigger

```
Invoice::processReferralCommissions() — called on billInvoice()
  │
  ├── Check: client has referred_by (referrer client)
  ├── Calculate: commission = amount * erp.referral_commission_l1 / 100 (default 5%)
  ├── Create ReferralEarning record (status: pending)
  └── Credit referrer's client wallet with commission amount
```

---

## 4. Wallet & Financial Lifecycle

### 4.1 User Wallet Top-Up (Kashier)

```
GET /financial/add-balance → show deposit form
POST /financial/add-balance/kashier { amount }
  │
  ├── Build Kashier deposit URL
  └── Redirect to Kashier

Kashier payment completes:
POST /financial/add-balance/webhook
  │
  ├── Validate signature
  ├── Credit user wallet
  └── Create WalletTransaction (type: credit)

GET /financial/add-balance/success → success page
GET /financial/add-balance/failure → retry page
```

### 4.2 P2P Wallet Transfer

```
GET /financial/transfer → show transfer form
GET /financial/transfer-api/search-users?q= → live user search
GET /financial/transfer-api/calculate-fee → fee calculation

POST /financial/transfer { recipient_identifier, amount, note }
  │
  └── WalletTransferService::transfer()
       ├── Validate: sender has sufficient balance
       ├── Validate: recipient exists
       ├── DB Transaction:
       │   ├── Debit sender wallet
       │   ├── Credit recipient wallet
       │   └── Create WalletTransfer record
       └── Notifications to both parties

GET /financial/transfer/history → list user's transfers
GET /financial/transfer/{id} → single transfer detail
```

### 4.3 Withdrawal Request

```
POST /financial/withdrawals { amount, payout_method_id }
  │
  ├── FinancialController::requestWithdrawal
  ├── Validate: sufficient balance
  ├── Create UserWithdrawal (status: pending)
  └── Lock funds (pending review)

Admin processes:
Admin dashboard shows pending withdrawals
  ├── Approve → mark processing → mark paid
  └── Reject → refund locked funds
```

---

## 5. ERP Client Lifecycle

### 5.1 Client States

```
active → inactive → blocked
```

### 5.2 Client Management Flows

```
CREATE:
POST /erp/clients { name, email, phone, ... }
  │
  ├── ERPDashboardController::storeClient
  ├── TenantClient created (tenant_id = current user's tenant)
  └── Client wallet auto-created on first billing

VIEW:
GET /erp/clients/{client}
  │
  └── ClientController::show
       ├── Client info
       ├── Wallet balance
       ├── Invoice history
       ├── Task history
       ├── Notes
       └── Activity timeline

STATUS:
PUT /erp/clients/{client}/status { status }
  └── ClientController::updateStatus

LINK TO PLATFORM USER:
Via email matching OR user_id FK (added 2026-05-20)
```

---

## 6. Marketplace Order Lifecycle

### 6.1 Order States

```
pending → accepted → in_progress → delivered → completed
                                              ↓
                                          disputed
```

### 6.2 Order Flow

```
BROWSE:
GET /marketplace/services → public listing
GET /marketplace/services/{id} → service detail

ORDER:
POST /marketplace/orders { service_id, package_id }
  │
  ├── ServiceOrderController::store
  ├── Check: buyer has wallet funds (or payment upfront)
  ├── Create ServiceOrder (status: pending)
  ├── Create MarketplaceEscrow (funds locked)
  └── Notify seller

DELIVER:
POST /marketplace/orders/{order}/deliver { delivery_data }
  │
  ├── Status → 'delivered'
  └── Notify buyer

COMPLETE:
POST /marketplace/orders/{order}/complete
  │
  ├── Release escrow to seller wallet
  └── Status → 'completed'

DISPUTE:
POST /marketplace/orders/{order}/dispute { reason }
  │
  ├── Status → 'disputed'
  └── Admin intervention required

REVIEW:
POST /marketplace/orders/{order}/review { rating, comment }
  └── Create ServiceReview

MESSAGES:
POST /marketplace/orders/{order}/messages { body }
  └── OrderMessageController::store → creates Message in Conversation
```

---

## 7. Freelance Job Lifecycle

### 7.1 Job States
```
open → in_progress → completed | cancelled
```

### 7.2 Contract States
```
active → completed | disputed | cancelled
```

### 7.3 Flow

```
POST /freelance/jobs → Create job (client)
POST /freelance/jobs/{job}/proposals → Submit proposal (freelancer)
  │
POST /freelance/proposals/{proposal}/accept → Accept proposal
  │
  └── Create Contract (status: active)

POST /freelance/contracts/{contract}/complete
  │
  ├── Release payment
  └── Status → completed
```

---

## 8. Tools Marketplace & Runtime Execution

### 8.1 Tool Purchase & Runtime Execution

```
User visits /tools (public listing via Tools module)
  │
  └── Selects tool + pricing plan
       │
       └── Subscribe or one-time purchase
            │
            └── Runtime activation flow:
                 │
                 ├── Runtime must be installed + running
                 ├── Runtime authenticates with platform (device auth)
                 ├── POST /plugins/sync → PluginSyncer downloads tool
                 └── Tool available in runtime at /plugins/{slug}/run

Run a Tool:
Platform frontend sends:
POST http://127.0.0.1:18400/plugins/{slug}/run { params }
  │
  ├── License check (local cache + platform verify)
  ├── Quarantine check
  ├── Security audit
  └── TaskRegistry.run(plugin, params) → returns taskId

Monitor progress:
GET http://127.0.0.1:18400/tasks/{taskId}
  └── { status, logs, result, priority }

WS events stream task progress in real-time
```

---

## 9. Booking System Lifecycle

```
Admin creates BookingEventType:
  { title, duration_minutes, price, buffer_time, max_bookings_per_slot }

Admin sets availability:
  BookingAvailabilityRule (day_of_week, start_time, end_time)

Admin blocks dates:
  BookingBlockedDate (date, reason)

Client books:
POST /booking/book { event_type_id, date, time, ... }
  │
  ├── Validate: slot available (not blocked, within rules)
  ├── Create Booking (status: pending → confirmed)
  └── Notify both parties

States: pending → confirmed → completed | cancelled
```

---

## 10. Intelligence System Workflows

```
COMPETITOR TRACKING:
POST /intelligence/competitors → Add competitor domain
POST /intelligence/competitors/{id}/analyze → Trigger analysis

AD TRACKING:
POST /intelligence/ads → Save ad to swipe vault
GET /intelligence/ads → Feed with filters (platform, date, competitor)

SWIPE VAULT:
POST /intelligence/swipe-collections → Create collection
POST /intelligence/swipe-collections/{id}/items → Add item (ad/screenshot)

UGC TRACKING:
POST /intelligence/ugc-creators → Track a UGC creator
GET /intelligence/ugc-creators → List with filters

LANDING PAGE SNAPSHOTS:
POST /intelligence/landing-pages → Capture URL snapshot
  └── Runtime plugin executes Playwright screenshot
```

---

## 11. Admin Workflows

### 11.1 User Management

```
GET /admin/users → List all platform users
GET /admin/users/problematic → Flagged users
POST /admin/users → Create user manually

GET /admin/users/{id} → Full user profile + stats
GET /admin/users/{id}/edit → Edit form
PUT /admin/users/{id} → Update user

POST /admin/users/{id}/toggle-block → Block/unblock
GET /admin/users/{id}/login-as → Impersonate user (ImpersonateController)
  └── Sets session to impersonated user
  └── Admin retains ability to return to own session

POST /admin/users/{userId}/notes → Private admin notes
POST /admin/users/{userId}/files/upload → Upload files for user
```

### 11.2 Tools Admin

```
GET /admin/tools → Tool catalog
POST /admin/tools → Create tool entry
PUT /admin/tools/{tool} → Update tool
POST /admin/tools/{tool}/upload-version → Upload new .zip version
  └── Stores file, creates ToolVersion record, sets is_latest
```

### 11.3 Serial License Admin

```
GET /admin/serial-softwares → Manage software registry
GET /admin/serial-devices → View all device registrations
GET /admin/serial-user-devices → User-device assignments
POST /admin/serial-user-devices → Assign device to user
PATCH /admin/serial-user-devices/{id}/status → Activate/suspend/revoke
PATCH /admin/serial-user-devices/users/{user}/temp-valid → Temporary license
```

---

## 12. Recurring Entries (ERP)

```
CREATE:
POST /erp/recurring { type, amount, client_id, interval, next_execution }

States: active → paused → completed | cancelled

PAUSE/RESUME:
POST /erp/recurring/{id}/pause → paused
POST /erp/recurring/{id}/resume → active

EXECUTION:
RecurringEntry auto-executes via:
  - Console command or queue job (Console/ directory)
  - Creates RecurringEntryLog on each execution
  - Creates actual invoice/expense on trigger

GET /erp/recurring/{id}/logs → Execution history
```
