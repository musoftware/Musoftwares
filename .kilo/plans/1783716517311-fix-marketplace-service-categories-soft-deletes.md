# Fix: Missing `deleted_at` column on `marketplace_service_categories`

## Root cause

The Eloquent model `Modules\Marketplace/Models/ServiceCategory.php` declares `use SoftDeletes;`, which auto-applies a global scope `WHERE deleted_at IS NULL` to every query. However, the migration that creates the underlying table (`Modules/Marketplace/Database/Migrations/2026_05_15_000003_create_marketplace_tables.php`) does **not** include `->softDeletes()`, and no follow-up migration ever adds the column.

Result: every read of `ServiceCategory` (controllers, dashboard, routes, admin, seeder, tests) blows up with:

```
SQLSTATE[42S22]: Unknown column 'marketplace_service_categories.deleted_at' in 'WHERE'
```

The same pattern was already fixed for `marketplace_packages` via `2026_05_30_000000_add_soft_deletes_to_marketplace_packages.php` — we will mirror that fix.

## Goal

Add the missing `deleted_at` column to `marketplace_service_categories` so the `ServiceCategory` model's `SoftDeletes` global scope works. Do not change any other Marketplace table in this fix.

## Affected boundary

- DB: `marketplace_service_categories.deleted_at` (nullable timestamp, indexed by Laravel's `softDeletes()`)
- Module: `Modules/Marketplace`
- Model: `Modules/Marketplace/Models/ServiceCategory.php` (no change — trait usage is intentional)

## Out of scope

- Removing the `SoftDeletes` trait from the model (intentional — destroy() in `ServiceCategoryController` already calls soft delete).
- Auditing the other 16 Marketplace models that also `use SoftDeletes` — that's a separate sweep.
- Production data backfill. The column is nullable, so existing rows become "not deleted" automatically.

## Plan

1. Create new migration file mirroring the existing `marketplace_packages` pattern:
   - Path: `Modules/Marketplace/Database/Migrations/2026_07_10_120000_add_soft_deletes_to_marketplace_service_categories.php`
   - Content (use the same guard pattern as `2026_05_30_000000_add_soft_deletes_to_marketplace_packages.php`):
     ```php
     <?php

     use Illuminate\Database\Migrations\Migration;
     use Illuminate\Database\Schema\Blueprint;
     use Illuminate\Support\Facades\Schema;

     return new class extends Migration
     {
         public function up()
         {
             if (Schema::hasColumn('marketplace_service_categories', 'deleted_at')) {
                 return;
             }

             Schema::table('marketplace_service_categories', function (Blueprint $table) {
                 $table->softDeletes();
             });
         }

         public function down()
         {
             Schema::table('marketplace_service_categories', function (Blueprint $table) {
                 $table->dropSoftDeletes();
             });
         }
     };
     ```
   - Timestamp `2026_07_10_120000` is chosen to be later than every existing migration in that folder and sortable.

2. Run `php artisan migrate` (dev environment, DB `u962989541_db`).

3. Verify by hitting the previously failing entry points:
   - `Modules\Marketplace\Http\Controllers\ServiceCategoryController@index`
   - `Modules\Marketplace\Http\Controllers\DashboardController`
   - `Modules\Marketplace\Http\Controllers\Admin\MarketplaceServiceController`
   - The `database/seeders/ServiceCategorySeeder.php` path (`firstOrCreate`).

## Validation

- `php artisan migrate` exits 0; new row appears in `migrations` table with the new filename.
- `php artisan tinker` → `Modules\Marketplace\Models\ServiceCategory::count()` returns a number (not an exception).
- Re-run the seeder: `php artisan db:seed --class="database\\seeders\\ServiceCategorySeeder"` (or whatever class loader path it uses) without error.
- Smoke test the admin route that originally triggered the error.

## Risks

- The migration is additive (nullable column) — no data loss.
- If the column is already present in some environments, the `Schema::hasColumn` guard makes the migration a no-op, so it is safe to re-run.
- No application code change is required.

## Follow-up (not part of this fix)

The Marketplace module has 16 other models using `SoftDeletes`. The same class of bug likely exists on at least one of them. Recommend a separate sweep that:
1. Lists every Marketplace model with `use SoftDeletes`,
2. Cross-checks each table for a `deleted_at` column,
3. Generates an `add_soft_deletes_to_<table>` migration per missing case.