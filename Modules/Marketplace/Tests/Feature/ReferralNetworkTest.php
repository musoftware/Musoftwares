<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\UserReferral;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Services\ReferralService;

class ReferralNetworkTest extends TestCase
{
    use RefreshDatabase;

    public function test_referral_commission_calculation_and_crediting()
    {
        $referrer = User::factory()->create(['user_balance' => 0]);
        $buyer = User::factory()->create(['user_balance' => 500]);
        $seller = User::factory()->create(['user_balance' => 0]);

        UserReferral::create([
            'user_id' => $referrer->id,
            'title' => 'Default Referral',
            'key' => 'REF123',
            'slug' => 'ref123',
        ]);


        $category = ServiceCategory::create(['name' => 'Marketing', 'slug' => 'marketing']);
        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'Social Media Management',
            'category_id' => $category->id,
            'description' => 'Social media management',
            'status' => 'active',
            'referral_commission_from' => 'seller',
            'referral_commission_percentage' => 10.0,
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Monthly Package',
            'description' => 'Monthly management',
            'price' => 200,
            'currency_id' => 1,
            'delivery_days' => 30,
        ]);

        $order = ServiceOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'amount' => 200,
            'commission_amount' => 20,
            'currency_id' => 1,
            'status' => 'completed',
        ]);

        $referralService = new ReferralService();
        $earnedCommission = $referralService->processOrderReferralCommission($order);

        $this->assertEquals(20.0, $earnedCommission); // 10% of 200 seller price

        $referrer->refresh();
        $this->assertEquals(20.0, $referrer->user_balance);
    }

    public function test_referral_withdrawal_request_flow()
    {
        $user = User::factory()->create(['user_balance' => 150]);
        $pm = \App\Models\UserPaymentMethod::create([
            'user_id' => $user->id,
            'type' => 'instapay',
            'mobile' => '01012345678',
        ]);

        $referralService = new ReferralService();
        $withdrawRequest = $referralService->requestWithdrawal($user, 100.0, 'instapay', ['phone' => '01012345678'], $pm->id);

        $this->assertEquals('pending', $withdrawRequest->status);
        $this->assertEquals(100.0, $withdrawRequest->amount);

        $user->refresh();
        $this->assertEquals(50.0, $user->user_balance); // Balance locked during review
    }


}
