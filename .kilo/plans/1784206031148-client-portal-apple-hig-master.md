# Client Portal — Apple HIG Master Redesign (Master Spec Sheet)

**Ticket source:** Master Spec Sheet v2 (Arabic UI/UX ticket). Broadens the prior Apple-HIG dashboard work to the entire client portal (Dashboard + Project Details + Invoice Details + Slide-overs + Global UI system + Mega Menu).

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

This plan covers the **NEW** scope introduced in the Master Spec Sheet. Items already shipped are listed under "Done" for context and will not be re-implemented.

---

## Goal

Bring the rest of the client portal to the same Apple-HIG standard as the new Dashboard:

1. Global UI system: proportional currency typography, unified icon stroke, soft shadows + hover lift, page-fade transitions.
2. Project Details: status pill, team avatar stack, tabbed SPA interface (Tasks / Discussions / Files / Financials), simplified budget row.
3. Invoice Details: paper metaphor with diagonal watermark stamp, prominent Pay CTA.
4. Slide-over panels for Tasks and Tickets (chat-like layout), with deep-link query params.
5. Site-wide friendly empty states with warm copy.
6. Mega menu polish: 12-16px radius, vertical separator hints, 12px gray description text.

---

## Affected Boundaries

| Area | Files (final state) | Notes |
| --- | --- | --- |
| New UI primitives | `resources/js/Components/ui/AvatarStack.tsx`, `SlideOver.tsx`, `WatermarkStamp.tsx`, `PageTransition.tsx` (new) | Reusable across pages |
| Project Details | `Pages/Client/Projects/Show.tsx` (rewrite), `Pages/Client/Projects/tabs/{Tasks,Discussions,Files,Financials}.tsx` (new tabs) | Tab content lazily fetched via Inertia `router.reload({ only })` |
| Invoice Details | `Pages/Client/Billing/InvoicePay.tsx` (apply paper metaphor + watermark) | Front-end only |
| Slide-overs | `Pages/Client/Projects/Tasks.tsx`, `Pages/Client/Projects/TasksAggregator.tsx`, `Pages/Client/Support/Tickets/Index.tsx`, `Pages/Client/Support/Tickets/Show.jsx` | Each row becomes a Sheet trigger with `?open=ID` query |
| Empty states | Audit all `EmptyState` usages (44 hits) — apply `tone="friendly"` + warm copy in client portal scope | Out of scope: Admin/ERP |
| Global UI | `resources/css/app.css` (extend `.icon-md`, `.hover-lift-card`, `.page-fade`), `Layouts/AuthenticatedLayout.tsx` (radius/separators/typography on mega menu) | One Tailwind layer addition |
| Translations | `lang/en|ar/general.php` + `resources/js/translations.json` via `php artisan translations:export` | ~6 new keys |

Out of scope (explicit):
- Admin/ERP/Marketplace/Tools dashboards and the rest of the workspace outside the client portal.
- `CurrencyDisplay`, `MetricCard`, `formatMoney` behavior (still `font-mono` outside the new proportional scope).
- DB schema for a project team/assignees relationship (decision flagged below).
- Removing existing tasks/tickets full-page routes — they remain as deep-link fallbacks (the spec asks to add slide-overs, not delete pages).

---

## Open Questions (need ONE decision each before task 2 starts)

### Q1. Project Details tabs — what data shape is available?
- **Finding:** `resources/js/Pages/Client/Projects/Show.tsx:13` (`ProjectDetail`) has **no `team` / `assignees` field** and **no per-tab data** for Discussions / Files / Financials. The existing routes (`routes/web.php:206-220`) expose `client.projects.tasks.index`, `client.projects.files.index`, `client.projects.comments.index`, but the parent `client.projects.show` controller (`app/Http/Controllers/Client/ClientProjectController.php`) returns only the project itself + `recentReports`.
- **Recommended decision (Q1A, scope-minimal):** keep the tabs as **client-side Inertia partial reloads** — `router.reload({ only: ['tasks','discussions','files','financials'], data: { project, tab } })`. Extend `ClientProjectController::show()` to load the four collections only when the matching query flag is set. **No DB migration.** This preserves Apple's SPA-tab feel while staying within the ticket.
- Alternative (Q1B): introduce a `team` pivot table and add per-project team assignees. **Reject unless explicitly required** — out of stated scope and the team concept is already captured at the ERP tenant level (`Modules/ERP/.../Team/*`).

### Q2. Slide-overs replace or layer over full pages?
- **Finding:** Tasks list is at `Pages/Client/Projects/Tasks.tsx:51` (uses `EmptyState`). Tickets already have a `Show.jsx` page with `ChatWindow`. Tickets index is `Pages/Client/Support/Tickets/Index.jsx` (verified to exist; uses `EmptyState`).
- **Recommended decision (Q2A):** keep both — make row clicks open a `SlideOver` (Radix Dialog wrapper) **and** retain the full-page route as the deep-link fallback. URL pattern: `/projects/{p}/tasks?task={id}` and `/tickets?open={id}`. Spec says "no page transition; stay in context" which slide-overs deliver; full pages still needed for shareable URLs.
- Alternative (Q2B): delete full pages. **Reject** — breaks deep linking, accessibility, and any existing test that hits `/tickets/{id}`.

### Q3. Universal proportional currency?
- **Conflict:** prior plan explicitly scoped `IsoCurrencyAmount` to Dashboard + Projects Index ("other pages keep their existing `font-mono`/symbol-prefix formatting"). Master Spec Sheet now says "إلغاء الرمز `e£` نهائياً… الاعتماد على أكواد العملات العالمية القياسية" — implying site-wide.
- **Recommended decision (Q3A, conservative):** keep `IsoCurrencyAmount` for the redesigned Dashboard + Projects Index + Invoice Details + Pending Banner (the surfaces the spec explicitly calls out). Leave `CurrencyDisplay` (`font-mono`) untouched for Admin/ERP/Marketplace/Subscriptions tables (unchanged scope = no risk). **Document the boundary in the PR description.** Reason: changing 50+ admin pages would exceed the ticket's stated "client portal" focus and risks the lint/typecheck budget.
- Alternative (Q3B): swap everywhere. **Accept only if user explicitly confirms** — large blast radius and the plan called this out as out-of-scope for good reason.

### Q4. Friendly empty-state copy
- The Arabic spec gives one example (`"كل المهام مكتملة بنجاح.. وقت استراحة القهوة! ☕️"`). Implementer will mirror this style for ~6 empty states (Tasks, All Tasks, Projects, Files, Invoices, Tickets). No new i18n key needed unless we want the copy to live in PHP. **Recommended:** keep the warm copy inline in components using existing i18n keys + a `tone="friendly"` prop already wired. Add only `general.all_caught_up_friendly` and `general.empty_projects_friendly` to translations.

### Q5. Page-fade transition library
- Codebase already has `framer-motion ^12.40.0` and `@gsap/react ^2.1.2` in dependencies. **Recommended:** use `framer-motion` (`AnimatePresence` + `motion.div` in `AuthenticatedLayout`) — already integrated across the app. GSAP is reserved for hero landings.

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

## Validation Plan
1. `npm run lint` — must be 0 errors.
2. `npx tsc --noEmit` — must be 0 errors.
3. `php artisan translations:export` — must complete (no PHP syntax error in updated `general.php`).
4. Manual smoke per checklist in task 10.
5. Accessibility spot-check: VoiceOver/NVDA on slide-over + tabs.
