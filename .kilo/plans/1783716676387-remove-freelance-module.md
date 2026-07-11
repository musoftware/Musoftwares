# Plan: Complete Removal of the Freelance Module

## Goal
Delete every piece of code, configuration, UI, route, migration, seeder, language key, and asset related to the Freelance / Freelancer system so the Musoftwares platform is fully separated from it. Fix the immediate runtime failure (`Table 'u962989541_db.freelance_skills' doesn't exist`) caused by the Admin Users page querying non‑existent tables.

## Root cause of the immediate error
`app/Http/Controllers/Admin/UsersController.php:411` runs `User::query()->whereHas('freelanceSkills')` which loads `\Modules\Freelance\Models\Skill` against `freelance_skills` and `freelance_user_skills`. The Freelance module's own migrations (which create those tables) were never copied into `database/migrations/`, so the tables are missing. The right fix is to remove the feature entirely per the user's request.

## Scope
Full removal (confirmed by user).

### 1. Files / directories to delete
- `Modules/Freelance/` (entire module, including `Models/Skill.php`)
- `app/Models/Freelance/` (legacy `Client`, `Currency`, `Currencies_exchange` models referenced by helpers — see §3)
- `app/Http/Controllers/Freelance/` and `app/Http/Controllers/Admin/Freelance*Controller.php` if present
- `app/Http/Middleware/EnforceFreelanceDomain.php`
- `resources/js/Pages/Freelance/**` (Landing, Dashboard, AboutUs, HowItWorks, Layout, PublicLayout, Contracts/*, Freelancers/*, Jobs/*, Profile/*, Proposals/*, Settings/*)
- `resources/js/Components/Freelance/**` (FreelanceModeContext, FreelanceModeToggle, PublicFooter, PublicNavbar, ui/*)
- `lang/en/freelance.php`, `lang/ar/freelance.php`
- `public/manifest-freelancer.json`, `public/illustrations/freelance-illustration.svg`, `public/images/freelance-meta.png`
- `tests/Feature/Freelance/**` (JobLifecycleTest, ProposalLifecycleTest, ContractLifecycleTest, ProfileAndSkillTest, JobPokeTest, DisputeAndAdminTest, AdminExtendedTest, FreelanceTestCase)
- `tests/Feature/FreelanceWorkflowTest.php`
- `tests/Feature/Admin/FreelanceSkillControllerTest.php`, `FreelanceProposalControllerTest.php`, `FreelanceJobControllerTest.php`, `FreelanceContractControllerTest.php`
- `tests/E2E/freelance.spec.ts`
- `.agents/rules/freelance-module-points-skill-matching.md`
- `.agents/skills/marketplace_billing/` — **keep**, but audit for any Freelance‑specific rules
- `.control/history/EX2_Develop_Freelance_Job_Board_&_Proposals.md` (history; may keep, out of scope)

### 2. Migrations to delete (no destructive DB drop yet — see §6)
Remove these migration files so they are not run on fresh installs:
- `database/migrations/2026_05_04_214503_seed_freelancer_role.php`
- `database/migrations/2026_05_27_093943_drop_freelancer_legacy_tables.php`
- `database/migrations/2026_05_30_000000_add_status_and_creator_to_freelance_skills_table.php`
- `database/migrations/2026_06_04_222636_create_freelance_profiles_table.php`
- `database/migrations/2026_06_05_001839_add_tracking_counters_to_freelance_jobs_table.php`
- `database/migrations/2026_06_05_005303_add_last_poked_at_to_freelance_jobs_table.php`
- `database/migrations/2026_06_10_163445_create_freelance_reviews_table.php`

The existing `database/migrations/2026_06_24_134855_remove_old_modules_tables.php` already prefixes `freelance_` — verify it covers all `freelance_*` tables on rollback, and add `freelance_user_skills`, `freelance_profiles`, `freelance_skills`, `freelance_jobs`, `freelance_proposals`, `freelance_contracts`, `freelance_reviews`, `freelance_points`, `freelance_pokes` to its down() if missing.

### 3. Code edits to remove Freelance references
**Backend (`app/`)**
- `app/Models/User.php`
  - Remove `'can_add_freelance_skills'` from `$fillable` (line 45) and `$casts` (line 84).
  - Remove `freelanceSkills()` belongsToMany (lines 567–571).
- `app/Http/Controllers/Admin/UsersController.php`
  - Drop the Freelancer tab and the `whereHas('freelanceSkills')` query (lines 404–449). Remove the 'freelancers' variable, view reference, and any related nav/permission gate.
- `app/Http/Controllers/Admin/BusinessController.php` (currently active)
  - Verify no Freelance conditionals remain; strip any.
- `app/Http/Controllers/SearchController.php:71-72`
  - Remove the `class_exists(\Modules\Freelance\Models\Job::class)` branch.
- `app/Http/Controllers/SsoController.php:48-49`
  - Remove the `freelancesys` branch.
- `app/Http/Middleware/HandleInertiaRequests.php:57,67`
  - Remove the `'freelance' => ...` keys from the shared Inertia props.
- `app/Http/Middleware/SubscriptionMiddleware.php:34-35`
  - Remove `'freelance'` from the bypass array.
- `app/Services/SubscriptionService.php:60,157,170`
  - Remove `'freelance'` slugs/branches.
- `app/Helpers/FileHelper.php`, `CartHelper.php`, `TaxHelper.php`, `TaskHelper.php`
  - Replace `use App\Models\Freelance\…;` imports with the canonical `App\Models\Currency` etc., or remove if unused.
- `app/Helpers/TimerHelper.php:218-222`
  - Keep `employee` branch; remove `freelancer` role check.
- `app/Providers/EventServiceProvider.php:66`
  - Remove the Freelance event registration line/comment.
- `bootstrap/app.php:6,50,59`
  - Remove `EnforceFreelanceDomain` import, middleware alias, and the `'freelance/point-purchases/webhook'` CSRF exception.

**Routes**
- `routes/web.php` lines 871–874, 973 — remove all `/freelance/*` route definitions (PointPurchaseController, etc.).
- `routes/api.php` lines 137–153 — remove the "Mobile Freelance API" block and the `require base_path('Modules/Freelance/routes/api.php')`.

**Config**
- `config/services.php:102` — remove `'freelancesys'` entry.

**Database seeders**
- `database/seeders/RolesAndPermissionsSeeder.php:40` — remove `'freelancer'` role.
- `database/seeders/ModulePlanSeeder.php:122-125` — remove the `Freelancer Premium` plan.
- Confirm migration `2026_05_04_214503_seed_freelancer_role.php` is removed (§2).

**Frontend**
- `resources/js/app.tsx` (or equivalent) — remove any `<FreelanceModeProvider>` wrapper, `FreelanceModeToggle` import, and Freelance routes from any router (`createInertiaApp` page resolver, react-router, etc.). Grep for `Pages/Freelance` and `Components/Freelance`.
- Any nav/menu component with a "Freelance" link — remove.

**Translations**
- `lang/en/general.php`, `lang/ar/general.php`, `lang/en/admin.php`, `lang/ar/admin.php`
  - Remove every key with `freelance`/`freelancer` substring (lines listed in grep results) **only if not referenced anywhere else**. Verify with grep before deletion; otherwise leave the keys (they will simply be unused). Prefer targeted removal over blanket cleanup to avoid breaking unrelated strings.

### 4. Permissions / role cleanup
- After removing the `freelancer` role from `RolesAndPermissionsSeeder`, write a one‑off cleanup migration that:
  - Deletes `role_user` rows where `role_id` matches the removed `freelancer` role.
  - Deletes the `roles` row for `freelancer`.
  - (Out of scope for first PR: keep user migration simple — handle in production manually if data exists.)

### 5. Dependencies
- Check `package.json` for any freelancer‑only npm deps (none expected; verify with grep).
- Check `composer.json` for `froiden/laravel-installer`‑style or freelance‑specific requires (none expected; verify).

### 6. Database cleanup (production)
Add a destructive migration `2026_07_xx_drop_freelance_tables.php`:
- `Schema::dropIfExists('freelance_user_skills')`
- `Schema::dropIfExists('freelance_skills')`
- `Schema::dropIfExists('freelance_profiles')`
- `Schema::dropIfExists('freelance_jobs')`
- `Schema::dropIfExists('freelance_proposals')`
- `Schema::dropIfExists('freelance_contracts')`
- `Schema::dropIfExists('freelance_reviews')`
- `Schema::dropIfExists('freelance_points')`
- `Schema::dropIfExists('freelance_pokes')`
- `Schema::dropColumn('users', 'can_add_freelance_skills')` (guard with `hasColumn`)
- Optional: remove `freelancer` role rows (see §4).

Guard each operation with `hasTable`/`hasColumn` so the migration is idempotent.

### 7. Documentation / rules
- Delete `.agents/rules/freelance-module-points-skill-matching.md`.
- Audit `.agents/skills/*` for references to `Modules\Freelance` and `freelance_skills` and prune.

## Implementation order
1. Backend code edits (`User`, controllers, middleware, helpers, services, providers, routes, config) — keeps `php artisan route:list` clean first.
2. Migrations cleanup (§2 + new destructive migration §6).
3. Seeders (RolesAndPermissionsSeeder, ModulePlanSeeder).
4. Frontend (delete `Pages/Freelance`, `Components/Freelance`, strip router wrappers, strip nav links).
5. Translations (targeted removal of unused keys).
6. Tests (delete `tests/Feature/Freelance/**`, `FreelanceWorkflowTest.php`, `tests/Feature/Admin/Freelance*`, `tests/E2E/freelance.spec.ts`; update `tests/Feature/Admin/AdminCostsFilterTest.php` line 36 which references `EnforceFreelanceDomain`).
7. Static assets and `.agents/rules`.
8. Final sweep: `grep -ri "freelance" app/ database/ resources/ routes/ config/ tests/ bootstrap/ lang/ Modules/` — only intentional leftovers (history files, generic `freelancer` mention in unrelated copy) should remain.

## Validation
- `php artisan route:list | grep -i freelance` → empty.
- `php artisan migrate:fresh` succeeds with no `freelance_*` tables created.
- `php artisan test` passes (freelance tests deleted; other tests untouched).
- Visit `/admin/users` → no SQL error, no Freelancer tab.
- Visit `/` and admin sidebar → no Freelance link.
- `php artisan tinker` → `Module::count()` for any Freelance model throws class‑not‑found (expected).

## Risks / open items
- **Data loss**: Destructive migration drops `freelance_*` tables. Confirm with user that no production data must be retained. If retention is needed, export first.
- **`freelancer` role**: Used by `TimerHelper` for payroll‑like flow. Removing the role is required; if TimerHelper logic still needs "non‑client" semantics, switch to checking `!$client->hasRole('client')` or a dedicated `employee` flag.
- **`App\Models\Freelance\Client/Currency`**: Legacy models. After helper refactor, the `app/Models/Freelance` folder should be empty — delete it.
- **Translation key removal**: Conservative; only delete keys that grep confirms are unused.

## Out of scope
- Removing generic English/Arabic copy that mentions "freelancer" as a marketing term (e.g. "Online invoicing for freelancers") unless it is in a Freelance‑only file.
- Refactoring unrelated modules (Marketplace, Fbmb, etc.).
- Rebuilding `EnforceFreelanceDomain` for any other tenant.