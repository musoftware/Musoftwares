# Modular Architecture Guide

## How `nwidart/laravel-modules` Works

The ERP System is structured using the **Modular Monolith** architectural pattern powered by the `nwidart/laravel-modules` package (`^11.1 || ^13.0`). Unlike standard monolithic Laravel applications where all models live in `app/Models/` and all controllers in `app/Http/Controllers/`, a modular monolith groups domain logic by feature inside the `/Modules` directory.

Each module acts as an almost fully independent Laravel package containing its own models, controllers, form requests, database migrations, routes, views, and event listeners.

```text
ERP System Root
├── app/                  ← Core Framework bootstrap and base HTTP middleware
├── database/migrations/  ← Master framework tables (sessions, cache, jobs)
├── Modules/
│   ├── Core/             ← Shared authentication, currencies, chat, double-entry accounting ledgers
│   ├── ERP/              ← Invoicing, client wallets, tenant isolation, recurring entries, withdrawals
│   ├── Freelance/        ← Skills matching, job bidding, proposals, points transactions
│   ├── Marketplace/      ← Productized service catalog, packages, orders, escrow checkouts
│   └── Shared/           ← Shared helper utilities (Inactive)
```

## Active Module Configuration (`modules_statuses.json`)

The active state of each module is tracked in `modules_statuses.json`:

```json
{
    "Core": true,
    "ERP": true,
    "Freelance": true,
    "Marketplace": true,
    "Shared": false
}
```

## Creating a New Module

To generate a completely new feature module using the CLI:

```bash
php artisan module:make Support
```

## Internal Module Directory Structure

Every module adheres to a standard internal directory layout mirroring standard Laravel application architecture:

```text
Modules/ERP/
├── Config/               ← Module-specific configuration files
├── Console/              ← Custom Artisan CLI commands and cron jobs
├── Database/
│   ├── factories/        ← Model testing factories
│   ├── migrations/       ← Module schema migration tables
│   └── Seeders/          ← Dummy data seeders
├── Events/               ← Domain events
├── Listeners/            ← Event listeners
├── Http/
│   ├── Controllers/      ← HTTP & API Controllers
│   ├── Middleware/       ← Module-specific middleware
│   └── Requests/         ← Form validation Request classes
├── Models/               ← Eloquent ORM Models
├── Policies/             ← Authorization gate policies
├── Providers/            ← Module ServiceProviders & EventProviders
├── Resources/
│   ├── views/            ← PDF Blade templates or email layouts
│   └── lang/             ← Localization strings
├── Routes/
│   ├── web.php           ← Inertia route endpoints
│   └── api.php           ← REST API endpoints
└── Services/             ← Encapsulated business logic service classes
```

## Inter-Module Communication Architecture

Although modules are physically separated in the directory tree, they run within the exact same PHP runtime and share a single MySQL database.

### 1. Direct Model Invocation (Allowed when foreign keys exist)
If the Marketplace module needs to look up a user's currency from the Core module, it can directly use the Core model:

```php
use Modules\Core\Models\Currency;

$currency = Currency::where('code', $buyer->preferred_currency)->first();
```

### 2. Service Container Dependency Injection (Recommended for business logic)
When executing complex state mutations (like debiting a client wallet or dispatching notifications), modules must inject domain services rather than directly executing raw SQL updates on another module's tables.

```php
namespace Modules\Marketplace\Http\Controllers;

use Modules\ERP\Services\WalletService;
use Modules\Marketplace\Models\Order;

class OrderEscrowController
{
    public function __construct(protected WalletService $walletService) {}

    public function secureEscrow($orderId)
    {
        $order = Order::findOrFail($orderId);
        
        // Securely debit buyer's client wallet via ERP module service
        $this->walletService->debit(
            $order->buyer->client_wallet_id,
            $order->total_amount,
            $order->currency_code,
            "Escrow hold for marketplace order #{$order->id}",
            $order
        );
    }
}
```

## Module Artisan Helpers

```bash
# Create a controller inside a specific module
php artisan module:make-controller InvoiceController ERP

# Create an Eloquent model with a migration inside a specific module
php artisan module:make-model TenantClient -m ERP

# Create a FormRequest validation class
php artisan module:make-request StoreInvoiceRequest ERP

# Disable a module temporarily for testing
php artisan module:disable Freelance

# Enable a module
php artisan module:enable Freelance
```
