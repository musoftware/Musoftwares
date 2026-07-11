# Admin Sidebar Cleanup — Implementation Plan

## Goal
Fix the admin sidebar (`resources/js/Components/Admin/AppSidebar.tsx`) so every entry points to a real route and page, and make titles i18n-ready.

## Source of truth
- Audit plan: `.kilo\plans\1783717530464-admin-menu-audit.md`
- Component: `resources/js/Components/Admin/AppSidebar.tsx`
- Routes: `php artisan route:list` (verified during audit)

## Decisions (resolved)

1. **Broken entries (F1)** → **Remove** `Short Links` and `Musoftware Clients`. Building the modules is explicitly out of scope.
2. **Missing-but-implemented (F2)** → Add to sidebar:
   - `AI Estimator` → `/admin/tools/ai-estimator` (Operations group)
   - `Incoming Webhooks` → `/admin/settings/incoming-webhooks` (System & Settings group)
   - `Global Reports` → `/admin/reports` (Operations group)
   - **Skip** `Free Downloads` — no Inertia page exists at `resources/js/Pages/Admin/**` even though the route does (`admin.free-downloads.index`).
   - **Skip** `Payment Gateway` admin UI — only `api/payment-gateway/*` and `sms-payment-gateway/*` routes exist; no `GET /admin/payment-gateway`.
3. **Duplicate "Reports" (F3)** → Rename Finance & Business entry from `Reports` to `Finance Reports`. Add `Global Reports` under Operations.
4. **Tasks parent URL (F6)** → Leave as-is. First sub-item is the de facto landing page; collapse trigger 404 is a known minor issue.
5. **Hardcoded strings (F7)** → Wrap displayed titles in `__()`. Keep `item.title` as a stable English identifier used by the role filter at lines 162–186. Render-time: `__('admin.sidebar.<snake_case_key>', item.title)`.

## File changes — single file

### `resources/js/Components/Admin/AppSidebar.tsx`

1. **Imports (line 18)** — remove `Link2`; add `Wand2` (AI Estimator), `BarChart3` (Global Reports).
   ```ts
   import { LayoutDashboard, Users, Building2, DollarSign, Settings, ChevronRight,
            Briefcase, CreditCard, ListTodo, Wand2, BarChart3 } from 'lucide-react';
   ```

2. **Array literal (lines 30–148)** — apply the following diffs:

   - Remove the `Short Links` group (lines 131–135) entirely.
   - In `System & Settings`:
     - Remove the `Musoftware Clients` sub-item (line 141).
     - Add `Incoming Webhooks` sub-item → `/admin/settings/incoming-webhooks`.
   - In `Operations`:
     - Add `AI Estimator` sub-item (icon `Wand2`) → `/admin/tools/ai-estimator`.
     - Add `Global Reports` sub-item (icon `BarChart3`) → `/admin/reports`.
   - In `Finance & Business`:
     - Rename sub-item `Reports` → `Finance Reports` (URL stays `/admin/business/reports`).

3. **Render (lines 220, 237, 257)** — wrap displayed title/sub-title text with `__()` using a stable snake_case key derived from the English identifier. Pattern:
   ```ts
   const t = (s: string) => __(`admin.sidebar.${s.toLowerCase().replace(/[^a-z0-9]+/g, '_')}`, s);
   ```
   Define `t` once inside the component before the `return`. Replace every `{item.title}` and `{subItem.title}` JSX expression with `{t(item.title)}` / `{t(subItem.title)}`. Tooltip props (`tooltip={item.title}`) can also use `t(item.title)`.

4. **Role filter (lines 162–186)** — **no logic change** required. The filter matches on the raw English `item.title` / `subItem.title`, which we keep unchanged. After renaming `Reports` → `Finance Reports`, confirm no allow-list relies on the exact string `Reports`:
   - Accountant (line 164) filters on group titles only — safe.
   - Support agent (line 171) — `Tickets` / `Guest Tickets` — safe.
   - Moderator (line 181) — `Tickets` — safe.

5. **Translations JSON** — wrap-with-`__()` returns the English fallback (line 26 of `resources/js/lib/i18n.ts`) when a key is missing, so no translation entries are required for the UI to render. Populating `admin.sidebar.*` keys is a follow-up.

## Risk and mitigations
- **Active state mis-highlight** — `isActive` uses `url === item.url || url.startsWith(item.url + '/')` (line 202). `/admin/tools/ai-estimator` startsWith `/admin/tools` is false; URL `/admin/reports` does not collide with any existing entry. No collision.
- **Accountant oversharing** — `Operations` is not in the accountant allow-list (line 164), so the new AI Estimator and Global Reports entries are not exposed to accountants. Matches user expectation.
- **Moderator / Support agent** — neither filter affects `System & Settings`, so `Incoming Webhooks` becomes visible to them. This is acceptable: the page already exists at `resources/js/Pages/Admin/Settings/IncomingWebhooks/Index.tsx` and is unconstrained.
- **Build vs remove for Short Links / Musoftware Clients** — confirmed out of scope in the audit; removing them avoids 404s.

## Validation
1. `php artisan route:list | grep admin.tools.ai-estimator` etc. — already confirmed during planning.
2. `npx tsc --noEmit` (or the repo's typecheck command) to catch type regressions in `AppSidebar.tsx`.
3. Smoke test manually as each role:
   - `admin` — every sidebar link loads without 404; active states highlight correctly.
   - `accountant` — sees only Invoices / Finance & Business / Seller & Payout.
   - `support_agent` — sees Operations with Tickets + Guest Tickets only.
   - `moderator` — sees Operations with Tickets only.
4. Confirm `link2` icon import is removed cleanly (no unused-import lint error).

## Out of scope
- Building `Short Links` or `Musoftware Clients` modules.
- Building Free Downloads or Payment Gateway admin UI pages.
- Adding language entries to `translations.json`.
- Renaming the `Business/Reports` controller or route.
- Changing the dead `/admin/tasks` parent URL.
