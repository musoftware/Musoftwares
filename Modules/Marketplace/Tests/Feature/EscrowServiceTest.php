<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use App\Models\User;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Enums\EscrowStatus;
use Modules\Marketplace\Services\EscrowService;
use Modules\Marketplace\Models\MarketplaceEscrow;

class EscrowServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_escrow_release_credits_seller_correctly()
    {
        $buyer = User::factory()->create(['user_balance' => 1000]);
        $seller = User::factory()->create(['user_balance' => 0]);

        $category = \Modules\Marketplace\Models\ServiceCategory::create(['name' => 'Test', 'slug' => 'test']);
        $service = \Modules\Marketplace\Models\Service::create(['seller_id' => $seller->id, 'title' => 'Test Service', 'category_id' => $category->id, 'description' => 'test', 'status' => 'active']);
        $package = \Modules\Marketplace\Models\ServicePackage::create(['service_id' => $service->id, 'name' => 'Basic', 'description' => 'test', 'price' => 100, 'currency_id' => 1, 'delivery_days' => 1]);

        $order = ServiceOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'amount' => 100,
            'commission_amount' => 10, // 10%
            'currency_id' => 1,
            'status' => 'pending'
        ]);

        $service = new EscrowService();
        $escrow = $service->holdFunds($order);

        $buyer->refresh();
        $this->assertEquals(900, $buyer->user_balance);

        $service->releaseFunds($escrow);

        $seller->refresh();
        $this->assertEquals(90, $seller->user_balance); // 100 - 10 commission
        
        $escrow->refresh();
        $this->assertEquals(EscrowStatus::RELEASED, $escrow->status);
    }

    public function test_escrow_refund_credits_buyer()
    {
        $buyer = User::factory()->create(['user_balance' => 1000]);
        $seller = User::factory()->create(['user_balance' => 0]);

        $category = \Modules\Marketplace\Models\ServiceCategory::create(['name' => 'Test', 'slug' => 'test']);
        $service = \Modules\Marketplace\Models\Service::create(['seller_id' => $seller->id, 'title' => 'Test Service', 'category_id' => $category->id, 'description' => 'test', 'status' => 'active']);
        $package = \Modules\Marketplace\Models\ServicePackage::create(['service_id' => $service->id, 'name' => 'Basic', 'description' => 'test', 'price' => 100, 'currency_id' => 1, 'delivery_days' => 1]);

        $order = ServiceOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'amount' => 100,
            'commission_amount' => 10,
            'currency_id' => 1,
            'status' => 'pending'
        ]);

        $service = new EscrowService();
        $escrow = $service->holdFunds($order);

        $buyer->refresh();
        $this->assertEquals(900, $buyer->user_balance);

        $service->refundFunds($escrow);

        $buyer->refresh();
        $this->assertEquals(1000, $buyer->user_balance); 

        $escrow->refresh();
        $this->assertEquals(EscrowStatus::REFUNDED, $escrow->status);
    }
}
