<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Services\CheckoutService;
use Modules\Marketplace\Services\EscrowService;
use Modules\Marketplace\Services\PromotionsService;

class MultiCurrencyAndCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_deducts_buyer_wallet_and_holds_escrow()
    {
        $buyer = User::factory()->create(['user_balance' => 500]);
        $seller = User::factory()->create(['user_balance' => 0]);

        $category = ServiceCategory::create(['name' => 'Design', 'slug' => 'design-check']);
        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'UI Mockup Design',
            'category_id' => $category->id,
            'description' => 'Figma UI design',
            'status' => 'active',
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Standard Package',
            'description' => '3 screens design',
            'price' => 200,
            'currency_id' => 1,
            'delivery_days' => 4,
        ]);

        $checkoutService = new CheckoutService(new EscrowService(), new PromotionsService());
        $order = $checkoutService->processCheckout($buyer, $package->id);

        $buyer->refresh();
        $this->assertEquals(300, $buyer->user_balance); // 500 - 200 held in escrow
        $this->assertEquals('pending', $order->status->value);

        $this->assertDatabaseHas('marketplace_escrows', [
            'order_id' => $order->id,
            'amount' => 200,
            'status' => 'held',
        ]);
    }

    public function test_checkout_fails_on_insufficient_balance()
    {
        $buyer = User::factory()->create(['user_balance' => 50]);
        $seller = User::factory()->create(['user_balance' => 0]);

        $category = ServiceCategory::create(['name' => 'Design', 'slug' => 'design-fail']);
        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'Branding Kit',
            'category_id' => $category->id,
            'description' => 'Complete brand kit',
            'status' => 'active',
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Full Kit',
            'description' => 'Brand kit',
            'price' => 300,
            'currency_id' => 1,
            'delivery_days' => 5,
        ]);

        $checkoutService = new CheckoutService(new EscrowService(), new PromotionsService());

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(__('marketplace.insufficient_balance_for_checkout', ['required' => 300, 'available' => 0]));
        $checkoutService->processCheckout($buyer, $package->id);
    }
}
