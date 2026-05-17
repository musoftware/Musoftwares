# Troubleshooting & Debugging Guide

## 1. Common Backend Errors & Resolutions

### `500 Internal Server Error: Class "Modules\ERP\Models\Tenant" not found`
**Cause:** The autoloader has not registered newly generated module classes, or the module itself is disabled in `modules_statuses.json`.
**Resolution:**
```bash
# Dump composer autoloader mapping
composer dump-autoload

# Verify module status in modules_statuses.json
```
If disabled, change the boolean in `modules_statuses.json` to `true` or run `php artisan module:enable ERP`.

### `SQLSTATE[42S02]: Base table or view not found`
**Cause:** Migrations from active modules have not been executed on your local database schema.
**Resolution:**
```bash
php artisan migrate
```
If developing new module migrations, run `php artisan module:migrate ERP`.

### `419 Page Expired` (CSRF Token Mismatch)
**Cause:** The user's browser session cookie has expired, or file permissions on `storage/framework/sessions` are preventing session writing.
**Resolution:**
1. Clear your browser cookies and refresh the page.
2. Ensure proper folder permissions: `chmod -R 775 storage/ bootstrap/cache`.
3. Check `.env` for `SESSION_DOMAIN` matching your local URL exactly.

---

## 2. Common Frontend Errors & Resolutions

### `Inertia Modal / Page Flash: Component "ERP/Invoices/Index" not found`
**Cause:** Vite cannot locate the React page file specified in your controller's `Inertia::render()` call.
**Resolution:** Check file casing exactly. Linux/macOS filesystems are strictly case-sensitive. Ensure the file is at `resources/js/Pages/ERP/Invoices/Index.tsx` or `.jsx`.

### `Infinite Network Request Loop on Inertia Visit`
**Cause:** A React component `useEffect` hook has an active Inertia data object or shared prop listed in its dependency array while executing an Inertia `visit()` or `post()`.
```tsx
// ❌ Dangerous (Causes Infinite Re-render Loops)
useEffect(() => {
  router.get('/erp/invoices', { page: 2 }, { preserveState: true });
}, [invoices]); // invoices prop updates on each visit!
```
**Resolution:** Never depend on compound Inertia props. Depend strictly on primitive variables (e.g., page index integer).

### `WebSocket / Echo Connection Error`
**Cause:** The WebSocket server or broadcasting configuration is either down or misconfigured in `.env`.
**Resolution:** Verify your frontend broadcasting environment variables in `.env`:
```ini
VITE_BROADCAST_DRIVER="reverb"
VITE_REVERB_HOST="localhost"
VITE_REVERB_PORT=8080
VITE_REVERB_SCHEME="http"
```

---

## 3. Database & Query Performance Debugging

### Debugging Slow Eloquent Queries
If a page load feels sluggish, inspect query execution times by installing **Laravel Telescope** or enable query logging in your `AppServiceProvider.php`:

```php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

public function boot(): void
{
    if (app()->isLocal()) {
        DB::listen(function ($query) {
            if ($query->time > 100) { // Log queries taking longer than 100ms
                Log::warning("Slow Query Execution: {$query->sql} [Time: {$query->time}ms]");
            }
        });
    }
}
```

### N+1 Query Problem Elimination
When rendering invoice item lists or freelance proposals, ensure eager loading is applied in your controllers:

```php
// ❌ Bad (Triggers 100+ SQL Queries)
$invoices = Invoice::all();
foreach ($invoices as $invoice) {
    echo $invoice->client->name;
}

// ✅ Good (Executes exactly 2 SQL Queries)
$invoices = Invoice::with(['client', 'items'])->get();
```

---

## 4. Cache Purging & System Resets

When config changes, route updates, or view modifications do not reflect in the browser, run the master cache clearing commands:

```bash
# Clear compiled views, cache, config, and route caches
php artisan optimize:clear

# Recompile frontend assets from scratch
npm run build
```
