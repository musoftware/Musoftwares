<?php

namespace Tests\Feature\Admin;

use App\Models\Currency;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServicePackage;
use Tests\TestCase;

class MarketplaceOrderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $buyerUser;

    protected User $sellerUser;

    protected ServiceCategory $category;

    protected Service $service;

    protected ServicePackage $package;

    protected ServiceOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->buyerUser = User::factory()->create(['onboarding_completed' => true]);
        $this->buyerUser->assignRole('client');

        $this->sellerUser = User::factory()->create(['onboarding_completed' => true]);

        $currency = Currency::first();
        if (! $currency) {
            $currency = Currency::create([
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 1,
            ]);
        }

        $this->category = ServiceCategory::create([
            'name' => 'Web Dev',
            'slug' => 'web-dev',
        ]);

        $this->service = Service::create([
            'seller_id' => $this->sellerUser->id,
            'category_id' => $this->category->id,
            'title' => 'My Service',
            'description' => 'A great service',
            'status' => 'active',
        ]);

        $this->package = ServicePackage::create([
            'service_id' => $this->service->id,
            'name' => 'Basic',
            'description' => 'Basic package',
            'price' => 50,
            'delivery_days' => 2,
        ]);

        $this->order = ServiceOrder::create([
            'buyer_id' => $this->buyerUser->id,
            'seller_id' => $this->sellerUser->id,
            'package_id' => $this->package->id,
            'amount' => 50,
            'currency_id' => $currency->id,
            'commission_amount' => 5,
            'business_amount' => 50,
            'business_currency_id' => $currency->id,
            'status' => 'pending',
        ]);
    }

    public function test_admin_can_view_orders_index(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/marketplace/orders');
        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_view_orders_index(): void
    {
        $response = $this->actingAs($this->buyerUser)->get('/admin/marketplace/orders');
        $response->assertStatus(403);
    }

    public function test_admin_can_view_order_show(): void
    {
        $response = $this->actingAs($this->admin)->get("/admin/marketplace/orders/{$this->order->id}");
        $response->assertStatus(200);
    }

    public function test_admin_can_resolve_dispute(): void
    {
        // Order must be disputed to resolve dispute
        $this->order->update(['status' => 'disputed']);

        $response = $this->actingAs($this->admin)->post("/admin/marketplace/orders/{$this->order->id}/dispute", [
            'action' => 'refund_buyer', // this should match validation in ResolveMarketplaceDisputeRequest
        ]);

        $response->assertRedirect();
        // Just verify it doesn't return 500 error, sometimes it redirects back with errors if action logic fails
        // but checking session has no errors is fine
        $response->assertSessionHasNoErrors();
    }
}
