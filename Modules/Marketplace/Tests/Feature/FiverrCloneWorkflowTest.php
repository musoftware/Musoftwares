<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Currency;
use App\Models\Conversation;
use App\Models\Message;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServiceReview;
use Modules\Marketplace\Models\ServiceExtra;
use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Enums\EscrowStatus;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class FiverrCloneWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $buyer;
    protected Currency $usd;
    protected ServiceCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Register the roles if they do not exist
        Role::firstOrCreate(['name' => 'seller']);
        Role::firstOrCreate(['name' => 'client']);

        // Set up currency
        $this->usd = Currency::firstOrCreate(['currency' => 'USD'], [
            'symbol' => '$',
            'string_format' => '$ %s'
        ]);

        $this->seller = User::factory()->create([
            'user_balance' => 0,
            'currency_id' => $this->usd->id,
            'email_verified_at' => now(),
            'onboarding_completed' => true
        ]);
        $this->seller->assignRole('seller');

        $this->buyer = User::factory()->create([
            'user_balance' => 1000,
            'currency_id' => $this->usd->id,
            'email_verified_at' => now(),
            'onboarding_completed' => true
        ]);
        $this->buyer->assignRole('client');

        $this->category = ServiceCategory::create([
            'name' => 'Design & Graphics',
            'slug' => 'design-graphics',
            'description' => 'Logo design and more'
        ]);

        // Dynamically register policies to bypass missing service provider registrations
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Marketplace\Models\ServiceOrder::class,
            \Modules\Marketplace\Policies\ServiceOrderPolicy::class
        );
        \Illuminate\Support\Facades\Gate::policy(
            \Modules\Marketplace\Models\Service::class,
            \Modules\Marketplace\Policies\ServicePolicy::class
        );
    }

    /**
     * Test 1: Service creation with packages and extras.
     */
    public function test_seller_can_create_service_with_packages_and_extras()
    {
        $payload = [
            'title' => 'Professional Logo Design',
            'description' => 'I will design a high quality logo for your business.',
            'category_id' => $this->category->id,
            'tags' => ['logo', 'design', 'vector'],
            'faq' => [
                ['question' => 'Do you provide vector source files?', 'answer' => 'Yes, in standard and premium packages.']
            ],
            'requirements' => ['Please upload your brand colors and guidelines.'],
            'is_free' => false,
            'packages' => [
                [
                    'name' => 'Basic Logo Package',
                    'description' => 'One simple logo concept with JPG and PNG formats.',
                    'price' => 150,
                    'currency_id' => $this->usd->id,
                    'delivery_days' => 3,
                    'revisions' => 2,
                    'features' => ['1 Concept', 'High Resolution']
                ],
                [
                    'name' => 'Premium Brand Package',
                    'description' => 'Three complex concepts with vector source files.',
                    'price' => 500,
                    'currency_id' => $this->usd->id,
                    'delivery_days' => 5,
                    'revisions' => 5,
                    'features' => ['3 Concepts', 'Source Files', 'Vector Format']
                ]
            ],
            'extras' => [
                [
                    'title' => 'Extra Fast Delivery',
                    'price' => 50,
                    'duration_days' => 1
                ]
            ]
        ];

        $response = $this->actingAs($this->seller)
            ->post(route('marketplace.services.store'), $payload);

        $response->assertRedirect();
        
        $this->assertDatabaseHas('marketplace_services', [
            'seller_id' => $this->seller->id,
            'title' => 'Professional Logo Design',
            'category_id' => $this->category->id,
            'status' => 'draft'
        ]);

        $service = Service::where('title', 'Professional Logo Design')->first();

        $this->assertCount(2, $service->packages);
        $this->assertCount(1, $service->extras);

        $this->assertDatabaseHas('marketplace_packages', [
            'service_id' => $service->id,
            'name' => 'Basic Logo Package',
            'price' => 150.00000000
        ]);

        $this->assertDatabaseHas('marketplace_service_extras', [
            'service_id' => $service->id,
            'title' => 'Extra Fast Delivery',
            'price' => 50.00
        ]);
    }

    /**
     * Test 2: Service browsing and filtering.
     */
    public function test_buyers_can_browse_and_filter_active_services()
    {
        // Create an active service
        $activeService = Service::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Active Writing Service',
            'description' => 'Active description',
            'status' => 'active'
        ]);

        // Create a draft service
        $draftService = Service::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Draft Design Service',
            'description' => 'Draft description',
            'status' => 'draft'
        ]);

        $response = $this->actingAs($this->buyer)->get(route('marketplace.services.index'));
        $response->assertStatus(200);

        $response->assertInertia(fn ($page) => $page
            ->component('Marketplace/Browse')
            ->has('services.data', 1)
            ->where('services.data.0.title', 'Active Writing Service')
        );

        // Filter by category
        $responseFiltered = $this->actingAs($this->buyer)->get(route('marketplace.services.index', ['category_id' => $this->category->id]));
        $responseFiltered->assertStatus(200);

        // Filter by search query
        $responseSearch = $this->actingAs($this->buyer)->get(route('marketplace.services.index', ['search' => 'Writing']));
        $responseSearch->assertInertia(fn ($page) => $page
            ->where('services.data.0.title', 'Active Writing Service')
        );
    }

    /**
     * Test 3: Order placement, wallet deduction, and escrow creation.
     */
    public function test_buyer_can_purchase_package_and_hold_funds_in_escrow()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Fiverr Clone Gig',
            'description' => 'Logo gig description',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Standard Package',
            'description' => 'Logo design details',
            'price' => 300.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 2
        ]);

        $response = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.store'), [
                'package_id' => $package->id
            ]);

        $order = ServiceOrder::where('buyer_id', $this->buyer->id)->first();
        $response->assertRedirect(route('marketplace.orders.show', $order->id));

        // Verify balance was deducted (used)
        $this->buyer->refresh();
        $this->assertEquals(700, $this->buyer->user_balance);

        // Verify order record created with correct amounts
        $this->assertDatabaseHas('marketplace_orders', [
            'id' => $order->id,
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'amount' => 300.00,
            'status' => ServiceOrderStatus::PENDING->value
        ]);

        // Verify escrow was held
        $this->assertDatabaseHas('marketplace_escrows', [
            'order_id' => $order->id,
            'amount' => 300.00,
            'status' => EscrowStatus::HELD->value
        ]);

        // Verify conversation was created
        $this->assertDatabaseHas('conversations', [
            'conversable_type' => ServiceOrder::class,
            'conversable_id' => $order->id,
            'type' => 'marketplace_order',
            'status' => 'open'
        ]);
    }

    /**
     * Test 4: Buyer and seller messaging.
     */
    public function test_parties_can_message_inside_order_conversation()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Messaging Gig',
            'description' => 'Desc',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Pkg',
            'description' => 'Desc',
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 3
        ]);

        // Place order
        $this->actingAs($this->buyer)->post(route('marketplace.orders.store'), ['package_id' => $package->id]);
        $order = ServiceOrder::first();

        // Seller posts a message
        $responseSeller = $this->actingAs($this->seller)
            ->post(route('marketplace.orders.messages.store', $order->id), [
                'body' => 'Hello buyer, I have started working on your order.'
            ]);
        $responseSeller->assertRedirect();

        // Buyer posts a message
        $responseBuyer = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.messages.store', $order->id), [
                'body' => 'Thank you, looking forward to it.'
            ]);
        $responseBuyer->assertRedirect();

        $conversation = Conversation::where('conversable_id', $order->id)->first();
        $this->assertCount(2, $conversation->messages);
        
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->seller->id,
            'body' => 'Hello buyer, I have started working on your order.'
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'sender_id' => $this->buyer->id,
            'body' => 'Thank you, looking forward to it.'
        ]);
    }

    /**
     * Test 5: Service delivery by seller.
     */
    public function test_seller_can_deliver_order_with_payload()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Delivery Gig',
            'description' => 'Desc',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Pkg',
            'description' => 'Desc',
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 3
        ]);

        $this->actingAs($this->buyer)->post(route('marketplace.orders.store'), ['package_id' => $package->id]);
        $order = ServiceOrder::first();

        // Deliver order
        $payload = [
            'message' => 'Here is your finished logo files.',
            'links' => 'https://drive.google.com/file/d/logo_assets'
        ];

        $response = $this->actingAs($this->seller)
            ->post(route('marketplace.orders.deliver', $order->id), $payload);

        $response->assertRedirect();
        
        $order->refresh();
        $this->assertEquals(ServiceOrderStatus::DELIVERED, $order->status);
        $this->assertNotNull($order->delivered_at);
        $this->assertNotNull($order->auto_complete_at);
        $this->assertEquals('Here is your finished logo files.', $order->delivery_payload['message']);
        $this->assertEquals('https://drive.google.com/file/d/logo_assets', $order->delivery_payload['links']);
    }

    /**
     * Test 6: Order completion and escrow release.
     */
    public function test_buyer_can_complete_delivered_order_and_release_escrow()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Completion Gig',
            'description' => 'Desc',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Pkg',
            'description' => 'Desc',
            'price' => 200.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 3
        ]);

        $this->actingAs($this->buyer)->post(route('marketplace.orders.store'), ['package_id' => $package->id]);
        $order = ServiceOrder::first();

        // Deliver
        $this->actingAs($this->seller)->post(route('marketplace.orders.deliver', $order->id));

        // Complete
        $response = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.complete', $order->id));

        $response->assertRedirect();

        $order->refresh();
        $this->assertEquals(ServiceOrderStatus::COMPLETED, $order->status);
        $this->assertNotNull($order->completed_at);

        // Verify seller is credited amount minus commission
        // In ServiceOrderController: commission rate = 10%, price = 200 => commission_amount = 20
        // Earnings = 180
        $this->seller->refresh();
        $this->assertEquals(180, $this->seller->user_balance);

        // Verify Escrow is updated to RELEASED
        $this->assertDatabaseHas('marketplace_escrows', [
            'order_id' => $order->id,
            'status' => EscrowStatus::RELEASED->value
        ]);
    }

    /**
     * Test 7: Review submission and automatic sync.
     */
    public function test_buyer_can_review_completed_order_and_syncs_rating()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Review Gig',
            'description' => 'Desc',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Pkg',
            'description' => 'Desc',
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 1
        ]);

        $this->actingAs($this->buyer)->post(route('marketplace.orders.store'), ['package_id' => $package->id]);
        $order = ServiceOrder::first();

        // Deliver and Complete
        $this->actingAs($this->seller)->post(route('marketplace.orders.deliver', $order->id));
        $this->actingAs($this->buyer)->post(route('marketplace.orders.complete', $order->id));

        // Review order
        $response = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.review.store', $order->id), [
                'rating' => 5,
                'review' => 'Excellent work! Highly recommended!'
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('service_reviews', [
            'service_id' => $service->id,
            'order_id' => $order->id,
            'reviewer_id' => $this->buyer->id,
            'rating' => 5,
            'review' => 'Excellent work! Highly recommended!'
        ]);

        // Verify Service averages updated
        $service->refresh();
        $this->assertEquals(5.00, $service->avg_rating);
        $this->assertEquals(1, $service->review_count);
    }

    /**
     * Test 8: Order dispute and refund workflow.
     */
    public function test_order_dispute_and_refund_returns_escrow_to_buyer()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Refund Gig',
            'description' => 'Desc',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Pkg',
            'description' => 'Desc',
            'price' => 400.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 3
        ]);

        $this->actingAs($this->buyer)->post(route('marketplace.orders.store'), ['package_id' => $package->id]);
        $order = ServiceOrder::first();

        // Buyer balance is now 600
        $this->buyer->refresh();
        $this->assertEquals(600, $this->buyer->user_balance);

        // Dispute
        $responseDispute = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.dispute', $order->id));
        $responseDispute->assertRedirect();

        $order->refresh();
        $this->assertEquals(ServiceOrderStatus::DISPUTED, $order->status);

        $escrow = MarketplaceEscrow::where('order_id', $order->id)->first();
        $this->assertEquals(EscrowStatus::DISPUTED, $escrow->status);

        // Admin/System RefundEscrow
        $escrowService = resolve(\Modules\Marketplace\Services\EscrowService::class);
        $escrowService->refundFunds($escrow);

        // Buyer balance restored
        $this->buyer->refresh();
        $this->assertEquals(1000, $this->buyer->user_balance);

        $escrow->refresh();
        $this->assertEquals(EscrowStatus::REFUNDED, $escrow->status);
    }

    /**
     * Test 9: Security and policy authorization checks.
     */
    public function test_unauthorized_user_cannot_access_or_modify_order()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Secure Gig',
            'description' => 'Desc',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Pkg',
            'description' => 'Desc',
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 3
        ]);

        // Place order
        $this->actingAs($this->buyer)->post(route('marketplace.orders.store'), ['package_id' => $package->id]);
        $order = ServiceOrder::first();

        $otherUser = User::factory()->create([
            'email_verified_at' => now(),
            'onboarding_completed' => true
        ]);

        // 1. Show order details
        $this->actingAs($otherUser)
            ->get(route('marketplace.orders.show', $order->id))
            ->assertStatus(403);

        // 2. Deliver order
        $this->actingAs($otherUser)
            ->post(route('marketplace.orders.deliver', $order->id))
            ->assertStatus(403);

        // 3. Complete order
        $this->actingAs($otherUser)
            ->post(route('marketplace.orders.complete', $order->id))
            ->assertStatus(403);

        // 4. Dispute order
        $this->actingAs($otherUser)
            ->post(route('marketplace.orders.dispute', $order->id))
            ->assertStatus(403);
    }

    /**
     * Test 10: Validate timezone constraints in Cairo timezone.
     */
    public function test_cairo_timezone_consistency()
    {
        // Set timezone context
        config(['app.timezone' => 'Africa/Cairo']);
        date_default_timezone_set('Africa/Cairo');

        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Cairo Time Gig',
            'description' => 'Desc',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Pkg',
            'description' => 'Desc',
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 5
        ]);

        $this->actingAs($this->buyer)->post(route('marketplace.orders.store'), ['package_id' => $package->id]);
        $order = ServiceOrder::first();

        // Deliver the order
        $this->actingAs($this->seller)->post(route('marketplace.orders.deliver', $order->id));
        $order->refresh();

        // Expected auto_complete_at is delivered_at + 3 days
        $expectedAutoComplete = $order->delivered_at->copy()->addDays(3);
        
        $this->assertTrue(
            $order->auto_complete_at->equalTo($expectedAutoComplete),
            "Estimated completion dates must be calculated correctly."
        );

        $this->assertEquals(
            'Africa/Cairo',
            $order->delivered_at->timezoneName,
            "Delivered timestamps should align with the Cairo timezone rules."
        );
    }

    /**
     * Test 11: Seller cannot purchase their own service.
     */
    public function test_seller_cannot_purchase_their_own_service()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Own Service Gig',
            'description' => 'Desc',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Own Package',
            'description' => 'Desc',
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 3
        ]);

        $response = $this->actingAs($this->seller)
            ->post(route('marketplace.orders.store'), [
                'package_id' => $package->id
            ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertEquals(0, ServiceOrder::where('seller_id', $this->seller->id)->count());
    }

    /**
     * Test 12: Buyer can purchase a free service package.
     */
    public function test_buyer_can_purchase_free_package()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Free Service Gig',
            'description' => 'Desc',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Free Package',
            'description' => 'Free description',
            'price' => 0.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 1
        ]);

        $response = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.store'), [
                'package_id' => $package->id
            ]);

        $order = ServiceOrder::where('buyer_id', $this->buyer->id)->where('amount', 0)->first();
        $response->assertRedirect(route('marketplace.orders.show', $order->id));

        $this->buyer->refresh();
        $this->assertEquals(1000, $this->buyer->user_balance); // Balance unchanged

        $this->assertDatabaseHas('marketplace_escrows', [
            'order_id' => $order->id,
            'amount' => 0,
            'status' => EscrowStatus::HELD->value
        ]);
    }

    /**
     * Test 13: Console command auto-completes matured orders.
     */
    public function test_console_command_auto_completes_matured_orders()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Auto-Complete Gig',
            'description' => 'Desc',
            'status' => 'active'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Auto-Complete Pkg',
            'description' => 'Desc',
            'price' => 200.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 1
        ]);

        // Create matured order (delivered, auto_complete_at is in the past)
        $maturedOrder = ServiceOrder::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'package_id' => $package->id,
            'amount' => 200.00,
            'commission_amount' => 20.00,
            'currency_id' => $this->usd->id,
            'status' => ServiceOrderStatus::DELIVERED,
            'delivered_at' => now()->subDays(4),
            'auto_complete_at' => now()->subDays(1)
        ]);

        // Create escrow record
        MarketplaceEscrow::create([
            'order_id' => $maturedOrder->id,
            'amount' => 200.00,
            'currency_id' => $this->usd->id,
            'business_amount' => 200.00,
            'business_currency_id' => $this->usd->id,
            'exchange_rate' => 1.0,
            'exchange_rate_date' => now()->toDateString(),
            'status' => EscrowStatus::HELD
        ]);

        // Create unmatured order (delivered, auto_complete_at is in the future)
        $unmaturedOrder = ServiceOrder::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'package_id' => $package->id,
            'amount' => 200.00,
            'commission_amount' => 20.00,
            'currency_id' => $this->usd->id,
            'status' => ServiceOrderStatus::DELIVERED,
            'delivered_at' => now()->subHours(1),
            'auto_complete_at' => now()->addDays(3)
        ]);

        // Execute console command via Artisan facade
        $exitCode = \Illuminate\Support\Facades\Artisan::call('marketplace:auto-complete-orders');
        $this->assertEquals(0, $exitCode);

        $maturedOrder->refresh();
        $this->assertEquals(ServiceOrderStatus::COMPLETED, $maturedOrder->status);
        $this->assertNotNull($maturedOrder->completed_at);

        $unmaturedOrder->refresh();
        $this->assertEquals(ServiceOrderStatus::DELIVERED, $unmaturedOrder->status);
        $this->assertNull($unmaturedOrder->completed_at);

        // Verify seller credited for matured order
        $this->seller->refresh();
        $this->assertEquals(180, $this->seller->user_balance);
    }

    /**
     * Test 14: Buyer cannot purchase package of an inactive/draft service.
     */
    public function test_cannot_purchase_package_of_inactive_service()
    {
        $service = Service::create([
            'seller_id' => $this->seller->id,
            'title' => 'Draft Gig',
            'description' => 'Desc',
            'status' => 'draft'
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Draft Package',
            'description' => 'Desc',
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 3
        ]);

        $response = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.store'), [
                'package_id' => $package->id
            ]);

        $response->assertSessionHasErrors(['error']);
        $this->assertEquals(0, ServiceOrder::where('buyer_id', $this->buyer->id)->count());
    }
}
