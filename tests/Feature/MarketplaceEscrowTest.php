<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\MarketplaceEscrow;
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
        $usdCurrency = \App\Models\Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );
        $seller = User::factory()->create(['onboarding_completed' => true, 'currency_id' => $usdCurrency->id]);
        $buyer = User::factory()->create(['onboarding_completed' => true, 'currency_id' => $usdCurrency->id]);

        // Give buyer some money
        $buyer->user_balance = 1000;
        $buyer->save();
        $seller->user_balance = 0;
        $seller->save();

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
            'currency_id' => 1
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'Basic package',
            'price' => 100,
            'currency_id' => 1,
            'delivery_days' => 3
        ]);

        $response = $this->actingAs($buyer)
            ->withoutMiddleware(\App\Http\Middleware\EnsureSubscriptionIsActive::class)
            ->post(route('marketplace.orders.store'), [
                'package_id' => $package->id
            ]);

        $order = ServiceOrder::first();
        $this->assertNotNull($order);
        
        // 100 should be deducted from balance
        $this->assertEquals(900, $buyer->fresh()->user_balance);
        
        // Escrow should hold 100
        $escrow = MarketplaceEscrow::where('order_id', $order->id)->first();
        $this->assertNotNull($escrow);
        $this->assertEquals(\Modules\Marketplace\Enums\EscrowStatus::HELD->value, is_object($escrow->status) ? $escrow->status->value : $escrow->status);
        $this->assertEquals(100, $escrow->amount);

        // Seller should have nothing yet
        $this->assertEquals(0, $seller->fresh()->user_balance);
    }

    public function test_completion_releases_escrow_to_seller()
    {
        $usdCurrency = \App\Models\Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['symbol' => '$', 'string_format' => '$%01.2f']
        );
        $seller = User::factory()->create(['onboarding_completed' => true, 'currency_id' => $usdCurrency->id]);
        $buyer = User::factory()->create(['onboarding_completed' => true, 'currency_id' => $usdCurrency->id]);

        // Manually setup order and escrow state
        $buyer->user_balance = 1000;
        $buyer->save();
        $seller->user_balance = 0;
        $seller->save();

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
            'currency_id' => 1
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'Basic package',
            'price' => 100,
            'currency_id' => 1,
            'delivery_days' => 3
        ]);

        $order = ServiceOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'amount' => 100,
            'currency_id' => 1,
            'commission_amount' => 10, // 10%
            'status' => 'delivered'
        ]);

        $escrowService = app(\Modules\Marketplace\Services\EscrowService::class);
        // holdFunds will deduct 100 from 1000, leaving 900.
        $escrowService->holdFunds($order);

        $response = $this->actingAs($buyer)
            ->withoutMiddleware(\App\Http\Middleware\EnsureSubscriptionIsActive::class)
            ->post(route('marketplace.orders.complete', $order->id));
        
        $order->refresh();
        $this->assertEquals(\Modules\Marketplace\Enums\ServiceOrderStatus::COMPLETED->value, is_object($order->status) ? $order->status->value : $order->status);

        // Escrow released (Marketplace order completion controller handles this by crediting seller)
        $this->assertEquals(900, $buyer->fresh()->user_balance); // balance remains 900

        // Seller gets amount - commission (100 - 10 = 90)
        $this->assertEquals(90, $seller->fresh()->user_balance);
    }
}
