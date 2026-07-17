# Client Dashboard Apple-HIG Redesign

**Ticket source:** UI/UX improvement ticket (Arabic) — Personalized Customer Dashboard following Apple HIG (Clarity, Depth, Consistency), real-world metaphors, and full focus on Projects & Financials.

**User-confirmed decisions:**

- Currency-format scope = **Dashboard-wide only** (Wallet card, Pending Banner amount, Project Budget/Paid rows). Other pages (admin, ERP, marketplace) keep their existing `font-mono` / symbol-prefix formatting.
- Empty-state illustrations = **Lucide icons** (Coffee, Wind, Sparkles) — no SVG art / no image assets.
- Country flags = **Static JS map** keyed by ISO-4217 currency code — no DB migration.
- Musoftware Runtime card = **Removed** from the Dashboard (route `/runtime/download` remains reachable from the sidebar / footer / support menu if ever needed elsewhere).

## Goal

Convert the customer `Client/Dashboard` page into a personalized, fast-scanning, depth-rich layout:

- **Top row:** three equal columns — Active Subscriptions · Charge & Balance (with `Account Balance` merged into the upper half as a debit-card-style display) · Billing & Invoices.
- **Bottom row:** chart 65% / latest transactions 35%.
- **Removed:** the Musoftware Runtime card (kept the rest of the file structurally identical for diff hygiene).
- System-wide polish items (iconography weight, proportional currency typography, pastel semantic icon coding in the Workspace dropdown, dynamic unpaid-invoices badge, folder metaphor + inline BUDGET/PAID row, frosted-glass chart tooltip, friendly empty states, active-state tint, hover depth).

## Affected Boundaries

**Frontend — single-page scope:**

- `resources/js/Pages/Client/Dashboard.tsx` — assemble new top-3 + chart/transactions grid.
- `resources/js/Pages/Client/Dashboard/Components/CoreOperationsCards.tsx` — remove Runtime card; merge `Account Balance` into a redesigned Charge & Balance card; dynamic unpaid-invoices badge.
- `resources/js/Pages/Client/Dashboard/Components/FinancialHistory.tsx` — change split 65/35 (was 2/1), apply frosted-glass tooltip to recharts.
- `resources/js/Pages/Client/Dashboard/Components/PendingInvoicesBanner.tsx` — switch amount from `font-mono` to proportional; ensure wallet balance row uses the new display.

**Cross-page (Dashboard scope only):**

- `resources/js/Pages/Client/Projects/Index.tsx` — replace the gray/green Budget & Paid pill boxes with an inline flex row using new `ProjectBudgetRow` component (icons: Wallet / CheckCircle2). Add folder-tab metaphor + soft hover lift.
- `resources/js/Pages/Client/Projects/Tasks.tsx` and `TasksAggregator.tsx` — pass a friendly Lucide icon override to `EmptyState`.

**Shared utilities:**

- `resources/js/lib/currencyDisplay.tsx` *(new)* — small reusable component that renders an ISO-4217 currency with the country flag and proportional typography (re-used by the wallet card, the project inline row, and the pending banner).
- `resources/js/lib/currencyMeta.ts` *(new)* — tiny TS module exporting `{ FLAGS_BY_CODE: Record<string,string>, CURRENCY_SECTIONS: { code: string; flag: string }[] }`. Single source of truth, isolated from `formatMoney` so we don't disturb the rest of the site.
- `resources/js/Components/ui/EmptyState.tsx` — additive prop change: accept a custom Lucide icon (already supported) AND a new optional `tone?: 'neutral' | 'friendly'` flag that swaps the white circle background for a pastel tinted circle. No breaking changes.

**Workspace dropdown (HIG icon coding):**

- `resources/js/Layouts/AuthenticatedLayout.tsx` — re-tint the icon squares per column (Projects → blue pastel, Financials → emerald pastel, Support → orange pastel) and add the active-state tint at the column header / item level.

**Translations:**

- `resources/js/translations.json` — add 3 keys to both `ar` and `en` namespaces: `general.account_id_masked` (e.g. "•••• 4829"), `general.budget`, `general.paid`. Everything else already exists (`charge_balance`, `account_balance`, `active_subscriptions`, `unpaid_invoices`, …).

**Out of scope / explicitly NOT changed:**

- `formatMoney` in `resources/js/lib/utils.ts` — untouched.
- `CurrencyDisplay.tsx` — untouched (still used by hundreds of admin/ERP pages).
- `MetricCard.tsx` — untouched (still used by dozens of pages).
- Admin/ERP/Marketplace dashboards.
- Musoftware Runtime backend / routes — untouched.
- DB schema (no migration).

## Data Flow

The data contract supplied by `DashboardService::getClientDashboardData()` (`app/Services/DashboardService.php:288`) does not need to change — every visual requirement can be satisfied client-side because all required fields exist today:

| Visual requirement | Source field |
| --- | --- |
| Active Subscriptions count | `stats.activeSubscriptions` |
| Account Balance value | `stats.walletBalance` |
| Unpaid Invoices count & amount | `stats.unpaidInvoices`, `stats.unpaidAmount` |
| Last 4 digits for "•••• 4829" | `auth.user.id` masked client-side (deterministic, server never sees it as PII — falls back to a `•••• ****` placeholder for safety) |
| Chart & transactions | `chartData`, `recentTransactions` (already passed) |
| Currency code for flags | `stats.currency.currency` (ISO 4217 string already there) |

No controller, route, or service-layer changes.

## Implementation Tasklist (ordered)

1. **Add currency-meta helper** — `resources/js/lib/currencyMeta.ts` exporting `FLAGS_BY_CODE` (EGP→🇪🇬, USD→🇺🇸, SAR→🇸🇦, EUR→🇪🇺, GBP→🇬🇧, AED→🇦🇪, MAD→🇲🇦, IQD→🇮🇶) and `getCurrencyMeta(code)` returning `{ code, flag }`. Plus `resources/js/lib/currencyDisplay.tsx` exporting `<IsoCurrencyAmount amount={n} currency={cur} size="lg|md|sm" />`:
   - Number rendered with `font-feature-settings: "tnum"`, **no `font-mono`** — proportional sans as user requested.
   - Country flag rendered as a small circular emoji span with `aria-hidden`.
   - ISO code rendered next to the number at ~50% size, slate-500.
   - Sizes: `sm` 14px / `md` 18px / `lg` 28px-Bold (used for the wallet card).

2. **Update `EmptyState.tsx`** — add optional `tone` prop. When `tone === 'friendly'`, replace white icon circle with pastel-tinted background (sky-50/70 for tasks, amber-50/70 for projects-by-default) and switch icon to a friendlier Lucide name when passed via `icon` by the parent.

3. **Rewrite `CoreOperationsCards.tsx`:**
   - Delete the `MonitorPlay` / Runtime card entirely.
   - Restructure grid to remain 3 columns on `lg` (with `sm:grid-cols-2`, `grid-cols-1`).
   - **Subscriptions card:** unchanged structurally (no scope-creep).
   - **Charge & Balance (merged wallet) card:**
     - Upper half: linear-gradient slate-900 → indigo-900 → slate-900 card with white text; render `IsoCurrencyAmount` at `size="lg"` inside it.
     - Show `•••• 4829` bottom-left of the gradient block (derived from `stats.currency.id` & `auth.user.id`, see note below).
     - Lower half: a filled primary-button "Add Funds" CTA (`bg-primary hover:bg-primary-hover` plus a `hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200` lift on the whole card).
   - **Billing & Invoices card:**
     - Replace static `bg-slate-200` badge for "Unpaid Invoices" with `<DynamicUnpaidBadge count={stats.unpaidInvoices} />`. The badge: `count === 0` → `bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100`; `count > 0` → `bg-rose-50 text-rose-700 ring-1 ring-rose-100`. Animated `transition-colors duration-300`.

4. **Update `FinancialHistory.tsx`:**
   - Change outer grid from `lg:grid-cols-3 / lg:col-span-2 / lg:col-span-1` → `lg:grid-cols-[65fr_35fr]` (or `grid-cols-1 lg:grid-cols-[13_7]` to keep tailwind purging happy — use the latter).
   - Add `glass` styling to the recharts `Tooltip` contentStyle: `{ background: 'rgba(255,255,255,0.7)', backdropFilter: 'blur(8px)', WebkitBackdropFilter: 'blur(8px)', border: '1px solid rgba(226,232,240,0.6)', borderRadius: '12px', boxShadow: '0 8px 24px -8px rgb(15 23 42 / 0.15)' }`.

5. **Update `PendingInvoicesBanner.tsx`:**
   - Replace the `tabular-nums` + `font-extrabold` wrapper span containing the amount with `<IsoCurrencyAmount amount={amount} currency={stats.currency} size="md" />`. Keep the red/amber/green backgrounds, just remove `font-mono` so it matches the wallet card.

6. **Update `Dashboard.tsx`:**
   - **Delete** the old 2-column financial consolidation (`MetricCard`-based wallet + monthly-subscription pair, lines 72–83 of `Dashboard.tsx`) since the wallet now lives inside the Charge & Balance card.
   - **Add** the new single 3-column row using the redesigned `CoreOperationsCards`.
   - **Re-order**: `PendingInvoicesBanner` → `Welcome heading` → 3-col operational cards → `FinancialHistory` (chart 65% / transactions 35%).

7. **Refactor `Projects/Index.tsx`:**
   - **Folder metaphor:** add a small SVG `rect`/CSS-only tab pseudo-element (`::before` of the Card, position `absolute -top-2 right-6 w-16 h-2 rounded-t-md bg-slate-200`) to suggest a file tab. Avoid extra DOM.
   - **Replace** the `grid-cols-2` Budget/Paid boxes with a new inline row component `ProjectBudgetRow`:
     - `BUDGET: <IsoCurrencyAmount ... size="sm" />` (uses `Wallet` icon, slate-400).
     - `PAID: <IsoCurrencyAmount ... size="sm" />` (uses `CheckCircle2` icon, emerald-400).
   - **Card-level hover:** add `hover:-translate-y-0.5 hover:shadow-lg transition-all duration-200`.

8. **Friendly empty states:**
   - `Tasks.tsx`: pass `icon={Coffee}` (or `Wind`), `tone="friendly"`.
   - `TasksAggregator.tsx`: same (`empty tasks → Coffee`, `no projects filter → Sparkles`).

9. **Workspace dropdown icon coding (`AuthenticatedLayout.tsx`):**
   - Column 1 (Projects): icon squares → `bg-blue-50 text-blue-600`.
   - Column 2 (Financials): icon squares → `bg-emerald-50 text-emerald-600` (note: emerald, not green).
   - Column 3 (Support): icon squares → `bg-orange-50 text-orange-600`.
   - **Active-state tint:** add a thin `border-b border-{pastel}-200` per column header so the open column is visually obvious, and keep the existing per-item active background but soften it (`bg-{pastel}-50/60`).

10. **Unify lucide icon stroke** (system-wide polish item, low-risk):
    - Audit `lucide-react` default import site-wide; lucide already ships `strokeWidth={2}` by default. Add a single Tailwind utility `.icon-md { @apply h-5 w-5; stroke-width: 1.75; }` in `resources/css/app.css` (the codebase uses Tailwind v4 per `components.json` / `vite.config.js`) and refactor *only* the project / dashboard / dropdown icons to use this. Out-of-scope to chase every icon across the admin; leave admin/ERP untouched.

11. **Translations:**
    - Add to `resources/js/translations.json` (both `ar` & `en` namespaces, around lines 1409/11085):
      - `general.account_id_masked` → "Card •••• 4829" / "بطاقة •••• 4829"
      - (existing) `general.budget`, `general.paid` — already present.
    - Run `php artisan translations:export` (build-time script that regenerates the JSON — see `i18n.ts` import at the top of the lib).

## Risks & Mitigations

- **Risk:** Changing `font-mono` on the Wallet / project row could break visual parity with the rest of the site.
  **Mitigation:** scope strictly to the Dashboard + project index (already user-confirmed). Use `font-feature-settings: "tnum"` so digits still align.
- **Risk:** SVG/CSS folder-tab pseudo-element could overflow rounded corners of the Card.
  **Mitigation:** clip with `overflow-hidden` already present on the Card's `flex flex-col overflow-hidden` class.
- **Risk:** Backdrop-blur on the chart tooltip doesn't render in older browsers.
  **Mitigation:** keep the existing solid background fallback by combining `style={{ background: 'rgba(255,255,255,0.78)', backdropFilter: 'blur(8px)' }}` — degrades gracefully.
- **Risk:** Tailwind v4 dynamic class names (e.g., `lg:grid-cols-[13_7]`) get purged.
  **Mitigation:** add the arbitrary value as a literal in the source — Tailwind v4 with `@source` configured (check `vite.config.js` / CSS entry) does JIT for arbitrary bracket values from source. If not, fall back to inline `style={{ gridTemplateColumns: '65fr 35fr' }}` on the wrapper.
- **Risk:** Mascot flag emoji rendering differs across OSes.
  **Mitigation:** add `aria-hidden="true"` and a tooltip via `<span title="...">`. Use Unicode Regional Indicator Pairs only — these are universally supported on Windows 11 / macOS / iOS / Android.
- **Risk:** Removing the Runtime card breaks the `general.musoftware_runtime` translation key used elsewhere.
  **Mitigation:** the translation key is referenced only in `CoreOperationsCards.tsx` (verified via grep); leaving the key in `translations.json` is harmless.

## Validation Plan

1. **Lint / type-check (mandatory, sequential, before commit):**
   - `npm run lint` (defined in `package.json`).
   - `npm run types` / `npx tsc --noEmit` (per `tsconfig.json`).
2. **Unit / feature tests:** `php artisan test --filter=ClientDashboardTest` (or whatever existing test covers `DashboardService::getClientDashboardData()`); only needed if a feature test already exists for it. Adding new tests is out of scope unless explicitly requested.
3. **Manual smoke (developer must run locally):**
   - Boot: `php artisan serve` (use `serve.bat`) and `npm run dev`. Log in as a `client` user with at least one active subscription, one unpaid invoice, EGP currency.
   - Visit `/dashboard`. Verify top row = 3 equal cards · no Runtime card · wallet gradient + last-4 + flag + ISO code · unpaid badge is rose-tinted.
   - Toggle an unpaid invoice to 0 → reload → badge turns emerald.
   - Visit `/projects` → cards have folder tab, BUDGET/PAID inline, hover lift.
   - Visit `/projects/{id}/tasks` with no tasks → coffee icon, pastel circle.
   - Visit `/projects/all-tasks` with filters returning empty → friendly empty state.
   - Open `Workspace` dropdown → Projects column tint = blue, Financials = emerald, Support = orange; active item has soft tint.
   - Hover any Bar in the chart → tooltip is glass-blurred, currency codes match ISO.
4. **Accessibility check:**
   - Wallet gradient card + amount screen-readable (test with VoiceOver / NVDA).
   - Flag emojis are `aria-hidden`.
   - Active menu-item in Workspace dropdown has visible focus ring AND `aria-current`.
5. **Browser matrix (developer):** latest Chrome, Safari (mac), Firefox. Backdrop-blur gracefully degrades on Firefox <103 (no blur, still readable).

## Open Questions / Explicit Out-of-Scope Items

- No DB migration for `currencies.flag_emoji` (user confirmed static JS map).
- No changes to `formatMoney`, `CurrencyDisplay`, `MetricCard` components (they remain `font-mono` for the rest of the site; user confirmed Dashboard-only scope).
- No new tests added (running existing test suite is required, but no new feature test or Playwright test is part of this ticket).
- No backend / API changes.
- The `•••• 4829` mask is generated client-side from `user.id` + `currency.id` (no extra PII query); if the customer wants a real masked PAN from the gateway later, the component can be swapped to take an explicit prop.

## Files Touched (final summary)

```
resources/js/lib/currencyMeta.ts                            (new)
resources/js/lib/currencyDisplay.tsx                        (new)
resources/js/Components/ui/EmptyState.tsx                   (additive `tone` prop)
resources/js/Pages/Client/Dashboard.tsx                     (re-order, drop redundant wallet MetricCard)
resources/js/Pages/Client/Dashboard/Components/CoreOperationsCards.tsx  (remove Runtime; rebuild charge+balance; dynamic badge)
resources/js/Pages/Client/Dashboard/Components/FinancialHistory.tsx     (65/35 split; frosted tooltip)
resources/js/Pages/Client/Dashboard/Components/PendingInvoicesBanner.tsx (swap tabular-nums → IsoCurrencyAmount)
resources/js/Pages/Client/Projects/Index.tsx                (folder tab, inline BUDGET/PAID, hover lift)
resources/js/Pages/Client/Projects/Tasks.tsx                (friendly empty state)
resources/js/Pages/Client/Projects/TasksAggregator.tsx      (friendly empty state)
resources/js/Layouts/AuthenticatedLayout.tsx               (pastel icon coding + column-active tint)
resources/js/translations.json                              (add account_id_masked ar/en)
resources/css/app.css                                       (optional `.icon-md` helper)
```
