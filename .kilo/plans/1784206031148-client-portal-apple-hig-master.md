# Client Portal — Layout & UX Blueprint (Master Spec Sheet v3)

**Scope refinement (added 2026-07-16):** The user explicitly dropped the conversation about colors, gradients, and pastel tints. The remaining work is **purely layout, spacing, UX flow, and real-world metaphors**. Anything already shipped (gradient on wallet card, pastel column coding, soft shadows, hover lift, frosted chart tooltip, badge tints, icon stroke unification) **stays as-is** and is not revisited. The implementer should not propose new colors, gradients, or pastel hues during this phase.

**Ticket source:** Master Spec Sheet v2 + v3. Broadens the prior Apple-HIG dashboard work to the entire client portal (Dashboard + Project Details + Invoice Details + Slide-overs + Global UI system + Mega Menu).

**Status of prior session (`.kilo/plans/1784205179140-client-dashboard-apple-hig-redesign.md`):** already implemented. Files touched and verified with `npm run lint` + `npx tsc --noEmit` (no errors, one pre-existing hook warning unrelated to this work). Re-confirmed in PR diff:
- `resources/js/lib/currencyMeta.ts`, `currencyDisplay.tsx` (new)
- `resources/js/Components/ui/EmptyState.tsx` (`tone` prop added)
- `resources/js/Pages/Client/Dashboard.tsx` (2-col wallet removed)
- `resources/js/Pages/Client/Dashboard/Components/CoreOperationsCards.tsx` (Runtime removed, charge+wallet merged, dynamic unpaid badge)
- `resources/js/Pages/Client/Dashboard/Components/FinancialHistory.tsx` (65/35 split, frosted tooltip)
- `resources/js/Pages/Client/Dashboard/Components/PendingInvoicesBanner.tsx` (proportional amount)
- `resources/js/Pages/Client/Projects/Index.tsx` (folder-tab metaphor, inline Budget/Paid)
- `resources/js/Pages/Client/Projects/Tasks.tsx`, `TasksAggregator.tsx` (friendly empty states)
- `resources/js/Layouts/AuthenticatedLayout.tsx` (pastel column coding, aria-current, icon-md)
- `resources/css/app.css` (`.icon-md` utility)
- `lang/en|ar/general.php` + `resources/js/translations.json` (`account_id_masked` added via `php artisan translations:export`)

This plan covers the **NEW** scope. Items already shipped are listed under "Done" for context and will not be re-implemented.

---

## Goal

Bring the rest of the client portal to the same layout/spacing/UX standard as the new Dashboard, focused exclusively on **structure and interactions** (not color):

1. Global layout: spacing rhythm, dividers, elevation hierarchy, page-fade transitions.
2. Project Details: status pill position, team avatar stack, tabbed SPA interface (Tasks / Discussions / Files / Financials), inline budget row.
3. Invoice Details: paper-on-desk metaphor with diagonal watermark stamp and prominent Pay CTA.
4. Slide-over panels for Tasks and Tickets (chat-bubble layout), with deep-link query params.
5. Mega menu polish: radius, padding rhythm, separator dividers, description typography size.
6. Site-wide friendly empty states with warm copy.
7. Personal greeting with time-of-day + days-until-renewal hint.

---

## Affected Boundaries

| Area | Files (final state) | Notes |
| --- | --- | --- |
| New UI primitives | `resources/js/Components/ui/AvatarStack.tsx`, `SlideOver.tsx`, `WatermarkStamp.tsx`, `PageTransition.tsx` (new) | Reusable across pages |
| New helpers | `resources/js/lib/greeting.ts` (new) | Time-of-day helper |
| Project Details | `Pages/Client/Projects/Show.tsx` (rewrite), `Pages/Client/Projects/tabs/{Tasks,Discussions,Files,Financials}.tsx` (new tabs) | Tab content lazily fetched via Inertia `router.reload({ only })` |
| Invoice Details | `Pages/Client/Billing/InvoicePay.tsx` (apply paper metaphor + watermark) | Front-end only |
| Slide-overs | `Pages/Client/Projects/Tasks.tsx`, `Pages/Client/Projects/TasksAggregator.tsx`, `Pages/Client/Support/Tickets/Index.tsx`, `Pages/Client/Support/Tickets/Show.jsx` | Each row becomes a Sheet trigger with `?open=ID` query |
| Empty states | Audit all `EmptyState` usages in `Pages/Client/**` — apply `tone="friendly"` + warm copy | Out of scope: Admin/ERP |
| Global UI | `resources/css/app.css` (extend `.hover-lift-card`, `.paper-shadow`), `Layouts/AuthenticatedLayout.tsx` (radius/separators/typography on mega menu) | Layout/elevation utilities only |
| Translations | `lang/en|ar/general.php` + `resources/js/translations.json` via `php artisan translations:export` | ~10 new keys |

Out of scope (explicit):
- Admin/ERP/Marketplace/Tools dashboards and the rest of the workspace outside the client portal.
- `CurrencyDisplay`, `MetricCard`, `formatMoney` behavior (still `font-mono` outside the new proportional scope).
- DB schema for a project team/assignees relationship (decision flagged below).
- Removing existing tasks/tickets full-page routes — they remain as deep-link fallbacks.
- Any new color, gradient, or pastel tint — palette is locked.

---

## Execution Phases (mapping to user proposal)

The user proposed three phases. Honest mapping against the current codebase — phases already shipped in earlier sessions are listed as **DONE** so the implementer (and the user) can see the remaining work in context.

### Phase 1 — Dashboard restructure (3-col grid, merged wallet+charge, removed Runtime) — **DONE**
Implemented and verified (`npm run lint` + `npx tsc --noEmit` = 0 errors) in the earlier session that shipped `1784205179140-client-dashboard-apple-hig-redesign.md`. Files already in production:
- `resources/js/Pages/Client/Dashboard.tsx` (2-col financial consolidation removed).
- `resources/js/Pages/Client/Dashboard/Components/CoreOperationsCards.tsx` (3 cards, Runtime card removed, merged wallet gradient, masked account ID, dynamic unpaid badge).
- `resources/js/Pages/Client/Dashboard/Components/FinancialHistory.tsx` (65/35 split, frosted recharts tooltip).
- `resources/js/Pages/Client/Dashboard/Components/PendingInvoicesBanner.tsx` (proportional amount via `IsoCurrencyAmount`).
- `resources/js/lib/currencyMeta.ts` + `currencyDisplay.tsx` (new shared primitives).
- `lang/en|ar/general.php` + `resources/js/translations.json` (`account_id_masked` via `php artisan translations:export`).

**Remaining Phase 1 micro-tasks** (purely layout/copy):
1. **Time-of-day greeting** — `resources/js/lib/greeting.ts` (new tiny helper): `morning` 5:00–11:59, `afternoon` 12:00–16:59, `evening` 17:00–21:59, `night` 22:00–4:59. Returns `{ label, emoji }`. Use in `Dashboard.tsx` line 64: `{greeting.label}, {user?.name} {greeting.emoji}` with new translation keys `general.greeting_morning` ("Good morning") / `_afternoon` / `_evening` / `_night`. Honor the locale (`document.documentElement.lang`). Layout only — no color change.
2. **Days-until-renewal hint** on the Active Subscriptions card. Add `nextRenewalAt?: string|null` to `DashboardStats` (backend supplies it from the soonest active subscription). Render a small text line under the count, using a weight contrast (e.g. `text-xs font-medium`) and a left border / small glyph to indicate urgency when `N <= 3`. New translation key `general.renews_in_days` with `:count` placeholder.

### Phase 2 — Mega menu polish + unified icon system — **PARTIALLY DONE**
- **DONE:** pastel column coding (blue / emerald / orange), `aria-current` on active items, `icon-md` utility class added to `resources/css/app.css`, `focus-visible` rings on each link.
- **Remaining Phase 2 polish** (layout only — no color):
  - Increase `DropdownMenuContent` radius from `rounded-xl` to `rounded-2xl` and widen padding `p-4` → `p-5` between columns.
  - Add vertical separator hint via `divide-y divide-slate-100` on each column's items container (subtle, no color change).
  - Larger drop shadow: `shadow-[0_24px_48px_-12px_rgb(15_23_42_/0.18)]` (size/spread only — palette stays).
  - Description typography: ensure all description `<p>` tags use `text-[12px] text-slate-500 leading-normal` (size rhythm only).

### Phase 3 — Projects + Project Details + Slide-overs — **PARTIALLY DONE / NEXT FOCUS**
- **DONE:** Project Index card redesign — folder-tab metaphor (`::before` overlay), inline `ProjectBudgetRow` (Wallet + CheckCircle2 icons), hover-lift card transition, friendly empty state in `Pages/Client/Projects/Index.tsx`.
- **REMAINING (this is where the next sprint starts):**
  1. **Extract `ProjectBudgetRow`** to `resources/js/Components/ProjectBudgetRow.tsx` so both Index and Show share it.
  2. **Project Details tabs** — `Pages/Client/Projects/Show.tsx` rewrite + four new tab components under `Pages/Client/Projects/tabs/`.
  3. **Team AvatarStack** in project header — **blocked by Q1**.
  4. **Slide-overs** on Tasks list, TasksAggregator, and Tickets index.
  5. **Invoice Details** paper metaphor + watermark + sticky Pay CTA.
  6. **Chat-bubble layout** inside the SlideOver body for Tickets (reuse `ChatWindow` already imported by `Show.jsx`).
  7. **`/messages` route build** (Q6A — file is missing; build from `MessagesController::index()` shape).
  8. **Site-wide friendly empty states audit** for the client portal scope.
  9. **Page-fade transition** wrap on `AuthenticatedContent`.

---

## Open Questions (ONE decision blocks Phase 3 — the rest are resolved)

### Q1. Project Details tabs + Team AvatarStack — what data shape is available?
- **Finding:** `resources/js/Pages/Client/Projects/Show.tsx:13` (`ProjectDetail`) has **no `team` / `assignees` field** and **no per-tab data** for Discussions / Files / Financials. The existing routes (`routes/web.php:206-220`) expose `client.projects.tasks.index`, `client.projects.files.index`, `client.projects.comments.index`, but the parent `client.projects.show` controller (`app/Http/Controllers/Client/ClientProjectController.php`) returns only the project itself + `recentReports`.
- **Recommended decision (Q1A, scope-minimal):**
  - **Tabs:** keep as **client-side Inertia partial reloads** — `router.reload({ only: ['tasks','discussions','files','financials'], data: { project, tab } })`. Extend `ClientProjectController::show()` to load the four collections only when the matching query flag is set. **No DB migration.** Preserves the SPA-tab feel while staying in scope.
  - **Team avatar stack (project header):** do **not** introduce a pivot table. Read `project.team` only if `app/Models/Project.php` already has a relation; otherwise pass `team: []`. `AvatarStack` must render an "Unassigned" placeholder so the page never looks broken. Defer proper team data to a follow-up ticket.
- Alternative (Q1B): introduce a `team` pivot table and add per-project team assignees. **Reject** — out of stated scope and the team concept is already captured at the ERP tenant level (`Modules/ERP/.../Team/*`).

### Q2. Slide-overs replace or layer over full pages?
- **Finding:** Tasks list is at `Pages/Client/Projects/Tasks.tsx:51` (uses `EmptyState`). Tickets already have a `Show.jsx` page with `ChatWindow`. Tickets index is `Pages/Client/Support/Tickets/Index.jsx`.
- **Recommended decision (Q2A):** keep both — make row clicks open a `SlideOver` (Radix Dialog wrapper) **and** retain the full-page route as the deep-link fallback. URL pattern: `/projects/{p}/tasks?task={id}` and `/tickets?open={id}`.
- Alternative (Q2B): delete full pages. **Reject** — breaks deep linking, accessibility, and any existing test that hits `/tickets/{id}`.

### Q3. Universal proportional currency?
- **Recommended decision (Q3A, conservative):** keep `IsoCurrencyAmount` for the redesigned Dashboard + Projects Index + Invoice Details + Pending Banner (the surfaces the spec explicitly calls out). Leave `CurrencyDisplay` (`font-mono`) untouched for Admin/ERP/Marketplace/Subscriptions tables.
- Alternative (Q3B): swap everywhere. **Accept only if user explicitly confirms** — large blast radius.

### Q4. Friendly empty-state copy
- **Recommended:** keep warm copy inline in components using existing i18n keys + `tone="friendly"` prop (already wired). Add only `general.all_caught_up_friendly` and `general.empty_projects_friendly` to translations.

### Q5. Page-fade transition library
- **Recommended:** `framer-motion` (`AnimatePresence` + `motion.div` in `AuthenticatedLayout`) — already integrated. GSAP reserved for hero landings.

### Q6. `/messages` route is broken — what should the implementer do?
- **Finding:** `routes/web.php:947-949` returns `Inertia::render('Client/Messages/Index')` but **no such file exists**. `routes/web.php:1002` redefines `messages.index` inside an admin middleware group.
- **Recommended decision (Q6A):** build `resources/js/Pages/Client/Messages/Index.tsx` using the data shape from `MessagesController::index()` (`conversations` collection with `last_message` preview + `unread_count`, plus `users` list for direct chat).
- Alternative (Q6B): redirect `/messages` to `/notifications` and remove the broken route. **Reject unless** direct chat isn't a priority — Mega menu promises it as a first-class feature.

---

## Ordered Tasklist

> **Hard rule:** task 1 (new shared primitives) must complete before any consumer touches tasks 2–7. Backend additions (task 8) can run in parallel with frontend tasks.

### 1. Add shared UI primitives (foundation)
- **`resources/js/Components/ui/AvatarStack.tsx`** — takes `members: { id, name, avatar_url?, role? }[]`, renders overlapping circular `<Avatar>` from Radix. Last slot collapses to `+N`. Each avatar has a Tooltip with `name · role`.
- **`resources/js/Components/ui/SlideOver.tsx`** — thin wrapper over the existing `resources/js/Components/ui/sheet.tsx` (Radix Dialog). Right-side `sm:max-w-md`, `backdrop-blur-sm bg-black/30`, focus trap, ESC + overlay click to close. Exposes `open`, `onOpenChange`, `title`, `description` props. Honors `?open=ID` deep-link via `useEffect` reading `window.location.search`.
- **`resources/js/Components/ui/WatermarkStamp.tsx`** — absolutely-positioned diagonal text with `text-[120px] font-black uppercase tracking-widest`, color from `tone: 'paid'|'unpaid'|'overdue'|'draft'` → emerald/rose/amber/slate-300, `opacity-15 rotate-[-30deg] pointer-events-none select-none aria-hidden="true"`. Hidden on mobile (`hidden md:block`).
- **`resources/js/Components/ui/PageTransition.tsx`** — wraps children in `<motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} exit={{ opacity: 0 }} transition={{ duration: 0.2 }}>`. Drop into `AuthenticatedContent` so every Inertia page fades.

### 2. Global UI system — `resources/css/app.css`
Extend `@layer utilities`:
- `.icon-md` — already present (keep).
- `.hover-lift-card` — `@apply transition-all duration-200 hover:-translate-y-0.5 hover:shadow-[0_10px_30px_rgba(0,0,0,0.04)];`.
- `.paper-shadow` — `@apply shadow-[0_10px_30px_rgba(0,0,0,0.04)];` (default for `.card` replacement on Invoice Details).
- `.page-fade-enter` / `.page-fade-leave` — handled by Framer Motion (no CSS needed).
- Add `--shadow-card-soft: 0 10px 30px rgba(0,0,0,0.04);` to `:root` so JS-injected shadows can reference it.

### 3. Mega menu polish — `resources/js/Layouts/AuthenticatedLayout.tsx`
- Increase `DropdownMenuContent` radius to `rounded-2xl` (`border-radius: 16px`) and widen `p-4` → `p-5` between columns.
- Add vertical separator hint: per column a `divide-y divide-slate-100` on the items container.
- Tighten description typography: `<p className="text-xs text-slate-500 leading-normal">` already in use; verify `text-[12px]` per spec and add `text-[#6c757d]` fallback if `slate-500` looks too dark in QA.
- Drop shadow upgrade: add `shadow-[0_24px_48px_-12px_rgb(15_23_42_/0.18)]` to the content container.
- Active-state tint already shipped (blue/emerald/orange pastel). Keep.

### 4. Project Details — tabbed SPA + team avatars (Q1A scope)
- Extend `ClientProjectController::show()` to accept `?tab=tasks|discussions|files|financials` and return only the relevant collection (`Inertia::render(...)` with the matching `with` block). Pure controller-level change.
- Rewrite `resources/js/Pages/Client/Projects/Show.tsx`:
  - Header: title + `StatusBadge` (already partially there) + `<AvatarStack>` (max 5 + overflow).
  - Tabs strip: Radix `@radix-ui/react-tabs` is in deps. State persists in URL query.
  - Tab panels lazy-fetch via `router.reload({ only: ['tabContent'], data: { tab } })` triggered on tab change.
  - Each tab panel is a small component in `Pages/Client/Projects/tabs/`. `Tasks` tab delegates to existing `Tasks` page logic but in a "no chrome" mode (no AuthenticatedLayout).
  - `Financials` tab shows `Budget / Paid Invoices / Pending Invoices / Progress` (already in `Show.tsx:69-74`) — but using `IsoCurrencyAmount` (already wired).
  - Inline budget row inside the project header (per spec §1 last bullet) — replicates the new `ProjectBudgetRow` already shipped on the Index card. **Extract `ProjectBudgetRow` to `resources/js/Components/ProjectBudgetRow.tsx`** so both pages share it.

### 5. Invoice Details — paper metaphor + watermark
- `resources/js/Pages/Client/Billing/InvoicePay.tsx`:
  - Outer container: `<div className="bg-slate-100 p-6 sm:p-10 rounded-3xl">` (paper on table metaphor).
  - Invoice card: `bg-white rounded-2xl shadow-[0_10px_30px_rgba(0,0,0,0.04)] p-8 sm:p-10 relative overflow-hidden`.
  - Render `<WatermarkStamp tone={invoice.status} />` absolutely positioned, top-right, behind content.
  - For `status !== 'paid'` invoices, hoist a sticky `Pay Invoice` CTA below the breadcrumb: `<div className="sticky top-20 z-10 flex justify-end"><Button>Pay Invoice</Button></div>`.
  - Drop `font-mono` from the invoice header and `unit_price` cells; replace with `font-sans font-medium tabular-nums` so digits still align without the monospace look. The line items table keeps `text-end tabular-nums` (no proportional tweak — Excel-style alignment is appropriate here).

### 6. Slide-over integration — Tasks & Tickets
- **`resources/js/Pages/Client/Projects/Tasks.tsx`**:
  - Each `<Card>` becomes a `SheetTrigger` (wrap with Radix `asChild`). Opens `SlideOver` with the task details.
  - Detail body reuses the existing comment-style layout if `task.description` exists, plus an inline list of sub-todos.
  - On close, restore focus to the row.
  - Empty state remains `tone="friendly"` (already shipped).
- **`resources/js/Pages/Client/Projects/TasksAggregator.tsx`** — same conversion for each `TaskRow`.
- **`resources/js/Pages/Client/Support/Tickets/Index.jsx`** — convert each row into a Sheet trigger; body uses `ChatWindow` (already imported by `Show.jsx`).
- **`resources/js/Pages/Client/Support/Tickets/Show.jsx`** — **leave as-is**; it remains the deep-link target. Add a small "Open in page" link inside the SlideOver if the user wants full chat composer features.

### 7. Friendly empty states — site-wide audit
- Audit `EmptyState` usages in client portal (`Pages/Client/**`). For each, apply `tone="friendly"` and pick the matching Lucide icon (already shipped for Tasks/All Tasks). Add warm Arabic/English copy inline where the generic "no X yet" feels cold.
- Specifically target: `Pages/Client/Projects/Index.tsx` (FolderKanban → keep neutral because project list is not a "rest" surface), `Pages/Client/Projects/Files.tsx` (Paperclip, friendly tone), `Pages/Client/Support/Tickets/Index.jsx`, `Pages/Client/Billing/Invoices.tsx` (rare empty state).

### 8. Backend tweak (parallel)
- **`ClientProjectController::show()`** — accept `?tab=...` query, load the tab's collection only when requested. No DB migration.
- Add `team` loader (Q1A scope-minimal): if `project.users` relation exists on the model, eager-load; otherwise skip and the AvatarStack gracefully renders "Unassigned" placeholder. **Verify by reading `app/Models/Project.php` before implementing** — if no user relation exists, render `team=[]` and add a TODO comment.

### 9. Translations
- Add to `lang/en/general.php` + `lang/ar/general.php`:
  - `empty_tasks_friendly`: "All done — coffee break time!" / "كل المهام مكتملة بنجاح.. وقت استراحة القهوة!"
  - `empty_tickets_friendly`: "Quiet inbox. Need anything? We're here." / "لا توجد تذاكر حالياً. نحن هنا متى احتجتنا."
  - `pay_invoice`: "Pay Invoice" / "دفع الفاتورة"
  - `card_status_paid`, `card_status_unpaid`, `card_status_overdue`, `card_status_draft` (for watermark alt text)
- Run `php artisan translations:export` (already wired in `package.json` `predev`/`prebuild`).

### 10. Validation
- `npm run lint` → must show 0 errors. The pre-existing `ProjectBoard.tsx:357` warning is unrelated; leave it.
- `npx tsc --noEmit` → 0 errors.
- Smoke checklist (developer runs locally with a client account):
  - [ ] `/dashboard` unchanged (regression check).
  - [ ] `/projects` — folder tab + inline Budget/Paid intact.
  - [ ] `/projects/{id}` — tabs switch via URL, AvatarStack renders (or "Unassigned" fallback).
  - [ ] `/projects/{id}/tasks` — clicking a row opens SlideOver; ESC closes; `?task=ID` deep-link works.
  - [ ] `/tickets` — same SlideOver behavior.
  - [ ] `/billing/invoices/{uuid}` — paper metaphor + watermark + sticky Pay CTA (when unpaid) visible.
  - [ ] Workspace dropdown — radius feels softer, descriptions read at 12px.
  - [ ] Page transitions fade in < 250 ms.
- Accessibility:
  - SlideOver focus trap, ESC, restore focus on close, `aria-label` on trigger button.
  - Watermark `aria-hidden="true"`.
  - Tab strip uses Radix Tabs (handles `role="tablist"`, arrow keys, aria-selected).

---

## Risks & Mitigations

- **Risk:** Slide-over adoption breaks if the existing pages assume full-page lifecycle (e.g. ChatWindow needs DOM reflow). **Mitigation:** wrap each row click in a controlled `Sheet`; do not unmount the underlying list. Re-test deep links to `/tickets/{id}` after.
- **Risk:** Tab partial reload thrashes the network if user clicks fast. **Mitigation:** debounce 150 ms + show skeleton inside the tab panel.
- **Risk:** AvatarStack renders broken avatars when the user has no `avatar_url`. **Mitigation:** fall back to Radix `AvatarFallback` showing initials; never render a broken `<img>`.
- **Risk:** Watermark stamp obscures invoice content on small screens. **Mitigation:** `hidden md:block` per spec; mobile keeps the badge-style status only.
- **Risk:** Universal proportional currency (Q3B) chosen would touch Admin/ERP and is incompatible with `formatMoney`'s DB `string_format`. **Mitigation:** unless user explicitly confirms Q3B, keep `CurrencyDisplay` (`font-mono`) intact outside the client-portal scope.
- **Risk:** Team data shape (Q1) — if `project.users` doesn't exist on the Eloquent model, AvatarStack renders empty. **Mitigation:** graceful empty state ("Unassigned") + follow-up ticket for proper team data.

---

## Files Touched (final summary)

```
resources/js/Components/ui/AvatarStack.tsx               (new)
resources/js/Components/ui/SlideOver.tsx                 (new)
resources/js/Components/ui/WatermarkStamp.tsx            (new)
resources/js/Components/ui/PageTransition.tsx            (new)
resources/js/Components/ProjectBudgetRow.tsx             (extract — reuse on Index + Show header)
resources/js/Pages/Client/Projects/Show.tsx              (rewrite — tabs + avatars + paper header)
resources/js/Pages/Client/Projects/tabs/Tasks.tsx        (new)
resources/js/Pages/Client/Projects/tabs/Discussions.tsx  (new)
resources/js/Pages/Client/Projects/tabs/Files.tsx        (new)
resources/js/Pages/Client/Projects/tabs/Financials.tsx   (new)
resources/js/Pages/Client/Projects/Tasks.tsx             (SlideOver trigger)
resources/js/Pages/Client/Projects/TasksAggregator.tsx   (SlideOver trigger)
resources/js/Pages/Client/Projects/Files.tsx             (friendly empty)
resources/js/Pages/Client/Billing/InvoicePay.tsx         (paper + watermark + sticky CTA)
resources/js/Pages/Client/Support/Tickets/Index.jsx      (SlideOver trigger + friendly empty)
resources/js/Pages/Client/Support/Tickets/Show.jsx       (no change — deep-link fallback)
resources/js/Layouts/AuthenticatedLayout.tsx             (radius/shadow/typography on mega menu + PageTransition wrap)
resources/css/app.css                                    (extend utility layer)
app/Http/Controllers/Client/ClientProjectController.php (?tab=... partial-load + optional team)
lang/en/general.php + lang/ar/general.php                 (~6 new keys)
resources/js/translations.json                           (regenerated via php artisan translations:export)
```

---

## Out-of-Scope (explicit)
- Admin/ERP/Marketplace dashboards.
- Removing `formatMoney`, `CurrencyDisplay`, `MetricCard`.
- Project `team` pivot table (Q1B).
- Slide-over deletion of full Tasks/Tickets pages (Q2B).
- Site-wide currency re-format (Q3B).
- New automated Playwright tests (matches prior plan).
- DB migration for any currency flag.

---

## Route-by-Route Audit (added 2026-07-16)

The user asked to scan the current state of nine specific routes so the implementer can prioritize. Findings below; all references verified against the working tree.

### 1. `/dashboard` — `App\Http\Controllers\DashboardController@index`
- **Status:** fully redesigned in prior session.
- **File:** `resources/js/Pages/Client/Dashboard.tsx`, plus `Dashboard/Components/{CoreOperationsCards,FinancialHistory,PendingInvoicesBanner}.tsx`.
- **Tech:** `font-sans` proportional display via `IsoCurrencyAmount`; `lg:grid-cols-[65fr_35fr]` via Tailwind arbitrary value (verified in code); recharts `contentStyle` frosted (`rgba(255,255,255,0.7)` + `backdropFilter:'blur(8px)'`); dynamic unpaid badge swaps between emerald/rose.
- **Remaining work:** none unless Q3 universal proportional currency is approved (out of scope today).

### 2. `/projects` — `ClientProjectController@index`
- **Status:** redesigned in prior session (folder tab metaphor, inline `ProjectBudgetRow`, friendly empty state via `tone="friendly"`).
- **File:** `resources/js/Pages/Client/Projects/Index.tsx`.
- **Remaining work:** extract `ProjectBudgetRow` to `resources/js/Components/ProjectBudgetRow.tsx` so Show can reuse it (Q1A scope, see tasks below).

### 3. `/projects/{project}/tasks` — `ClientProjectTaskController@tasksIndex`
- **Status:** friendly empty state already shipped. List items are plain `<Card>` rows; no click handler today.
- **File:** `resources/js/Pages/Client/Projects/Tasks.tsx`.
- **Remaining work:** convert each row into a `SheetTrigger` for the SlideOver (task 6); add inline `projectBudget` metadata.

### 4. `/messages` — `MessagesController@index`
- **Status:** **broken / unreached**. The route renders `Inertia::render('Client/Messages/Index')` (`routes/web.php:947-949`) but **no such file exists** at `resources/js/Pages/Client/Messages/Index.tsx`. Inertia will 500 on visit. There is also a second route definition at `routes/web.php:1002` under an admin-style middleware that returns `messages.index` — duplicate names cause route shadowing.
- **Finding confirmed:** `glob Pages/Client/Messages` → no files.
- **Decision needed (NEW Q6):** either (a) build the missing `Messages/Index.tsx` from the data shape in `MessagesController::index()` (`conversations`, `users` for direct chat), or (b) redirect `/messages` to `/notifications` and remove the broken route. Recommend **(a) — build it** because the Mega menu exposes it as a first-class nav item.
- **Remaining work:** implement `Pages/Client/Messages/Index.tsx` (two-column layout: conversation list + active chat), wire SlideOver for opening a conversation if scope expands, polish with empty state + paper-card styling.

### 5. `/billing/invoices` — `InvoiceController@index`
- **Status:** uses legacy `CurrencyDisplay` (`font-mono`); invoice number column is `font-mono` (`resources/js/Pages/Client/Billing/Invoices.tsx:73`); no proportional helper.
- **Remaining work:** swap to `IsoCurrencyAmount` for the Amount / Paid / Remaining cells (small) and apply hover-lift on rows; sticky filter row on scroll.

### 6. `/financial/transactions` — `FinancialController@transactions`
- **Status:** uses hand-rolled `Number(wallet?.balance).toFixed(2)` (`resources/js/Pages/Client/Financial/Transactions.jsx:33,46,60`) — inconsistent across the three cards. Negative balance styling present but no flag emoji.
- **Remaining work:** replace the three balance values with `<IsoCurrencyAmount size="lg">`; adopt `hover-lift-card` on the three summary cards; convert the inline `<table>` empty fallback into `<EmptyState tone="friendly" icon={History}>` with warm copy.

### 7. `/financial/withdrawals` — `FinancialController@withdrawals`
- **Status:** uses `formatMoney` (`font-mono`) and the legacy `Modal` component. `confirm()` call in `PayoutMethods.jsx:71` is **forbidden by HIG skill** ("NO Native Prompts or Alerts").
- **Remaining work:** replace `formatMoney` with `IsoCurrencyAmount`; swap `confirm()` for a Shadcn `AlertDialog`; switch `Modal` → `Sheet` for the request-withdrawal form; align card styling with the new soft-shadow + hover-lift pattern; friendly empty state in the table fallback.

### 8. `/financial/payout-methods` — `PayoutMethodController@index`
- **Status:** uses `formatMoney`/`font-mono` indirectly; `confirm()` again; create/edit form inside the legacy `Modal`.
- **Remaining work:** replace `confirm()` with Shadcn `AlertDialog`; convert create/edit `Modal` into a `Sheet`; adopt consistent card shadow + hover lift; friendly empty state already mostly there — apply `tone="friendly"` to the empty `<Card>`.

### 9. `/referrals` — `ReferralController@index` (and `.earns`, `.registers`)
- **Status:** `Pages/Client/Dashboard/Referrals/Index.tsx` uses legacy `text-gray-*` Tailwind classes (inconsistent with the slate palette used elsewhere), `font-mono` on the slug/embed inputs (intentional — they are code/IDs), and `Tabs` to navigate to the three sub-pages via `router.visit(route(...))` (this **forces a full Inertia reload** on every tab click, breaking the SPA feel).
- **Remaining work:** rebuild as a single-page Tabs layout (the three sub-pages are tiny — fold them into tab panels with `router.reload({ only })`); migrate all `text-gray-*` → `text-slate-*`; keep `font-mono` on slug/embed inputs (these are copyable IDs — appropriate); switch the tab navigation away from full-page `router.visit` to Inertia partial reload or local state; add `friendly` empty state to `Referrals/Earns.tsx` and `Referrals/Registers.tsx`.

### Audit-wide findings
- **Currency consistency:** the prior plan scoped `IsoCurrencyAmount` to Dashboard + Projects Index. The Master Spec Sheet implies site-wide. Of the audited routes, **#5, #6, #7 should adopt it** (small numeric values shown to clients); #8 + #9 don't render currency directly except in commission totals. Decision aligns with the plan's recommended Q3A.
- **Accessibility regressions to fix:**
  - `confirm()` calls in `Withdrawals.jsx` and `PayoutMethods.jsx` — must become `AlertDialog`.
  - Mega menu refers to `#4 /messages` which currently 500s — visible bug.
  - `Notifications/Index.tsx` still uses `text-gray-*` — out of audited scope but flag for future sweep.
- **Component reuse opportunities:**
  - `ProjectBudgetRow` extraction (#2 ↔ Project Show header).
  - Soft-shadow card utility (`.hover-lift-card` + `.paper-shadow`) covers #1, #5, #6, #7, #8 cards.
  - `IsoCurrencyAmount` covers all currency displays in #5, #6, #7.

### Additional Open Question (NEW Q6, surfaced by this audit)

**Q6. `/messages` route is broken — what should the implementer do?**
- **Finding:** `routes/web.php:947-949` returns `Inertia::render('Client/Messages/Index')` but no file exists. Also `routes/web.php:1002` redefines `messages.index` inside an admin middleware group, shadowing the prior route at runtime depending on middleware order.
- **Recommended decision (Q6A):** build `resources/js/Pages/Client/Messages/Index.tsx` using the data shape from `MessagesController::index()` (`conversations` collection with `last_message` preview + `unread_count`, plus `users` list for direct chat). Keep `/messages` as the client-facing route; the admin-side duplicate at line 1002 should be flagged for a separate cleanup ticket.
- **Alternative (Q6B):** redirect `/messages` to `/notifications` and remove the broken route. **Reject unless** the team confirms direct chat isn't a priority — the Mega menu promises it as a first-class feature.
- **Blocking dependency:** Q6A unblocks the slide-over task in the existing plan (SlideOver can also wrap individual conversation details).

---

## Validation Plan

1. `npm run lint` — must be 0 errors.
2. `npx tsc --noEmit` — must be 0 errors.
3. `php artisan translations:export` — must complete (no PHP syntax error in updated `general.php`).
4. Manual smoke per checklist in task 10.
5. Accessibility spot-check: VoiceOver/NVDA on slide-over + tabs.
