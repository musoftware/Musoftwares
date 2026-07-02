# Plan: Shortlink Module

## Goal
A new `Shortlink` module (nwidart/laravel-modules) providing a general-purpose internal URL shortener. First use case: shorten the very long **shared-board** signed URL so it is shareable. Shortens any URL (internal signed links, marketing, external).

## Stack (confirmed)
Laravel 12 / PHP 8.4, nwidart/laravel-modules 12, Inertia 2.0 + React 18.2 + TS 5, Tailwind 4.3 / Shadcn, Ziggy, MySQL (utf8mb4). Modules in `Modules/`, gated by `modules_statuses.json`, tables prefixed `{module}_*`.

## Decisions
1. **Scope:** General-purpose shortener (any URL). Shared-board is the first user.
2. **Access/UI:** Admin-only CRUD under `AdminSidebarLayout`; pages in `resources/js/Pages/Admin/Shortlinks/`.
3. **Analytics:** Basic — `clicks` counter only (no per-click table). Extensible later without rewrite.
4. **Route:** Public `GET /s/{code}`, auto-generated high-entropy base62 code only (no custom aliases). 302 redirect (no browser caching → clicks accurate, target editable).
5. **Bitly:** Standalone now. Follow-up task to refactor `app/Services/QrCodeService.php::shortenUrlBitly()` to delegate here.
6. **Shared-board:** Add auto-shorten hook where the signed URL is generated.

### Security model (critical)
The shared-board URL is protected by an HMAC signature in the query string. The shortener stores the **full destination URL (signature included)** and redirects to it server-side. Therefore the **short code becomes the access credential (bearer token)** — it MUST be high-entropy and unguessable. Spec: base62, 10 chars (~60 bits), DB-unique with retry-on-collision. The destination still enforces its own signature on arrival.

## Data model
Table `shortlink_links`:
- `id` (bigIncrements)
- `short_code` (string, unique, indexed) — base62, 10 chars
- `destination_url` (text) — full target incl. signature
- `label` (string, nullable) — admin memo
- `created_by_user_id` (foreignId → users, nullable for system-generated)
- `is_active` (boolean, default true)
- `clicks` (unsignedBigInteger, default 0)
- `expires_at` (timestamp, nullable)
- `source_type`, `source_id` (nullableMorphs — e.g. `Project` for shared-board links)
- `timestamps`, `softDeletes()`

Index: unique on `short_code`.

## Data flow
- **Create:** admin form → `ShortlinkService::create(['destination_url', 'label', 'expires_at', 'source' => model|null])` → generate code, persist, return full short URL.
- **Redirect:** `GET /s/{code}` → `ShortlinkRedirectController` → resolve active, non-expired, non-deleted link → `redirect($destination, 302)` → atomic increment of `clicks`.
- **Shared-board hook:** `app/Http/Controllers/Admin/ProjectController.php:281` — after `URL::signedRoute('shared-board.show', [...])`, call `ShortlinkService::create(...)` with `source = $project`; pass `short_url` to the Inertia response/props that power the share UI. Locate the exact render/return point that surfaces `shareUrl` to the frontend and add `short_url` alongside it.

## Module structure (follow Marketplace pattern)
```
Modules/Shortlink/
  module.json
  composer.json
  Providers/
    ShortlinkServiceProvider.php        (register RouteServiceProvider, load migrations)
    RouteServiceProvider.php            (web group, namespace Modules\Shortlink\Http\Controllers)
  Http/
    Controllers/
      ShortlinkController.php           (admin index/create/store/destroy)
      ShortlinkRedirectController.php   (public redirect)
    Requests/
      StoreShortlinkRequest.php         (url validation, optional expires_at/label)
  Models/
    ShortlinkLink.php                   (casts, fills, morphTo source, scopes: active, notExpired)
  Services/
    ShortlinkService.php                (create, resolve, recordClick, generateUniqueCode)
  Policies/
    ShortlinkLinkPolicy.php             (admin-only before())
  Database/Migrations/
    2026_07_02_000001_create_shortlink_links_table.php
  routes/
    web.php                             (public /s/{code}; admin /admin/shortlinks/*)
  Tests/Feature/
    ShortlinkRedirectTest.php
    ShortlinkAdminTest.php
```

## Routes (`Modules/Shortlink/routes/web.php`)
```php
// Public — no auth
Route::get('/s/{code}', [ShortlinkRedirectController::class, 'redirect'])
    ->name('shortlink.redirect');

// Admin — match existing admin middleware convention (auth + role:admin)
Route::prefix('admin/shortlinks')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->group(function () {
        Route::get('/',  [ShortlinkController::class, 'index'])->name('admin.shortlinks.index');
        Route::post('/', [ShortlinkController::class, 'store'])->name('admin.shortlinks.store');
        Route::delete('{shortlink}', [ShortlinkController::class, 'destroy'])->name('admin.shortlinks.destroy');
        Route::post('{shortlink}/toggle', [ShortlinkController::class, 'toggle'])->name('admin.shortlinks.toggle');
    });
```
Register module in `modules_statuses.json` (add `"Shortlink": true`).

## Frontend
- `resources/js/Pages/Admin/Shortlinks/Index.tsx` — table (code, short URL copy button, destination, label, clicks, status, expiry, delete/toggle), create dialog (`StoreShortlinkRequest` fields). Wrap in `AdminSidebarLayout`.
- `resources/js/Pages/Admin/Shortlinks/Show.tsx` (optional for v1) — detail + click count.
- Add nav entry in `resources/js/Components/Admin/AppSidebar.tsx`.
- Shared-board UI: surface `short_url` (copy-to-share) in the admin project share surface and/or `resources/js/Pages/Public/SharedBoard.tsx` share control.
- i18n: `lang/en/shortlink.php` + `lang/ar/shortlink.php`; run `php artisan translations:export` to refresh `resources/js/translations.json`. Use the `__()` helper on frontend; zero hardcoded strings.

## Validation plan
- Feature tests (`php artisan test --filter=Shortlink`):
  - create link → redirect 302 to destination.
  - unknown code → 404.
  - expired (`expires_at` past) → 410 Gone.
  - `is_active=false` or soft-deleted → 410/404.
  - `clicks` increments exactly once per redirect (assert count).
  - signed shared-board target: create short link from real `signedRoute`, follow `/s/{code}`, assert lands on shared-board 200; tamper with nothing (signature honored via stored destination).
  - collision: force a duplicate code → generator retries and produces a unique code.
  - code entropy/length: stored code matches base62 / length policy.
  - non-admin hits `admin.shortlinks.*` → 403.
- `php artisan migrate` (fresh migration applies).
- `php artisan translations:export`.
- `npm run build` + TypeScript typecheck pass.

## Risks / failure modes
- Short code brute-force → 60-bit entropy mitigates.
- `APP_KEY` rotation invalidates stored signatures (signed destinations break) → v1 stores full destination URL; documented caveat. Future enhancement: re-sign-on-redirect for internal routes (not general URLs).
- Expired/inactive/soft-deleted → 410/404 with a clean page.
- Route collision → `/s` unique vs existing `/r`, `/c`.
- Concurrent redirect → atomic increment of `clicks` (use `->increment()`).

## Out of scope (future tasks)
- Replace Bitly in `QrCodeService`.
- Per-click logging, geo/IP, referrer, daily aggregates.
- Custom aliases.
- Client-facing creation in the dashboard.
- QR code generation per short link (`endroid/qr-code` is already a dependency — easy add later).

## Ordered task list
1. Scaffold module: `Modules/Shortlink/` dirs, `module.json`, `composer.json`, ServiceProviders; add `"Shortlink": true` to `modules_statuses.json`.
2. Migration `2026_07_02_000001_create_shortlink_links_table.php`; run `php artisan migrate`.
3. `ShortlinkLink` model (casts, fills, morphTo source, scopes).
4. `ShortlinkService` (create, resolve, recordClick, generateUniqueCode with collision retry).
5. Controllers: `ShortlinkRedirectController` (public 302 + increment), `ShortlinkController` (admin index/store/destroy/toggle) + `StoreShortlinkRequest`.
6. `ShortlinkLinkPolicy` (admin-only).
7. Module `routes/web.php` (public + admin groups).
8. Translations `lang/{en,ar}/shortlink.php`; `php artisan translations:export`.
9. Frontend: `Admin/Shortlinks/Index.tsx` (+ Show if desired) in `AdminSidebarLayout`; sidebar nav entry.
10. Shared-board hook in `app/Http/Controllers/Admin/ProjectController.php:281` + surface `short_url` in share UI.
11. Feature tests; `php artisan test`; `npm run build`; typecheck.

## Open items for implementation
- Confirm the exact Inertia render in `ProjectController` (and admin share page) that returns `shareUrl` so `short_url` is injected in the same response — implementation agent to grep `shared-board.show` / `shareUrl` to locate it precisely.
