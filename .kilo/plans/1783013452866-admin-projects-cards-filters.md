# Admin Projects → Cards + Advanced Filters

URL: `https://www.musoftwares.com/admin/projects`
Route: `admin.projects.index` → `Admin/Projects/Index.tsx` (rendered by `ProjectController::index`)

## Goal
Redesign the admin Projects listing from a single table into a **card grid** with a **collapsible advanced-filter panel**, while keeping the existing table available via a **view toggle** (cards = default). Add full faceted filtering (client, owner, multi-status, budget/balance/percentage ranges, date ranges, has-unpaid-invoices, sort + per-page). Keep bulk actions working in both views.

## Decisions (confirmed)
- **View mode:** Cards + Table toggle. Default = cards. Persist `view=grid|table` in URL query string.
- **Filter UI:** Collapsible inline panel above the grid, toggled by a "Filters" button with an active-count badge + "Clear all" action.
- **Filter dimensions:** client (searchable select), owner (select), status (multi-select pills), budget min/max, balance min/max, percentage min/max, start-date range, created-date range, has-unpaid-invoices toggle, sort field/dir, per-page.
- All filters flow through the URL (server-side, Inertia `router.get` with `preserveState`/`replace`) — matches existing `navigate()` pattern. No client-side filtering.

## Backend

### `app/Http/ProjectController.php` → `index()` (extend existing filters)
Extend the query-building block (currently lines 48–96) to accept new params. Keep existing tab/status_filter/search/sort logic intact.

Add whitelisted filter params (server-validated/sanitized):
- `owner_id` (int, nullable) → `where('owner_id', ...)`
- `status[]` (array of open|hold_on|closed) → `whereIn('status', ...)` — **replaces** the single `status_filter` on the frontend, but keep reading `status_filter` for backward-compat (if `status[]` absent, fall back to single). Frontend sends `status[]`.
- `budget_min` / `budget_max` (numeric) → `whereBetween('budget', ...)`
- `balance_min` / `balance_max` (numeric) → `whereBetween('project_balance', ...)`
- `percent_min` / `percent_max` (numeric) → `whereBetween('percentage', ...)`
- `start_from` / `start_to` (date) → `whereBetween('date_start', ...)`
- `created_from` / `created_to` (date) → `whereBetween('created_at', ...)`
- `has_unpaid` (bool) → `whereHas('invoices', fn => where('status','unpaid'))` (only when truthy)

Pagination/sort already supported (`per_page`, `sort`, `dir`).

### New props passed to `Inertia::render('Admin/Projects/Index', [...])`
Add alongside existing props:
- `'owners'` → list of staff who own projects: `User::whereIn('id', Project::whereNotNull('owner_id')->distinct()->pluck('owner_id'))->orderBy('name')->get(['id','name'])->map(fn => ['id'=>..,'name'=>..])`. (Fallback: empty array if none.)
- `'filters'` → expand existing array to include ALL current filter values (mirror request inputs) so the panel can reflect state: `search, client_id, owner_id, status (array), budget_min, budget_max, balance_min, balance_max, percent_min, percent_max, start_from, start_to, created_from, created_to, has_unpaid, sort, dir, per_page, view`.
- Add `'clients'`? No — reuse existing `admin.projects.search-clients` endpoint + `ClientAutocomplete` component for client selection (already present).

### `export()` method
Mirror the new filters into the CSV export query so exported set matches the visible filtered set. Use the same param names.

## Frontend

### `resources/js/types/project.ts`
- Extend `ProjectsIndexProps.filters` to a typed object holding all filter values above (most optional string|number|null, `status: ProjectStatus[]`, `view: 'grid'|'table'`).
- Add `owners: { id: number; name: string }[]` to props.

### New: `resources/js/Pages/Admin/Projects/Components/ProjectCard.tsx`
Standalone card modeled on `Client/Projects/Index.tsx` card style. Props: `project: Project`, plus callbacks `onSelect`, `onEdit`, `onArchive/Restore`, `onDelete`, `onOpenBoard`, `isSelected`.
Contents:
- Header: project name (link → `admin.projects.board.index`), status/archived badge, checkbox (top-left, hover-revealed) for bulk select.
- Client row (avatar + name/email) using existing `Avatar` components.
- Progress bar (`percentage`).
- Budget / Paid / Balance mini-stat tiles (`formatMoney` + `project.currency`).
- Unpaid-invoices alert chip when `counts.invoices_unpaid > 0` (red).
- Counts footer: tasks / reports / files + date range.
- Action buttons row: Board (LayoutDashboard), Edit, Archive/Restore, Delete (reuse existing handlers).

### New: `resources/js/Pages/Admin/Projects/Components/ProjectFiltersPanel.tsx`
Collapsible panel (`<div>` shown/hidden via state, or `Collapsible` from shadcn if available). Controlled local form state synced from `filters` prop; on change calls parent `onChange(partialFilters)` which triggers `navigate()` (debounced for text, immediate for selects/pills/dates).
Fields (grid layout, responsive 2→3→4 cols):
- Search text (also keep top SearchInput in DataTable for table view).
- Client autocomplete (`ClientAutocomplete`, endpoint `admin.projects.search-clients`).
- Owner `<select>` (options from `owners` prop).
- Status multi-select pills (toggle `open|hold_on|closed`, store array).
- Budget min/max numeric inputs.
- Balance min/max numeric inputs.
- Percentage min/max numeric inputs (0–100).
- Start date from/to (`type="date"`).
- Created date from/to (`type="date"`).
- Has-unpaid-invoices checkbox/toggle.
- Sort `<select>` (sortable keys) + dir toggle button.
- Per-page `<select>` (use `perPageOptions`).
- Footer: "Clear all" (resets all + navigates) + active-filter count badge on the trigger button.

### Refactor: `resources/js/Pages/Admin/Projects/Index.tsx`
- Add `view` state from `filters.view ?? 'grid'`; toggle buttons (LayoutGrid / Table icons) call `navigate({ view })`.
- Replace the top toolbar:
  - Tabs (active/archived/all) — keep.
  - "Filters" button (SlidersHorizontal icon) toggles `ProjectFiltersPanel` + active-count badge. Replaces the current single-status `Popover`.
  - Right side: view toggle, Export CSV, Create Project — keep.
- Keep bulk-selection bar (selectedIds logic) — unchanged; works for both views (cards expose checkbox; table keeps its column).
- **Grid view:** responsive `grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-5` of `<ProjectCard>`. Empty state via `EmptyState`.
- **Table view:** keep existing `<DataTable columns=... />` block intact (current code).
- Keep Edit `Dialog` and `ProjectActionsSheet` unchanged.
- Update `navigate()` base object to include all new filter keys + `view` (so toggles/filters preserve the rest).
- Update `exportHref` useMemo to append all active filter params.

### i18n
Add missing `general.*` keys used by the new UI to the relevant language-line source arrays (e.g. `filters`, `clear_all`, `budget_range`, `balance_range`, `percentage_range`, `date_range`, `has_unpaid_invoices`, `owner`, `grid_view`, `table_view`, `start_from`, `start_to`, `created_from`, `created_to`). Use existing `__('general.x')` helper; follow the i18n skill (modular PHP arrays, no hardcoded strings, no global JSON).

## Validation
1. `npm run build` (or `npm run dev`) — TS compiles, no type errors.
2. `php artisan route:list | grep projects` — routes intact.
3. Manual: visit `/admin/projects` → default shows cards; toggle to table; both render.
4. Apply each filter individually + combined → URL updates, results match, panel reflects state; "Clear all" resets; export CSV reflects filters.
5. Bulk select works in grid view (checkbox) and table view; archive/restore/delete confirm.
6. Empty state renders when no results.
7. Mobile: filter panel stacks; grid collapses to 1 col; cards remain usable.

## Out of scope
- Saving filter presets per user (future enhancement).
- Infinite scroll / virtualization (keep server pagination).
- Changing the board/edit/contracts sub-pages.
- Client-facing `/projects` portal (already card-based; untouched).
