# Recurring Notices — Board Sidebar Rail

## Goal
Add a new "recurring notice" entity that reuses the **scheduling logic** of `RecurringCost` (day/week/month/year + recurring_times + week-day/month-day/year-date selectors) but **does NOT create duplicate records/transactions**. It only **evaluates the date criteria**; when a notice is "due today" it is surfaced as notice cards in a **new collapsible left sidebar inside the Board layout** (`AdminBoardLayout`), rendered **only when there are due-today notices** so nothing is missed.

## Core behavior (locked decisions)
- `is_active` notices are evaluated against today's date using the same `isToday()` algorithm as `RecurringCost`.
- No `apply()` / no transaction creation / no `createdBefore` dedup table — pure criteria check at read time.
- Notice fields: `title`, `message` (nullable), `type` (info/success/warning/danger), plus the recurrence fields.
- Due-today notices are computed **server-side** and shared to Inertia as `recurring_notices_today` (already done), so the rail is always accurate.
- The rail lives in **`AdminBoardLayout`** (the Projects Board shell), NOT the nav `AppSidebar`. It is a new left column, collapsible, and conditionally rendered (only when `recurring_notices_today` has items).
- Management (CRUD) happens on normal admin pages under `AdminSidebarLayout`; a nav link is added so notices can be created.

## Already implemented (done — in working tree)
- `database/migrations/2026_07_06_000000_create_recurring_notices_table.php` — table `recurring_notices` (title, message, type, recurring, recurring_times, recurring_times_week/month/year, start_date, current_date, is_active, timestamps, soft deletes).
- `app/Models/RecurringNotice.php` — `isToday()`, `details()`, `isDueToday()`, static `dueToday()`, `scheduleLabel()` (no `apply()`).
- `app/Http/Controllers/Admin/RecurringNoticeController.php` — index/create/store/edit/update/destroy/toggle; shared `validateData()` + `fillNotice()`.
- `routes/web.php` — added `use RecurringNoticeController;` and routes under `business/recurring/notices` named `admin.recurring_notices.*` (index/create/store/edit/update/delete/toggle).
- `app/Http/Middleware/HandleInertiaRequests.php` — shared lazy prop `recurring_notices_today` (admin-gated, returns `[{id,title,message,type}]`).

## Remaining implementation tasks

### 1. Board notices rail (the user's core ask)
- Create `resources/js/Components/Admin/BoardNoticesRail.tsx`:
  - Reads `recurring_notices_today` from `usePage().props`.
  - Returns `null` when the array is empty (rail hidden entirely).
  - Collapsible state (expanded by default; collapsed shows only a thin strip with a count badge + expand button). Persist collapse state in `localStorage` key `board_notices_collapsed`.
  - Renders one card per notice: colored left-border by `type` (info=slate/blue, success=green, warning=amber, danger=red), title (bold), truncated `message`, and a small "Manage" link → `route('admin.recurring_notices.index')`.
  - Sticky, `top-0`, `h-screen`, width ~ `w-72` expanded / `w-10` collapsed, border-r, bg-white, overflow-y-auto, hidden on small screens (`hidden lg:flex`).
- Edit `resources/js/Layouts/AdminBoardLayout.tsx`:
  - Wrap output in a flex row: `<div className="flex min-h-screen bg-slate-50">` → `<BoardNoticesRail />` then `{children}` in a `flex-1 min-w-0` container. Keep `useInertiaNotifications()` and `Head`.
- Edit `resources/js/Pages/Admin/Projects/Board.tsx` if needed: ensure the inner content wrapper stays `flex-1 min-w-0` so the board still scrolls horizontally without overlap (verify `ProjectBoard` lane width behavior after adding the rail).

### 2. Management pages (mirror `RecurringCosts` pages, minus amount/currency)
- `resources/js/Pages/Admin/Business/RecurringNotices/Index.tsx`:
  - `AdminSidebarLayout`, title `Recurring Notices`.
  - Back link to `/admin/finance`.
  - Stats row: `total_active`, `due_today` (from controller `stats`).
  - Table: title + schedule_label, start_date, type (colored badge), `is_due_today` indicator, `is_active` Switch (`admin.recurring_notices.toggle`), Edit + Delete buttons.
  - "Add" button → `admin.recurring_notices.create`.
- `resources/js/Pages/Admin/Business/RecurringNotices/Create.tsx`:
  - Fields: title, message (textarea), type (select), start_date, recurring (day/week/month/year), recurring_times (1–30), plus the conditional multi-selects for week/month/year dates (reuse the exact UI/logic from `RecurringCosts/Create.tsx`).
  - POST → `admin.recurring_notices.store`.
- `resources/js/Pages/Admin/Business/RecurringNotices/Edit.tsx`:
  - Same form, prefilled from `notice` prop. PUT → `admin.recurring_notices.update` (`notice.id`).

### 3. Nav entry point (so notices can be created/managed)
- `resources/js/Components/Admin/AppSidebar.tsx`: add `{ title: "Recurring Notices", url: "/admin/business/recurring/notices" }` to the "Finance & Business" `subItems` (right after "Recurring Costs").

### 4. Translations (i18n — no hardcoded strings)
Add to BOTH `lang/en/general.php` and `lang/ar/general.php` (mirror existing `recurring_cost*` keys, alphabetically placed):
- `recurring_notices` → "Recurring Notices"
- `add_recurring_notice`, `create_recurring_notice`, `edit_recurring_notice`, `edit_recurring_notice_details`, `back_to_recurring_notices`, `admin_recurring_notices`, `no_recurring_notices_found`
- `recurring_notice_added_successfully`, `recurring_notice_updated_successfully`, `recurring_notice_deleted`
- `todays_notices` → "Today's Notices", `due_today` → "Due Today", `manage_notices` → "Manage Notices", `notice_message` → "Notice Message", `notice_type` → "Notice Type"

### 5. Verify & build
- `php artisan migrate` (apply the new migration).
- `php artisan route:list | findstr recurring_notices` (confirm routes registered).
- `npm run lint` and `npm run typecheck` (or the project's lint/build command) to validate new TSX/TS.
- Manual check: create a notice with recurrence `day`, interval 1, is_active → reload the Projects Board page → rail appears with the card; toggle `is_active` off (or change date) → rail disappears.

## Risks / notes
- The rail only appears on the Board page (per decision). Notices are still manageable from the Finance & Business admin nav.
- `dueToday()` loads all active notices each Inertia request for admin users; fine at current scale. If scale grows, cache the result for 1 minute keyed by date.
- Recurrence math is copied verbatim from `RecurringCost` to preserve identical scheduling semantics (including the February month-end edge case).
- Do NOT add an `AddRecurringCosts`-style scheduled command — notices create nothing, so there is nothing to "apply".

## Out of scope
- Notices anywhere other than `AdminBoardLayout`.
- Email/push notifications for notices.
- Per-user/role targeting of notices (all admin users see all due-today notices).
