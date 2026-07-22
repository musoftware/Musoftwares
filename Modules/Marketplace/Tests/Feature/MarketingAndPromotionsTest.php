<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Coupon;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceDiscount;
use Modules\Marketplace\Models\ServiceLandingPage;
use Modules\Marketplace\Services\PromotionsService;
use Modules\Marketplace\Services\AbTestingService;

class MarketingAndPromotionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_smart_discounts_and_coupon_application()
    {
        $user = User::factory()->create();

        $category = ServiceCategory::create(['name' => 'SEO', 'slug' => 'seo']);
        $service = Service::create([
            'seller_id' => User::factory()->create()->id,
            'title' => 'SEO Audit',
            'category_id' => $category->id,
            'description' => 'Complete website SEO audit',
            'status' => 'active',
        ]);

        ServiceDiscount::create([
            'service_id' => $service->id,
            'code' => 'BLACKFRIDAY',
            'percentage' => 20,
            'is_active' => true,
        ]);


        $promotionsService = new PromotionsService();
        $discountRes = $promotionsService->calculateServiceDiscount(100.0, $service->id, $user);

        $this->assertTrue($discountRes['applied']);
        $this->assertEquals(80.0, $discountRes['final_price']);
        $this->assertEquals(20.0, $discountRes['discount_amount']);

        // Coupon application
        $coupon = Coupon::create([
            'name' => 'Welcome Coupon',
            'code' => 'WELCOME10',
            'type' => 'fixed',
            'discount_amount' => 10,
            'currency_id' => 1,
            'is_active' => true,
        ]);




        $couponRes = $promotionsService->applyCoupon('WELCOME10', 80.0, $user);
        $this->assertEquals(70.0, $couponRes['final_amount']);
        $this->assertEquals(10.0, $couponRes['discount_amount']);
    }

    public function test_ab_testing_metrics_tracking()
    {
        $category = ServiceCategory::create(['name' => 'Copywriting', 'slug' => 'copywriting']);
        $service = Service::create([
            'seller_id' => User::factory()->create()->id,
            'title' => 'Sales Copywriting',
            'category_id' => $category->id,
            'description' => 'High converting sales copy',
            'status' => 'active',
        ]);

        $landingPage = ServiceLandingPage::create([
            'service_id' => $service->id,
            'title' => 'Landing Page V1',
            'slug' => 'landing-v1',
        ]);

        $abService = new AbTestingService();
        $metric = $abService->trackMetric($landingPage->id, 'cta_click', null, [
            'scroll_depth' => 75,
            'time_on_page' => 45,
            'conversion_value' => 150,
        ]);

        $this->assertEquals($landingPage->id, $metric->landing_page_id);
        $this->assertEquals('cta_click', $metric->event_type);
        $this->assertEquals(75, $metric->scroll_depth);
    }
}
