# Admin Menu Audit — Missing Entries & Broken Links

## Scope
Audit of the admin sidebar defined in `resources/js/Components/Admin/AppSidebar.tsx` (screenshot of the rendered "APPLICATION" group provided by user). Cross-checked against:

- `routes/web.php` (admin route groups at lines 543, 557, 1029)
- `resources/js/Pages/Admin/**` (existing Inertia pages)

The screenshot's items match the array literal in `AppSidebar.tsx` exactly, so the array is the source of truth.

---

## Verified Inventory (sidebar → routes/pages)

### APPLICATION → Dashboard
- `/admin/dashboard` → `routes/web.php:558`, `Pages/Admin/Dashboard.tsx` ✅

### User & Content
- Users → `/admin/users` ✅ (`routes/web.php:690`, `Pages/Admin/Users/Index.jsx`)
- Projects → `/admin/projects` ✅ (`routes/web.php:599`, `Pages/Admin/Projects/Index.tsx`)
- Plans → `/admin/plans` ✅ (`routes/web.php:648`, `Pages/Admin/Plans/Index.tsx`)
- Blog Articles → `/admin/blog-articles` ✅ (`routes/web.php:587`, `Pages/Admin/BlogArticles/Index.tsx`)

### Tasks
- Tasks List → `/admin/tasks/as_list` ✅ (`routes/web.php:830`)
- Task Calendar → `/admin/tasks/calendar` ✅ (`routes/web.php:834`)
- Client Tasks → `/admin/tasks/client-tasks` ✅ (`routes/web.php:836`)
- Employee Todos → `/admin/employee-todos` ✅ (`routes/web.php:590`)

### Invoices
- Unpaid / Archived / All Invoices → `/admin/invoices/*` ✅ (`routes/web.php:1031-1033`, `Pages/Admin/Invoices/*`)

### Finance & Business
- Costs, Recurring Costs, Income, Recurring Income, Recurring Salaries, Recurring Invoices, Reports, Balance, Payment Links, Hours Calendar, Transactions, Cost Transactions, Currencies, Currency Exchanges ✅ all match routes

### Operations
- Bulk Notify, Website Services, Guest Tickets, Tickets, Busy Times, Points Control, Point Packages, Charity, KYC Verification, Contracts, Contract Price List ✅ all match routes

### Marketplace
- All Services → `/admin/marketplace/all-services` ✅ (`routes/web.php:505`, `Pages/Admin/Marketplace/All.tsx`)
- Pending Services → `/admin/marketplace/pending-services` ✅ (`routes/web.php:506`, `Pages/Admin/Marketplace/Pending.tsx`)
- Categories → `/admin/marketplace/categories` ✅ (`routes/web.php:499`, `Pages/Admin/Marketplace/Categories.tsx`)
- Orders → `/admin/marketplace/orders` ✅ (`routes/web.php:516`, `Pages/Admin/Marketplace/Orders/Index.tsx`)
- Landing Pages → `/admin/marketplace/service-landing-pages` ✅ (`routes/web.php:521`, `Pages/Admin/Marketplace/ServiceLandingPages/Index.tsx`)

### Seller & Payout
- Payouts, Payment Methods, Withdraw Requests, Earning Analyze, Private CoWork, Vouchers, Coupons ✅

### Short Links
- `/admin/shortlinks` ❌ **BROKEN** — no controller, no route, no page

### System & Settings
- Musoftware Clients → `/admin/musoftware-clients` ❌ **BROKEN** — no controller, no route, no page
- Serial Softwares → `/admin/serial-softwares` ✅ (`routes/web.php:805`, `Pages/Admin/SerialSoftwares/Index.tsx`)
- Serial Devices → `/admin/serial-devices` ✅ (`routes/web.php:812`, `Pages/Admin/SerialDevices/Index.tsx`)
- Settings → `/admin/settings` ✅ (`routes/web.php:663`)
- Security & Rate Limits → `/admin/settings/security` ✅ (`routes/web.php:683`)

---

## Findings

### F1 — Broken menu links (2 items point to non-existent routes)
Both `Short Links` and `Musoftware Clients` are sidebar entries with no backing controller, route, or Inertia page. Clicking them 404s or renders an empty layout.

- `Short Links` (`AppSidebar.tsx:131-135`)
- `Musoftware Clients` (`AppSidebar.tsx:141`)

**Decision needed:** Either (a) build the missing controllers + routes + Inertia pages, or (b) remove the sidebar entries until the feature exists.

### F2 — Implemented admin modules not exposed in the menu
The following admin routes/controllers/Inertia pages exist but have no sidebar entry. They are reachable only by URL guessing.

| Module | Route | Page | Group candidate |
|---|---|---|---|
| AI Estimator | `GET /admin/tools/ai-estimator` (`web.php:564`) | not located | Operations or new "Tools" |
| Reports | `GET /admin/reports` (`web.php:576`) | `Pages/Admin/Reports/Index.tsx` | Operations (alongside Reports inside Finance?) — conflict with `Business/Reports` URL |
| Free Downloads | `resource /admin/free-downloads` (`web.php:676`) | not located | System & Settings |
| Incoming Webhooks | `GET /admin/settings/incoming-webhooks` (`web.php:679`) | not located | System & Settings (under Settings) |
| Quotation Print | `GET /admin/quotations/{contract}/print` (`web.php:674`) | n/a (print view) | hidden detail — fine to leave out |
| Recurring Notices | inline on Project Board (intentional, per `web.php:1154`) | `Components/Admin/NoticesManager.tsx` | intentional — fine |
| Payment Gateway | `web.php`? (pages exist: `Pages/Admin/PaymentGateway/*`) | yes | likely belongs under Finance & Business or Seller & Payout — needs confirmation |

### F3 — Duplicate "Reports" names
Two unrelated admin pages both named "Reports":
- `Pages/Admin/Reports/Index.tsx` at `/admin/reports` (global)
- `Pages/Admin/Business/Reports.tsx` at `/admin/business/reports` (Finance & Business)

The sidebar only surfaces the second one. If the first has distinct value (no controller inspection done), it should be linked too. **Decision needed.**

### F4 — Icon duplication / weak icons
- `Finance & Business` and `Invoices` both use `DollarSign` (AppSidebar.tsx:18 import + lines 57, 68). Mild UX issue, not functional.
- No missing icons block functionality.

### F5 — Role visibility logic
Lines 162-186 define filter logic for `accountant`, `support_agent`, `moderator`. Worth a quick re-check after adding/removing items so each role still sees a coherent subset. Not blocking the audit.

### F6 — `tasks/todos` POST/PUT/DELETE routes exist (`web.php:832-843`) but no GET index for raw `/admin/tasks` — sidebar `Tasks` parent URL `/admin/tasks` (`AppSidebar.tsx:46`) 404s. The first sub-item (`Tasks List`) is what users land on, but the collapsible trigger is also a link. **Minor.**

### F7 — Hardcoded English strings
`AppSidebar.tsx` titles are hardcoded English (`AppSidebar.tsx:30-148`). i18n skill mandates zero hardcoded strings; other components use `__()`. This is a translation-readiness gap.

---

## Open Questions (one at a time, with recommendation)

1. **Short Links + Musoftware Clients — build or remove?**
   - Recommended: **remove** from sidebar until the modules are actually built. They are misleading.
   - Alternative: build them (much larger scope; out of an audit's mandate).

2. **Add the missing-but-implemented modules to the menu?**
   - Recommended: add **AI Estimator** (Operations), **Free Downloads** (System & Settings), **Incoming Webhooks** (under Settings sub), **Reports** (Operations, with name `Global Reports` to disambiguate).
   - **Payment Gateway** — needs a quick route check before adding.

3. **Resolve the two "Reports" pages?**
   - Recommended: keep both, but rename the sidebar entry for `Reports` under Finance & Business to `Finance Reports` for clarity, and add `Global Reports` under Operations for the `/admin/reports` page.

---

## Plan of Work (when implementing)

1. Decide per open question (build vs remove vs add).
2. Edit `resources/js/Components/Admin/AppSidebar.tsx`:
   - Remove or wire broken entries (F1).
   - Add missing-but-implemented entries (F2/F3).
   - Wrap titles in `__()` for i18n parity (F7).
3. Verify `route('admin.*')` names used in `<Link href>` exist (already done in audit).
4. Smoke test by clicking each sidebar entry as `admin`, `accountant`, `support_agent`, `moderator` (F5).
5. Re-run sidebar role-filter logic if items added/removed.

## Out of Scope
- Building Short Links or Musoftware Clients modules themselves.
- Building any missing Inertia pages (only surfacing existing ones).
- Renaming the existing `Business/Reports` controller or routes.

## Validation
- Manual: open each menu link as each role, confirm no 404 and that active state highlights correctly (`isActive` / `isGroupActive` in `AppSidebar.tsx:202-204`).
- `php artisan route:list --columns=method,uri,name | grep admin.` to spot missing routes before edits.