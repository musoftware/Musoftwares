<?php

namespace Modules\ERP\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Product;
use Modules\ERP\Models\Contract;
use Modules\ERP\Services\InvoiceService;
use Modules\ERP\Services\InventoryService;
use App\Models\User;
use App\Models\Currency;
use Tests\TestCase;

class DomainEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure currencies exist
        Currency::firstOrCreate(['currency' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$']);
        Currency::firstOrCreate(['currency' => 'EUR'], ['name' => 'Euro', 'symbol' => '€']);
    }

    public function test_invoice_created_event_is_dispatched()
    {
        Event::fake([
            \Modules\ERP\Events\InvoiceCreated::class,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'user_id' => $user->id,
            'base_currency_id' => Currency::where('currency', 'USD')->first()->id,
        ]);

        app(\Modules\ERP\Infrastructure\Context\TenantContext::class)->setTenantId($tenant->id);
        session(['tenant_id' => $tenant->id]);

        \Modules\ERP\Models\TeamMember::create([
            'tenant_id' => $tenant->id,
            'name' => 'John Doe',
            'email' => 'employee@example.com',
            'password' => bcrypt('password'),
            'status' => 'active'
        ]);

        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Client',
            'currency_id' => Currency::where('currency', 'USD')->first()->id,
        ]);

        $service = app(InvoiceService::class);
        $invoice = $service->createInvoice([
            'client_id' => $client->id,
            'invoice_number' => 'INV-TEST-001',
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'notes' => 'Test',
            'items' => [
                [
                    'type' => 'simple',
                    'title' => 'Item 1',
                    'unit_price' => 100,
                    'quantity' => 1,
                ]
            ]
        ], $tenant);

        Event::assertDispatched(\Modules\ERP\Events\InvoiceCreated::class, function ($event) use ($invoice) {
            return $event->invoice->id === $invoice->id;
        });
    }

    public function test_inventory_adjusted_event_is_dispatched()
    {
        Event::fake([
            \Modules\ERP\Events\InventoryAdjusted::class,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'user_id' => $user->id,
            'base_currency_id' => Currency::where('currency', 'USD')->first()->id,
        ]);

        app(\Modules\ERP\Infrastructure\Context\TenantContext::class)->setTenantId($tenant->id);
        session(['tenant_id' => $tenant->id]);

        $service = app(InventoryService::class);
        $product = $service->createProduct([
            'name' => 'Test Product',
            'sku' => 'SKU-TEST',
            'unit_price' => 50,
            'stock_quantity' => 10,
            'currency_id' => Currency::where('currency', 'USD')->first()->id,
        ], $tenant->id);

        $service->adjustStock($product, 5, 'Restock', $tenant->id);

        Event::assertDispatched(\Modules\ERP\Events\InventoryAdjusted::class, function ($event) use ($product) {
            return $event->product->id === $product->id && $event->adjustmentAmount == 5;
        });
    }
}
