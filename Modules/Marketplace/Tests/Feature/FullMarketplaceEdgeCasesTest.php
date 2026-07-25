<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Currency;
use App\Models\Conversation;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\OrderRevision;
use Modules\Marketplace\Models\OrderStatusHistory;
use Modules\Marketplace\Models\MarketplaceAttachment;
use Modules\Marketplace\Models\CustomOffer;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Enums\EscrowStatus;
use Spatie\Permission\Models\Role;

class FullMarketplaceEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected User $seller;
    protected User $buyer;
    protected Currency $usd;
    protected ServiceCategory $category;
    protected Service $service;
    protected ServicePackage $package;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'seller']);
        Role::firstOrCreate(['name' => 'client']);

        $this->usd = Currency::firstOrCreate(['currency' => 'USD'], [
            'symbol' => '$',
            'string_format' => '$ %s'
        ]);

        $this->seller = User::factory()->create([
            'user_balance' => 0,
            'currency_id' => $this->usd->id,
            'email_verified_at' => now(),
            'onboarding_completed' => true,
            'seller_availability' => 'available',
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
            'name' => 'Development & IT',
            'slug' => 'development-it',
            'description' => 'Web and App development'
        ]);

        $this->service = Service::create([
            'seller_id' => $this->seller->id,
            'category_id' => $this->category->id,
            'title' => 'Full Stack Web App Development',
            'description' => 'I will build your web application using Laravel and React.',
            'status' => 'active'
        ]);

        $this->package = ServicePackage::create([
            'service_id' => $this->service->id,
            'name' => 'Premium Web App',
            'description' => 'Complete web app with authentication and dashboard.',
            'price' => 500.00,
            'currency_id' => $this->usd->id,
            'delivery_days' => 5,
            'revisions' => 3
        ]);

        \Illuminate\Support\Facades\Gate::policy(
            ServiceOrder::class,
            \Modules\Marketplace\Policies\ServiceOrderPolicy::class
        );
        \Illuminate\Support\Facades\Gate::policy(
            Service::class,
            \Modules\Marketplace\Policies\ServicePolicy::class
        );
    }

    /**
     * Test 1: ServiceOrder hasOneThrough 'service' relationship.
     * Prevents RelationNotFoundException in MessagesController.
     */
    public function test_service_order_has_direct_service_relationship()
    {
        $order = ServiceOrder::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'package_id' => $this->package->id,
            'amount' => 500.00,
            'commission_amount' => 50.00,
            'currency_id' => $this->usd->id,
            'status' => ServiceOrderStatus::PENDING
        ]);

        $this->assertNotNull($order->service);
        $this->assertEquals($this->service->id, $order->service->id);
        $this->assertEquals('Full Stack Web App Development', $order->service->title);
    }

    /**
     * Test 2: Order Snapshot creation on purchase.
     */
    public function test_order_creation_captures_snapshot()
    {
        $response = $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.store'), [
                'package_id' => $this->package->id
            ]);

        $order = ServiceOrder::where('buyer_id', $this->buyer->id)->first();
        $this->assertNotNull($order);
        $this->assertNotNull($order->snapshot);

        $this->assertEquals('Full Stack Web App Development', $order->snapshot['service_title']);
        $this->assertEquals('Premium Web App', $order->snapshot['package_name']);
        $this->assertEquals(500.00, $order->snapshot['price']);
        $this->assertEquals(5, $order->snapshot['delivery_days']);
    }

    /**
     * Test 3: Status History logging on order creation.
     */
    public function test_order_creation_logs_status_history()
    {
        $this->actingAs($this->buyer)
            ->post(route('marketplace.orders.store'), [
                'package_id' => $this->package->id
            ]);

        $order = ServiceOrder::first();
        $this->assertCount(1, $order->statusHistories);

        $history = $order->statusHistories->first();
        $this->assertNull($history->old_status);
        $this->assertEquals(ServiceOrderStatus::PENDING->value, $history->new_status);
        $this->assertEquals($this->buyer->id, $history->changed_by);
    }

    /**
     * Test 4: Revision request increments revision_count and logs OrderRevision.
     */
    public function test_revision_request_increments_flag_and_creates_revision_record()
    {
        $this->actingAs($this->buyer)->post(route('marketplace.orders.store'), ['package_id' => $this->package->id]);
        $order = ServiceOrder::first();

        // Seller delivers work
        $deliverableService = resolve(\Modules\Marketplace\Services\DeliverableService::class);
        $deliverableService->submitDeliverable($order, 'First draft delivered');
        $order->refresh();

        $this->assertEquals(1, $order->delivery_count);

        // Buyer requests revision
        $deliverableService->requestRevision($order, 'Please change header color to dark slate.');
        $order->refresh();

        $this->assertEquals(ServiceOrderStatus::REVISION, $order->status);
        $this->assertEquals(1, $order->revision_count);

        $this->assertDatabaseHas('marketplace_order_revisions', [
            'order_id' => $order->id,
            'buyer_id' => $this->buyer->id,
            'message' => 'Please change header color to dark slate.',
            'status' => 'pending'
        ]);

        $this->assertCount(1, $order->revisions);
    }

    /**
     * Test 5: Unified Attachments morph relationship.
     */
    public function test_marketplace_attachments_morph_relationship()
    {
        $order = ServiceOrder::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'package_id' => $this->package->id,
            'amount' => 500.00,
            'commission_amount' => 50.00,
            'status' => ServiceOrderStatus::PENDING
        ]);

        $attachment = MarketplaceAttachment::create([
            'user_id' => $this->seller->id,
            'attachable_type' => ServiceOrder::class,
            'attachable_id' => $order->id,
            'file_name' => 'final_design.pdf',
            'file_path' => 'deliveries/final_design.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 102400
        ]);

        $this->assertCount(1, $order->attachments);
        $this->assertEquals('final_design.pdf', $order->attachments->first()->file_name);
        $this->assertInstanceOf(ServiceOrder::class, $attachment->attachable);
    }

    /**
     * Test 6: Custom Offers creation and relationships.
     */
    public function test_custom_offer_creation_and_attributes()
    {
        $offer = CustomOffer::create([
            'seller_id' => $this->seller->id,
            'buyer_id' => $this->buyer->id,
            'service_id' => $this->service->id,
            'package_id' => $this->package->id,
            'description' => 'Custom proposal for custom feature integration',
            'price' => 750.00,
            'delivery_days' => 7,
            'revisions' => 2,
            'status' => 'pending',
            'expires_at' => now('Africa/Cairo')->addDays(3)
        ]);

        $this->assertDatabaseHas('marketplace_custom_offers', [
            'id' => $offer->id,
            'seller_id' => $this->seller->id,
            'buyer_id' => $this->buyer->id,
            'price' => 750.00,
            'status' => 'pending'
        ]);

        $this->assertEquals('Full Stack Web App Development', $offer->service->title);
    }

    /**
     * Test 7: MessagesController Index Endpoint with morph relation.
     */
    public function test_messages_controller_index_loads_conversations_with_service_order()
    {
        $order = ServiceOrder::create([
            'buyer_id' => $this->buyer->id,
            'seller_id' => $this->seller->id,
            'package_id' => $this->package->id,
            'amount' => 500.00,
            'commission_amount' => 50.00,
            'currency_id' => $this->usd->id,
            'status' => ServiceOrderStatus::PENDING
        ]);

        $conversation = Conversation::create([
            'conversable_type' => ServiceOrder::class,
            'conversable_id' => $order->id,
            'type' => 'marketplace_order',
            'status' => 'open'
        ]);

        $conversation->participants()->createMany([
            ['user_id' => $this->buyer->id, 'role' => 'buyer'],
            ['user_id' => $this->seller->id, 'role' => 'seller'],
        ]);

        $response = $this->actingAs($this->buyer)
            ->get(route('messages.index', ['recipient_id' => $this->seller->id]));

        $response->assertStatus(200);
    }
}
