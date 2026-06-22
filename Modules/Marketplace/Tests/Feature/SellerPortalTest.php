<?php

namespace Modules\Marketplace\Tests\Feature;

use App\Models\User;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Enums\EscrowStatus;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellerPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $buyer;

    protected function setUp(): void
    {
        parent::setUp();
        
        Role::firstOrCreate(['name' => 'seller']);

        $this->seller = User::factory()->create();
        $this->seller->assignRole('seller');

        $this->buyer = User::factory()->create();
        
        // Needed for some models
        Currency::firstOrCreate(['currency' => 'USD'], [
            'symbol' => '$',
            'string_format' => '$ %s'
        ]);
    }

    public function test_seller_can_access_dashboard_and_see_stats()
    {
        $category = ServiceCategory::create(['name' => 'Test', 'slug' => 'test']);
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'category_id' => $category->id,
            'title' => 'Test Service',
            'description' => 'Test Description',
            'status' => 'active',
        ]);
        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'Basic Description',
            'price' => 100,
            'delivery_days' => 1,
        ]);

        $order = ServiceOrder::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'package_id' => $package->id,
            'amount' => 100,
            'currency_id' => 1,
            'commission_amount' => 10,
            'status' => ServiceOrderStatus::COMPLETED,
        ]);

        MarketplaceEscrow::create([
            'order_id' => $order->id,
            'amount' => 100,
            'currency_id' => 1,
            'business_amount' => 100,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'status' => EscrowStatus::HELD,
        ]);

        $response = $this->actingAs($this->seller)
            ->get(route('seller.dashboard'));

        $response->assertStatus(200);
        
        // Let's assert Inertia response
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Marketplace/Seller/Dashboard')
            ->where('stats.total_sales', 100)
            ->where('stats.active_products', 1)
            ->where('stats.pending_payouts', 100)
            ->has('recent_orders', 1)
        );
    }

    public function test_non_seller_cannot_access_dashboard()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->get(route('seller.dashboard'));

        // Assuming middleware 'role:seller' aborts with 403 or redirects
        $response->assertStatus(403);
    }

    public function test_seller_can_access_products_page()
    {
        $response = $this->actingAs($this->seller)
            ->get(route('seller.products'));

        $response->assertStatus(200);
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Marketplace/Seller/Products')
            ->has('products.data', 0)
        );
    }

    public function test_seller_can_access_payouts_page()
    {
        $response = $this->actingAs($this->seller)
            ->get(route('seller.payouts'));

        $response->assertStatus(200);
        $response->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page
            ->component('Marketplace/Seller/Payouts')
            ->has('escrows.data', 0)
        );
    }
}
