<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Core\Models\Wallet;
use Tests\TestCase;

class MarketplaceEscrowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Since Marketplace packages require a service category, we might need a dummy category if model enforces it.
        // I will just create required models directly or use factories if they exist.
    }

    public function test_checkout_locks_funds_in_escrow_without_paying_seller_immediately()
    {
        $seller = User::factory()->create(['onboarding_completed' => true]);
        $buyer = User::factory()->create(['onboarding_completed' => true]);

        // Give buyer some money
        $buyerWallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $buyer->id,
            'context' => 'user',
            'balance' => 1000,
            'locked_balance' => 0,
            'currency' => 'USD'
        ]);

        $sellerWallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $seller->id,
            'context' => 'user',
            'balance' => 0,
            'locked_balance' => 0,
            'currency' => 'USD'
        ]);

        // Create category, Service and Package
        $category = \Modules\Marketplace\Models\ServiceCategory::create([
            'name' => 'Web Dev',
            'slug' => 'web-dev'
        ]);

        $service = Service::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Test Service',
            'slug' => 'test-service',
            'description' => 'Test',
            'status' => 'published',
            'currency_code' => 'USD'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'Basic package',
            'price' => 100,
            'currency_code' => 'USD',
            'delivery_days' => 3
        ]);

        $response = $this->actingAs($buyer)
            ->withoutMiddleware(\App\Http\Middleware\EnsureSubscriptionIsActive::class)
            ->post(route('marketplace.orders.store'), [
                'package_id' => $package->id
            ]);

        $order = ServiceOrder::first();
        $this->assertNotNull($order);
        
        $buyerWallet->refresh();
        $sellerWallet->refresh();

        // 100 should be deducted from balance and moved to locked_balance
        $this->assertEquals(900, $buyerWallet->balance);
        $this->assertEquals(100, $buyerWallet->locked_balance);

        // Seller should have nothing yet
        $this->assertEquals(0, $sellerWallet->balance);
    }

    public function test_completion_releases_escrow_to_seller()
    {
        $seller = User::factory()->create(['onboarding_completed' => true]);
        $buyer = User::factory()->create(['onboarding_completed' => true]);

        // Manually setup order and escrow state
        $buyerWallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $buyer->id,
            'context' => 'user',
            'balance' => 900,
            'locked_balance' => 100,
            'currency' => 'USD'
        ]);

        $sellerWallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $seller->id,
            'context' => 'user',
            'balance' => 0,
            'locked_balance' => 0,
            'currency' => 'USD'
        ]);

        $category = \Modules\Marketplace\Models\ServiceCategory::create([
            'name' => 'Web Dev',
            'slug' => 'web-dev'
        ]);

        $service = Service::create([
            'seller_id' => $seller->id,
            'category_id' => $category->id,
            'title' => 'Test Service',
            'slug' => 'test-service',
            'description' => 'Test',
            'status' => 'published',
            'currency_code' => 'USD'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'Basic package',
            'price' => 100,
            'currency_code' => 'USD',
            'delivery_days' => 3
        ]);

        $order = ServiceOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'amount' => 100,
            'currency_code' => 'USD',
            'commission_amount' => 10, // 10%
            'status' => 'delivered'
        ]);

        $response = $this->actingAs($buyer)
            ->withoutMiddleware(\App\Http\Middleware\EnsureSubscriptionIsActive::class)
            ->post(route('marketplace.orders.complete', $order->id));
        
        $order->refresh();
        $this->assertEquals('completed', $order->status);

        $buyerWallet->refresh();
        $sellerWallet->refresh();

        // Locked balance released
        $this->assertEquals(0, $buyerWallet->locked_balance);
        $this->assertEquals(900, $buyerWallet->balance); // balance remains 900

        // Seller gets amount - commission (100 - 10 = 90)
        $this->assertEquals(90, $sellerWallet->balance);
    }
}
