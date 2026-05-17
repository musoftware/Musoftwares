# Backend Developer Guide

## 1. Project Setup & Prerequisites

The backend of the ERP System is built upon **Laravel 12 (PHP 8.2+)** structured using a **Modular Monolith** pattern. All business logic is encapsulated within distinct feature modules (`Core`, `ERP`, `Freelance`, `Marketplace`, and `Shared`).

### Required System Dependencies
- **PHP 8.2 or 8.3** with standard extensions: `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `curl`, `gd`, `zip`, `xml`, `pcntl`.
- **Composer 2.x** for PHP package management.
- **MySQL 8.0+** (Required for column-based tenancy and JSON columns).
- **Node.js 20+ and npm** (Required for compiling React/TypeScript UI assets via Vite).
- **Redis 7.0+** (Highly recommended for production session storage, cache tagging, and background queues).

### Local Environment Initialization
To clone and initialize the local development environment:

```bash
# 1. Install PHP dependencies
composer install

# 2. Copy environment configuration
cp .env.example .env

# 3. Generate Laravel application encryption key
php artisan key:generate

# 4. Install Node dependencies
npm install
```

### Database Configuration (`.env`)
Configure your local database credentials inside `.env`:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=musoftware_erp
DB_USERNAME=root
DB_PASSWORD=secret
```

---

## 2. Running Locally & Development Server

During local development, you can run the provided concurrent script or start servers individually.

```bash
# Terminal 1: Run standard Laravel development server
php artisan serve

# Terminal 2: Run Vite frontend HMR server
npm run dev

# Or use the combined dev script from composer.json
composer dev
```

---

## 3. Database Migrations & Seeding Architecture

Because the platform is organized into modules via `nwidart/laravel-modules`, database migrations are located both in the root `/database/migrations` directory (for base framework tables like cache and jobs) and within each individual module's `/Database/migrations` directory.

### Running Migrations Across All Modules

When running standard artisan migration commands, Laravel Modules automatically registers and runs migrations from all active modules in alphabetical order (`Core` -> `ERP` -> `Freelance` -> `Marketplace`).

```bash
# Run all pending migrations
php artisan migrate

# Roll back the last migration batch
php artisan migrate:rollback

# Reset the entire database and re-run all migrations from scratch
php artisan migrate:fresh --seed
```

### Seeding Dummy & Test Data

```bash
# Run the master database seeder
php artisan db:seed

# Run a specific module seeder
php artisan module:seed ERP
```

---

## 4. Service Providers & Bootstrapping

Service Providers are the central bootstrapping mechanism of the application. Each module contains its own dedicated Service Provider (e.g., `Modules/ERP/Providers/ERPServiceProvider.php`).

### How Module Providers Boot
When the application starts, Laravel loads the `nwidart/laravel-modules` package, which inspects `modules_statuses.json`. For every active module, it loads the module's primary service provider.

```json
{
    "Core": true,
    "ERP": true,
    "Freelance": true,
    "Marketplace": true,
    "Shared": false
}
```

---

## 5. Middleware Architecture & Role Guards

The platform uses custom middleware to enforce authentication, multi-tenancy column scoping, and role authorization.

### Key Custom Middleware

#### 1. `HandleInertiaRequests`
Located in `app/Http/Middleware/HandleInertiaRequests.php`. Intercepts all incoming Inertia requests and attaches shared global data (authenticated user, role, impersonation status, flash toasts) to the response props.

#### 2. Role Authorization
Guarded via `spatie/laravel-permission` middleware or custom role inspection.

```php
// In route definition:
Route::middleware(['auth', 'permission:manage erp'])->group(function () {
    Route::get('/erp/dashboard', [ErpController::class, 'index']);
});
```

#### 3. `TenantScopeMiddleware`
Ensures that any logged-in tenant client has the column-based global tenant scope applied to all Eloquent models during the request lifecycle.

#### 4. `ImpersonateMiddleware`
Checks the session for `impersonate_id`. If present, it temporarily logs the super-admin into the target client account for troubleshooting.

```php
namespace Modules\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonateMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (session()->has('impersonate_id')) {
            Auth::onceUsingId(session()->get('impersonate_id'));
        }
        return $next($request);
    }
}
```

---

## 6. Route Structure & Handling

Each module contains its own routing definitions inside `Modules/{ModuleName}/Routes/` or `routes.php`.

```text
Modules/ERP/Routes/
├── web.php   ← Inertia page navigation routes (Protected by 'auth' & 'web' middleware)
└── api.php   ← REST/JSON API endpoints (Protected by 'api' & 'throttle' middleware)
```

### Standard Web Route Definition

```php
use Modules\ERP\Http\Controllers\InvoiceController;

Route::prefix('erp')->middleware(['web', 'auth'])->group(function () {
    Route::get('/invoices', [InvoiceController::class, 'index'])->name('erp.invoices.index');
    Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('erp.invoices.create');
    Route::post('/invoices', [InvoiceController::class, 'store'])->name('erp.invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('erp.invoices.show');
});
```
