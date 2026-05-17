# Testing Strategy & Engineering Guide

## Testing Philosophy

To guarantee high platform availability and absolute financial precision, the ERP System enforces an **Automated Testing Strategy**. All code changes must pass a dual testing pipeline: PHPUnit for backend domain and API testing, and Vitest for frontend React/TypeScript component testing.

## Backend Testing Environment (`phpunit.xml`)

When running test suites, Laravel automatically switches database connections to an in-memory SQLite database (`:memory:`) or a dedicated test MySQL schema as configured inside `phpunit.xml`.

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
            <directory>Modules/*/Tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
            <directory>Modules/*/Tests/Feature</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
    </php>
</phpunit>
```

## Running Backend Test Suites

```bash
# Run all unit and feature tests across core and all modules
php artisan test

# Filter tests by module or specific method
php artisan test --filter=WalletServiceTest

# Run tests in parallel to maximize multi-core hardware execution speed
php artisan test --parallel
```

## Writing Unit Tests (Domain Services)

Unit tests focus on testing pure business logic in isolation without touching real databases or dispatching network requests.

```php
namespace Modules\ERP\Tests\Unit;

use Tests\TestCase;
use Modules\Core\Services\ExchangeRateService;
use Modules\ERP\Services\WalletService;
use Modules\ERP\Models\ClientWallet;
use Modules\ERP\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WalletServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_crediting_wallet_increases_balance_and_writes_immutable_transaction()
    {
        $tenant = Tenant::factory()->create();
        $wallet = ClientWallet::factory()->create(['tenant_id' => $tenant->id, 'balance' => 100.00]);

        $service = new WalletService(new ExchangeRateService());
        
        $transaction = $service->credit($wallet->id, 50.00, 'USD', 'Test credit');

        $this->assertEquals(150.00, $wallet->fresh()->balance);
        $this->assertEquals(100.00, $transaction->balance_before);
        $this->assertEquals(150.00, $transaction->balance_after);
        $this->assertEquals('manual_credit', $transaction->type);
    }
}
```

## Writing Feature Tests (HTTP & Inertia Endpoints)

Feature tests execute the entire HTTP lifecycle, validating routing, middleware role guards, database scoping, and correct page rendering or API JSON structures.

```php
namespace Modules\ERP\Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

class InvoiceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_can_view_invoices_index_page_with_scoped_data()
    {
        $tenantUser = User::factory()->create();
        $tenant = Tenant::factory()->create(['user_id' => $tenantUser->id]);
        
        // Create invoice for this tenant
        Invoice::factory()->create(['tenant_id' => $tenant->id, 'invoice_number' => 'INV-001']);
        
        // Create invoice for an unrelated tenant
        Invoice::factory()->create(['tenant_id' => 999, 'invoice_number' => 'INV-999']);

        $response = $this->actingAs($tenantUser)->get('/erp/invoices');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('ERP/Invoices/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'INV-001')
        );
    }
}
```

## Frontend Testing Environment (`vitest.config.ts`)

Frontend unit testing is executed via **Vitest** paired with `@testing-library/react`.

```typescript
import { defineConfig } from 'vitest/config';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./vitest.setup.ts'],
  },
  resolve: {
    alias: {
      '@': path.resolve(__DIR__, './resources/js'),
    },
  },
});
```

## Running Frontend Component Tests

```bash
# Run Vitest test suite in watch mode
npx vitest

# Run once with code coverage report
npx vitest run --coverage
```

## Writing React Component Tests

```tsx
import React from 'react';
import { render, screen } from '@testing-library/react';
import StatCard from '@/Components/StatCard';
import { Wallet } from 'lucide-react';

describe('Shared Domain Components', () => {
  test('StatCard renders title and value correctly', () => {
    render(
      <StatCard 
        title="Total Escrow" 
        value="$5,000.00" 
        icon={<Wallet />}
      />
    );

    expect(screen.getByText('Total Escrow')).toBeInTheDocument();
    expect(screen.getByText('$5,000.00')).toBeInTheDocument();
  });
});
```
