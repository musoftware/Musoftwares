# /admin/payment-links — Gap Analysis & Implementation Plan

## 1. Scope

Audit the `/admin/payment-links` page (sidebar entry in `resources/js/Components/Admin/AppSidebar.tsx:78`) and its backing controller/model/guest flow, then list gaps and ordered remediation tasks.

In scope:
- `app/Http/Controllers/Admin/PaymentLinkController.php`
- `app/Models/PaymentLink.php` + migration `2026_06_02_100003_create_payment_links_table.php`
- `resources/js/Pages/Admin/Finance/PaymentLinks/Index.tsx`
- Route resource `admin.payment-links.*` (`routes/web.php:1100`, inside `auth/verified/onboarding/accountant` group)
- Guest flow `GuestPaymentLinkController` + `ProcessWebhookJob::handlePaymentLink` (duplicate logic)
- Related Invoice "Receive Payment Link" entry point (`Admin/Invoices/Show.tsx:427`)
- Missing lang keys (en/ar)

Out of scope: replacing Kashier gateway, designing a new UI layout, multi-tenant wallet reconciliation.

## 2. Current State (what works)

- Route resource (index/store/destroy) registered under admin auth + accountant middleware.
- Model: `uuid`, `user_id`, `client_id`, `title`, `amount`, `currency_id`, `status`, `paid_at`, `metadata`; soft-deletes; UUID auto-generated on create.
- Index page renders table, search box, status select, create modal (title/amount/currency), delete confirmation, dropdown with Copy/View/Delete.
- Guest flow: `GET /pay/{uuid}` → `PaymentLinkShow`; `POST /pay/{uuid}/initiate` → Kashier checkout; webhook marks link paid.
- Sidebar link present and lang keys for the visible strings exist in `lang/en/admin.php` and `lang/ar/admin.php`.

## 3. Gaps Found

### 3.1 Backend / Controller (`Admin/PaymentLinkController.php`)

1. **`index()` does not pass the `stats` prop the page reads** (`paymentLinks.total/paid/pending`). The metric cards render `undefined` for paid/pending.
2. **`store()` never persists `client_id`**, even though the migration/UI implies tenant-attached links.
3. **No `update()` method** → title/amount cannot be edited after creation; route is correctly `except([update])` so the API contract is missing.
4. **No `cancel()` method** → only `destroy` (soft delete); admins cannot mark a pending link as `cancelled` while keeping history.
5. **No server-side filtering** (search, status, client, date range) → all filtering is client-side over a single page (15 rows).
6. **Pagination not synced to URL** → refreshing wipes filters.
7. **No authorization policy** (anyone with the accountant middleware sees all tenants' links; should be tenant-scoped via `user_id` or `client_id`).
8. **No rate limiting / per-currency minimum** → free-form numeric input only.
9. **No transaction/wallet credit on webhook** → `GuestPaymentLinkController::paymentWebhook` and `ProcessWebhookJob::handlePaymentLink` only flip `status='paid'`; no ledger entry is created, so paid links do not appear in admin finance reports.

### 3.2 Frontend (`Admin/Finance/PaymentLinks/Index.tsx`)

1. Reads `paymentLinks.meta?.links || paymentLinks.links` — Laravel paginator exposes `links` directly; fallback mask hides real issues when the controller response shape drifts.
2. MetricCard for "Paid" uses `icon={Copy}` (wrong; should be `Check`/`CheckCircle`).
3. Status filter excludes `cancelled` even though the migration allows it; cancelled rows therefore vanish.
4. Search matches only `title` and `user.name` — does not match `uuid`, `amount`, `client.name`, or `currency.code`.
5. `EmptyState.action` points back to `admin.payment-links.index` — meaningless; should be `null` or an `onClick` that opens the create modal.
6. `paymentLinks.total` fallback used inside stats card even when stats object exists → masks the missing-controller bug.
7. No loading skeleton while Inertia fetches.
8. No pagination summary (e.g. "Showing 1–15 of 47").
9. The `create` modal lacks `description` (model has `metadata` JSON; UI never exposes it).
10. No client/tenant selector on create form even though the field exists.
11. No "Mark as paid manually" admin action (typical recovery when webhook failed).
12. No "Resend link" / "Regenerate UUID" action.
13. No bulk actions (delete/cancel multiple).
14. Delete is hidden for non-pending; an admin cannot delete a paid/cancelled link by mistake — but also cannot purge it intentionally.

### 3.3 Model / Migration

1. No DB index on `status` or `currency_id` → table scan on dashboard widgets that count paid/pending.
2. No `expires_at`, `cancelled_at`, `description`, `paid_transaction_id`, `paid_method` columns.
3. No enum constraint on `status` (string column); relies on convention only.
4. `metadata` JSON is never written.

### 3.4 Guest + Webhook

1. **Duplicate webhook handlers**: `GuestPaymentLinkController::paymentWebhook` (live route) and `ProcessWebhookJob::handlePaymentLink` (queued job). Either dispatch the job from the controller and delete the inline handler, or delete the job branch. Currently both can race.
2. Webhook does not verify amount matches `paymentLink.amount` → a partial payment still flips status to paid.
3. Webhook does not write `metadata.transaction_id` or link to a Kashier transaction record.
4. No idempotency guard beyond `status !== 'paid'`; two parallel webhooks can double-log.
5. Guest success/failure pages are static — they do not look up the original payment link to show amount/title/order info.

### 3.5 Invoices Integration

1. `Admin/Invoices/Show.tsx:427` "Receive Payment Link" calls `admin.invoices.share-link` (signed-URL flow) — NOT the new `payment_links` system. Two parallel "pay online" mechanisms exist; admins will be confused.
2. No migration path or admin-visible link between the two.

### 3.6 Localization

1. `lang/en/admin.php` and `lang/ar/admin.php` are missing parallel keys for any new strings (e.g. `expires_at`, `mark_paid_manually`, `cancel_link`, `regenerate_link`, `bulk_actions`, `no_results`, etc.). Today `__('admin.total_links')` and similar already exist; new strings must be added in both locales.

### 3.7 Tests

1. Zero coverage for `Admin/PaymentLinkController` (`tests/Feature/Admin/PaymentLink*` does not exist).
2. No feature test asserting accountant vs non-accountant access.
3. No test for webhook idempotency or amount mismatch.

## 4. Implementation Plan (ordered)

Tasks are sequenced so each compiles and tests green at the end of every step.

1. **Schema upgrade** — new migration adding `expires_at`, `cancelled_at`, `description`, `paid_transaction_id`, `paid_method`, indexes on `(status)`, `(user_id, status)`, `(client_id, status)`, and an enum-backed check constraint or PHP enum on status.
2. **Model updates** — cast new fields, add `scopePending/Paid/Cancelled/Expired`, add `markPaid()`, `markCancelled()`, `isExpired()`.
3. **Controller `index()`** — accept `search`, `status`, `client_id`, `currency_id`, `date_from`, `date_to`, `per_page` query params; build stats via `PaymentLink::aggregateForUser(Auth::id())`; pass `stats` to Inertia.
4. **Controller `store()`** — validate `client_id` (nullable, exists:users,id), `description`, `expires_at`; persist all fields; emit `PaymentLinkCreated` event.
5. **Add `update()`** — title, amount, description, currency, expires_at; reject updates when `status === 'paid'`.
6. **Add `cancel()`** — sets `cancelled_at` + `status='cancelled'`; rejects if already paid.
7. **Add `markPaid()`** admin override — requires `super_admin`; writes `paid_at`, `paid_method='manual'`, records actor in metadata.
8. **Policy `PaymentLinkPolicy`** — viewAny restricted to admin/accountant; view/update/delete scoped to `user_id` or `client_id` ownership.
9. **Refactor webhook** — make `GuestPaymentLinkController::paymentWebhook` dispatch `ProcessWebhookJob` with full payload; remove the duplicate `handlePaymentLink` branch in the job, or vice-versa. Add amount-mismatch guard and idempotent `lockForUpdate` in the job.
10. **Frontend Index.tsx fixes**:
    - Use real stats from controller; fix "Paid" metricCard icon to `CheckCircle`.
    - Add `cancelled` and `expired` to status filter.
    - Expand search to `uuid`, `amount`, `client.name`, `currency.code`.
    - Push filter state to URL (`router.get` with `replace: true`).
    - Empty-state: drop the redundant action button.
    - Add loading skeleton + pagination summary.
    - Add `Mark as paid`, `Cancel`, `Resend link` actions in the row dropdown.
    - Add bulk-select checkbox column + bulk toolbar.
11. **Create modal** — add `description`, optional `client` picker (reusing existing User picker), optional `expires_at`.
12. **Unify invoice "Receive Payment Link"** — either redirect the Invoice Show action to create a real `PaymentLink` row tied to the invoice and copy its `/pay/{uuid}` URL, or clearly label the existing signed-URL flow as "Share signed invoice link". Pick one and update copy + tooltips.
13. **Localization** — add new keys to `lang/en/admin.php` + `lang/ar/admin.php` for every string introduced in steps 4–11.
14. **Tests**:
    - `tests/Feature/Admin/PaymentLinkControllerTest.php`: index renders, store creates, update updates, cancel cancels, delete soft-deletes, non-accountant is forbidden, accountant scoped by client.
    - `tests/Feature/Admin/PaymentLinkWebhookTest.php`: webhook marks paid, duplicate webhook is idempotent, amount mismatch rejects, cancel after pay is rejected.
    - `tests/Unit/Models/PaymentLinkTest.php`: `isExpired`, scope filters.
15. **Manual smoke** — create link, copy URL, pay via Kashier sandbox, verify webhook flips status, verify it shows up in admin finance report (or note the gap explicitly).

## 5. Risks & Decisions Needed

- **Wallet credit on payment**: the current code does NOT credit any tenant wallet when a payment link is paid. Confirm with the user whether paid links should create a `Transaction` row, write into `payment_links.metadata`, or remain display-only. (Recommendation: write `paid_transaction_id` but defer wallet crediting to a follow-up, since the existing `finance` subsystem owns that logic.)
- **Tenant scoping**: today `Admin/PaymentLinkController::index` lists every tenant's links globally. Should the admin scope to `Auth::user()` (creator) or to a selected `client_id`? (Recommendation: default to creator `user_id`; allow `?client_id=` override for super_admin only.)
- **Unify with invoice share-link**: choose path 12a (replace) or 12b (relabel). (Recommendation: relabel for now to avoid breaking the invoice signed-URL contract; plan a separate ticket to consolidate.)

## 6. Validation Plan

- `php artisan test --filter=PaymentLink` after each step.
- `php artisan route:list --name=admin.payment-links` confirms new endpoints.
- `npx tsc --noEmit` and `npm run lint` for the frontend.
- Manual: log in as accountant, create link → copy URL → sandbox-pay → reload admin page → see status `paid`.
- Manual: log in as non-accountant admin → expect 403 on `/admin/payment-links`.
- Lighthouse mobile check on the Index page after UI changes.

## 7. Out of Scope

- Replacing Kashier or adding Stripe/PayMob for payment links.
- Refactoring `AppSidebar` global navigation.
- Building tenant self-service payment-links UI under `/sms-payment-gateway/payment-links` (separate page).