<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Currency;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Enums\EscrowStatus;
use Modules\Marketplace\Models\MarketplaceEscrow;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class MarketplaceOrderTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'seller']);
        Role::firstOrCreate(['name' => 'client']);

        Gate::policy(ServiceOrder::class, \Modules\Marketplace\Policies\ServiceOrderPolicy::class);
        Gate::policy(Service::class, \Modules\Marketplace\Policies\ServicePolicy::class);

        $this->currency = Currency::firstOrCreate(['currency' => 'USD', 'symbol' => '$'], ['exchange_rate' => 1.0]);
    }

    public function test_buyer_can_place_order_with_sufficient_balance()
    {
        $buyer = User::factory()->create([
            'user_balance' => 1000,
            'email_verified_at' => now(),
            'onboarding_completed' => true
        ]);
        $buyer->assignRole('client');

        $seller = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true
        ]);
        $seller->assignRole('seller');
        
        $category = \Modules\Marketplace\Models\ServiceCategory::create(['name' => 'Test', 'slug' => 'test']);
        $service = Service::create(['seller_id' => $seller->id, 'title' => 'Test Service', 'category_id' => $category->id, 'description' => 'test', 'status' => 'active']);
        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'test',
            'price' => 100,
            'currency_id' => $this->currency->id,
            'delivery_days' => 1
        ]);

        $response = $this->actingAs($buyer)->post(route('marketplace.orders.store'), [
            'package_id' => $package->id
        ]);

        $response->assertSessionHas('success');
        
        $this->assertDatabaseHas('marketplace_orders', [
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'status' => ServiceOrderStatus::PENDING
        ]);

        $order = ServiceOrder::first();

        $this->assertDatabaseHas('marketplace_escrows', [
            'order_id' => $order->id,
            'status' => EscrowStatus::HELD
        ]);

        // Assert buyer central balance deducted
        $buyer->refresh();
        $this->assertEquals(900, $buyer->user_balance);

        // Assert NO ERP records are created (Complete Isolation)
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_buyer_cannot_place_order_with_insufficient_balance()
    {
        $buyer = User::factory()->create([
            'user_balance' => 50,
            'email_verified_at' => now(),
            'onboarding_completed' => true
        ]);
        $buyer->assignRole('client');

        $seller = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true
        ]);
        $seller->assignRole('seller');
        
        $category = \Modules\Marketplace\Models\ServiceCategory::create(['name' => 'Test', 'slug' => 'test']);
        $service = Service::create(['seller_id' => $seller->id, 'title' => 'Test Service', 'category_id' => $category->id, 'description' => 'test', 'status' => 'active']);
        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'test',
            'price' => 100,
            'currency_id' => $this->currency->id,
            'delivery_days' => 1
        ]);

        $response = $this->actingAs($buyer)->post(route('marketplace.orders.store'), [
            'package_id' => $package->id
        ]);

        $response->assertSessionHasErrors(['error']);
        
        $this->assertDatabaseMissing('marketplace_orders', [
            'buyer_id' => $buyer->id,
        ]);

        // Assert NO ERP records are created
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_marketplace_order_lifecycle_remains_completely_isolated_from_erp()
    {
        $buyer = User::factory()->create([
            'user_balance' => 500,
            'email_verified_at' => now(),
            'onboarding_completed' => true
        ]);
        $buyer->assignRole('client');

        $seller = User::factory()->create([
            'user_balance' => 0,
            'email_verified_at' => now(),
            'onboarding_completed' => true
        ]);
        $seller->assignRole('seller');
        
        $category = \Modules\Marketplace\Models\ServiceCategory::create(['name' => 'Isolation Test', 'slug' => 'iso-test']);
        $service = Service::create(['seller_id' => $seller->id, 'title' => 'Isolation Service', 'category_id' => $category->id, 'description' => 'test', 'status' => 'active']);
        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Pro',
            'description' => 'test',
            'price' => 200,
            'currency_id' => $this->currency->id,
            'delivery_days' => 2
        ]);

        // 1. Place order
        $this->actingAs($buyer)->post(route('marketplace.orders.store'), ['package_id' => $package->id]);
        $order = ServiceOrder::firstOrFail();

        // 2. Deliver order via DeliverableController
        $this->actingAs($seller)->post(route('marketplace.orders.deliver', $order->id), [
            'note' => 'Work finished'
        ]);

        $order->refresh();
        $this->assertEquals(ServiceOrderStatus::DELIVERED, $order->status);

        // 3. Complete order & release escrow as buyer
        $response = $this->actingAs($buyer)->post(route('marketplace.orders.complete', $order->id));
        $response->assertSessionHas('success');

        $order->refresh();
        $this->assertEquals(ServiceOrderStatus::COMPLETED, $order->status);

        // 4. Verify seller received central wallet credit
        $seller->refresh();
        $this->assertGreaterThan(0, $seller->user_balance);

        // 5. Verify zero ERP records exist
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('invoices', 0);
    }
}
