<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Enums\EscrowStatus;
use Modules\Marketplace\Models\MarketplaceEscrow;
use Illuminate\Support\Facades\DB;

class MarketplaceOrderTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware();
    }

    public function test_buyer_can_place_order_with_sufficient_balance()
    {
        $buyer = User::factory()->create(['user_balance' => 1000]);

        $seller = User::factory()->create();
        
        $category = \Modules\Marketplace\Models\ServiceCategory::create(['name' => 'Test', 'slug' => 'test']);
        $service = Service::create(['seller_id' => $seller->id, 'title' => 'Test Service', 'category_id' => $category->id, 'description' => 'test', 'status' => 'active']);
        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'test',
            'price' => 100,
            'currency_id' => 1,
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

        // Assert buyer balance deducted
        $buyer->refresh();
        $this->assertEquals(900, $buyer->user_balance);
    }

    public function test_buyer_cannot_place_order_with_insufficient_balance()
    {
        $buyer = User::factory()->create(['user_balance' => 50]);

        $seller = User::factory()->create();
        
        $category = \Modules\Marketplace\Models\ServiceCategory::create(['name' => 'Test', 'slug' => 'test']);
        $service = Service::create(['seller_id' => $seller->id, 'title' => 'Test Service', 'category_id' => $category->id, 'description' => 'test', 'status' => 'active']);
        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'test',
            'price' => 100,
            'currency_id' => 1,
            'delivery_days' => 1
        ]);

        $response = $this->actingAs($buyer)->post(route('marketplace.orders.store'), [
            'package_id' => $package->id
        ]);

        $response->assertSessionHasErrors(['error']);
        
        $this->assertDatabaseMissing('marketplace_orders', [
            'buyer_id' => $buyer->id,
        ]);
    }
}
