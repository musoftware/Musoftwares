<?php

namespace Modules\ERP\Tests\Feature;

use App\Models\User;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        // Removed migrate:fresh
        
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);
    }

    public function test_user_cannot_access_pos_without_addon(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name' => 'Acme Corp',
            'status' => 'active',
            'base_currency_id' => 1
        ]);
        
        $response = $this->actingAs($user)
                         ->withSession(['tenant_id' => $tenant->id])
                         ->get(route('erp.pos.index'));
        
        $response->assertStatus(403);
    }

    public function test_user_can_access_pos_with_addon(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name' => 'Acme Corp',
            'status' => 'active',
            'base_currency_id' => 1
        ]);
        
        $user->subscriptions()->create([
            'object' => 'erp-pos',
            'status' => 'active',
            'expires_at' => now()->addYear(),
        ]);
        
        $response = $this->actingAs($user)
                         ->withSession(['tenant_id' => $tenant->id])
                         ->get(route('erp.pos.index'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('ERP/Pos/Index'));
    }

    public function test_pos_checkout_creates_invoice_and_transaction(): void
    {
        $user = User::factory()->create();
        $tenant = Tenant::create([
            'user_id' => $user->id,
            'name' => 'Acme Corp',
            'status' => 'active',
            'base_currency_id' => 1
        ]);
        
        $user->subscriptions()->create([
            'object' => 'erp-pos',
            'status' => 'active',
            'expires_at' => now()->addYear(),
        ]);

        $product = Product::create([
            'tenant_id' => $tenant->id,
            'name' => 'Test Product',
            'price' => 100,
            'stock_quantity' => 10,
            'currency_id' => 1,
        ]);

        $response = $this->actingAs($user)
                         ->withSession(['tenant_id' => $tenant->id])
                         ->post(route('erp.pos.checkout'), [
                             'client_id' => null,
                             'is_paid' => true,
                             'items' => [
                                 [
                                     'product_id' => $product->id,
                                     'quantity' => 2,
                                     'unit_price' => 100
                                 ]
                             ],
                             'payment_method' => 'cash'
                         ]);
        
        $response->assertStatus(200);
        $response->assertJson(['message' => __('erp.checkout_successful')]);

        $this->assertDatabaseHas('erp_invoices', [
            'tenant_id' => $tenant->id,
            'status' => 'paid',
            'amount' => 200
        ]);

        $this->assertDatabaseHas('erp_client_transactions', [
            'tenant_id' => $tenant->id,
            'type' => 'received',
            'amount' => 200,
            'reference_type' => 'invoice_payment'
        ]);

        $this->assertDatabaseHas('erp_products', [
            'id' => $product->id,
            'stock_quantity' => 8
        ]);
    }
}
