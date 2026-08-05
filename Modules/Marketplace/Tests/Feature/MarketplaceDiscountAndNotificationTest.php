<?php

namespace Modules\Marketplace\Tests\Feature;

use App\Models\Currency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\Marketplace\Emails\DiscountDigestMail;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServicePackage;
use Tests\TestCase;

class MarketplaceDiscountAndNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_package_calculates_discount_correctly()
    {
        $seller = User::factory()->create();
        $currency = Currency::firstOrCreate(['id' => 1], ['currency' => 'USD', 'symbol' => '$', 'name' => 'US Dollar']);
        $category = ServiceCategory::create(['name' => 'Design', 'slug' => 'design']);

        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'Logo Design Special Offer',
            'category_id' => $category->id,
            'description' => 'Professional logo design at discounted rate',
            'status' => 'active',
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic Package',
            'description' => '1 logo concept',
            'price' => 75.00,
            'old_price' => 100.00,
            'currency_id' => $currency->id,
            'delivery_days' => 2,
        ]);

        $this->assertTrue($package->has_discount);
        $this->assertEquals(25, $package->discount_percentage);
    }

    public function test_send_daily_discount_notifications_command_sends_emails()
    {
        Mail::fake();

        $seller = User::factory()->create();
        $currency = Currency::firstOrCreate(['id' => 1], ['currency' => 'USD', 'symbol' => '$', 'name' => 'US Dollar']);
        $category = ServiceCategory::create(['name' => 'Marketing', 'slug' => 'marketing']);

        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'SEO Audit Discount',
            'category_id' => $category->id,
            'description' => 'Complete SEO audit',
            'status' => 'active',
        ]);

        ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Full Audit',
            'description' => 'Detailed report',
            'price' => 50.00,
            'old_price' => 100.00,
            'currency_id' => $currency->id,
            'delivery_days' => 1,
        ]);

        User::factory()->count(10)->create();

        $exitCode = $this->artisan('marketplace:send-daily-discounts', ['--limit' => 5]);
        $this->assertEquals(0, $exitCode);

        Mail::assertSent(DiscountDigestMail::class);
    }
}
