<?php

namespace Modules\ERP\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Modules\ERP\Models\Tenant;
use App\Models\User;
use App\Models\Currency;
use Modules\ERP\Models\Product;
use App\Models\UserSubscription;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;
    protected $currency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->tenant = Tenant::create([
            'user_id' => $this->user->id,
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'status' => 'active',
        ]);
        
        $this->currency = Currency::create([
            'currency' => 'US Dollar',
            'symbol' => '$',
            'string_format' => '$%v',
        ]);

        // Mock addon subscription
        UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);
        UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp-inventory',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => now()->addDays(30),
            'auto_renew' => true,
        ]);

        session(['tenant_id' => $this->tenant->id]);
    }

    public function test_inventory_dashboard_loads()
    {
        $response = $this->actingAs($this->user)->get(route('erp.inventory.index'));
        $response->assertStatus(200);
    }

    public function test_can_create_product()
    {
        $response = $this->actingAs($this->user)->post(route('erp.inventory.products.store'), [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => 99.99,
            'currency_id' => $this->currency->id,
            'stock_quantity' => 50,
            'reorder_level' => 10,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('erp.inventory.index'));
        $this->assertDatabaseHas('erp_products', [
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'stock_quantity' => 50,
        ]);
        
        // Check stock log
        $product = Product::where('sku', 'TEST-001')->first();
        $this->assertDatabaseHas('erp_product_stock_logs', [
            'product_id' => $product->id,
            'change_amount' => 50,
            'new_quantity' => 50,
        ]);
    }

    public function test_can_adjust_stock()
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Stock Product',
            'price' => 10,
            'currency_id' => $this->currency->id,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)->post(route('erp.inventory.products.store_adjustment', $product->id), [
            'change_amount' => -3,
            'reason' => 'Sale',
        ]);

        $response->assertRedirect(route('erp.inventory.index'));
        
        $this->assertDatabaseHas('erp_products', [
            'id' => $product->id,
            'stock_quantity' => 7,
        ]);

        $this->assertDatabaseHas('erp_product_stock_logs', [
            'product_id' => $product->id,
            'change_amount' => -3,
            'new_quantity' => 7,
            'reason' => 'Sale',
        ]);
    }

    public function test_can_search_products()
    {
        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Searchable Product',
            'sku' => 'SEARCH-123',
            'price' => 10,
            'currency_id' => $this->currency->id,
            'stock_quantity' => 10,
        ]);

        $response = $this->actingAs($this->user)->get(route('erp.inventory.products.search', ['q' => 'SEARCH']));
        
        $response->assertStatus(200);
        $response->assertJsonFragment([
            'sku' => 'SEARCH-123'
        ]);
    }

    public function test_invoice_creation_deducts_stock()
    {
        $client = \Modules\ERP\Models\TenantClient::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Test Client',
            'email' => 'client@test.com',
            'currency_id' => $this->currency->id,
        ]);

        $product = Product::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Invoice Product',
            'price' => 10,
            'currency_id' => $this->currency->id,
            'stock_quantity' => 10,
        ]);

        $service = app(\Modules\ERP\Services\InvoiceService::class);
        $service->createInvoice([
            'client_id' => $client->id,
            'invoice_number' => 'INV-0001',
            'issued_at' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'notes' => 'Test Invoice',
            'items' => [
                [
                    'type' => 'product',
                    'title' => 'Invoice Product',
                    'unit_price' => 10,
                    'quantity' => 3,
                    'product_id' => $product->id,
                ]
            ],
            'costs' => []
        ], $this->tenant);

        $this->assertDatabaseHas('erp_products', [
            'id' => $product->id,
            'stock_quantity' => 7,
        ]);
    }
}
