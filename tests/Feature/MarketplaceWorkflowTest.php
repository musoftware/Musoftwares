<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use App\Models\Wallet;
use Tests\TestCase;

class MarketplaceWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $buyer;
    protected User $seller;
    protected User $admin;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->buyer = User::factory()->create(['onboarding_completed' => true]);
        $this->buyer->assignRole('client');

        $this->seller = User::factory()->create(['onboarding_completed' => true]);
        $this->seller->assignRole('client');

        $this->admin = User::factory()->create(['onboarding_completed' => true]);
        $this->admin->assignRole('admin');

        $this->category = ServiceCategory::create([
            'name' => 'Web Development',
            'slug' => 'web-development',
            'description' => 'PHP, Laravel, React development',
        ]);
    }

    public function test_can_browse_marketplace_services(): void
    {
        $response = $this->get(route('marketplace.services.index'));
        $response->assertStatus(200);
    }

    public function test_seller_can_create_and_admin_approves_service(): void
    {
        // 1. Seller creates service (draft)
        $response = $this->actingAs($this->seller)
            ->post(route('marketplace.services.store'), [
                'title' => 'Stunning NextJS App development',
                'description' => 'I will build a high quality landing page using React & Next.js. This description is very long to satisfy the 100 character minimum requirement of the ServiceController store validation rules.',
                'category_id' => $this->category->id,
                'packages' => [
                    [
                        'name' => 'Basic Package',
                        'description' => 'Basic web development services',
                        'price' => 100.00,
                        'currency_code' => 'USD',
                        'delivery_days' => 5,
                    ]
                ]
            ]);

        $this->assertDatabaseHas('marketplace_services', [
            'seller_id' => $this->seller->id,
            'title' => 'Stunning NextJS App development',
            'status' => 'draft',
        ]);

        $service = Service::where('title', 'Stunning NextJS App development')->first();

        // 2. Admin approves service
        $response = $this->actingAs($this->admin)
            ->post(route('admin.marketplace.services.approve', $service->id));

        $response->assertStatus(302);
        $this->assertEquals('active', $service->fresh()->status);
    }

    public function test_buyer_can_purchase_service_package_workflow(): void
    {
        // 1. Setup service and packages
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Fullstack Laravel development',
            'description' => 'Stunning Web Application using Laravel and Inertia.',
            'status' => 'active',
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Premium Pack',
            'description' => 'Complete Laravel SaaS application',
            'price' => 500.00,
            'currency_code' => 'USD',
            'delivery_days' => 10,
        ]);

        // 2. Initialize wallets with enough balance
        $buyerWallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $this->buyer->id,
            'context' => 'user',
            'balance' => 1000.00,
            'currency' => 'USD',
        ]);

        $sellerWallet = Wallet::create([
            'owner_type' => User::class,
            'owner_id' => $this->seller->id,
            'context' => 'user',
            'balance' => 100.00,
            'currency' => 'USD',
        ]);

        // 3. Buyer purchases the package
        $response = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.store'), [
                'package_id' => $package->id,
            ]);

        $response->assertStatus(302);

        // Verification: Order is created
        $this->assertDatabaseHas('marketplace_orders', [
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'package_id' => $package->id,
            'amount' => 500.00,
            'currency_code' => 'USD',
            'commission_amount' => 50.00, // 10% commission
            'status' => 'pending',
        ]);

        $order = ServiceOrder::where('buyer_id', $this->buyer->id)->first();

        // Verification: Wallet deductions and credits (500 debited from buyer, 450 credited to seller)
        $this->assertEquals(500.00, $buyerWallet->fresh()->balance);
        $this->assertEquals(550.00, $sellerWallet->fresh()->balance);

        // Verification: Conversation created
        $this->assertDatabaseHas('conversations', [
            'conversable_type' => ServiceOrder::class,
            'conversable_id' => $order->id,
            'type' => 'marketplace_order',
        ]);

        // 4. Seller delivers the service order
        $response = $this->actingAs($this->seller)
            ->post(route('marketplace.orders.deliver', $order->id));

        $response->assertStatus(302);
        $this->assertEquals('delivered', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->delivered_at);

        // 5. Buyer completes the service order
        $response = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.complete', $order->id));

        $response->assertStatus(302);
        $this->assertEquals('completed', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);
    }
}
