<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Services\DeliverableService;
use Modules\Marketplace\Services\OrderCollaborationService;
use Modules\Marketplace\Services\EscrowService;

class OrderFulfillmentAndWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deliverable_submission_and_revision_workflow()
    {
        $buyer = User::factory()->create(['user_balance' => 500]);
        $seller = User::factory()->create(['user_balance' => 0]);

        $category = ServiceCategory::create(['name' => 'Writing', 'slug' => 'writing']);
        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'Article Writing',
            'category_id' => $category->id,
            'description' => '1000 word article',
            'status' => 'active',
        ]);

        $package = ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Standard Article',
            'description' => 'Article',
            'price' => 100,
            'currency_id' => 1,
            'delivery_days' => 2,
        ]);

        $order = ServiceOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'amount' => 100,
            'commission_amount' => 10,
            'currency_id' => 1,
            'status' => 'pending',
        ]);

        // Seller submits deliverable
        $deliverableService = new DeliverableService();
        $order = $deliverableService->submitDeliverable($order, 'Here is your article delivery.', 'deliveries/article.docx');

        $this->assertEquals('delivered', $order->status->value);
        $this->assertNotNull($order->delivered_at);
        $this->assertNotNull($order->auto_complete_at);

        // Buyer requests revision
        $order = $deliverableService->requestRevision($order, 'Please revise paragraph 2.');
        $this->assertEquals('processing', $order->status->value);
    }

    public function test_workspace_direct_chat_messaging_and_read_tracking()
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $category = ServiceCategory::create(['name' => 'Video', 'slug' => 'video']);
        $service = Service::create(['seller_id' => $seller->id, 'title' => 'Video Editing', 'category_id' => $category->id, 'description' => 'Video editing', 'status' => 'active']);
        $package = ServicePackage::create(['service_id' => $service->id, 'name' => 'Short Video', 'description' => 'Video', 'price' => 150, 'currency_id' => 1, 'delivery_days' => 3]);

        $order = ServiceOrder::create(['buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'package_id' => $package->id, 'amount' => 150, 'commission_amount' => 15, 'currency_id' => 1, 'status' => 'pending']);

        $collabService = new OrderCollaborationService();

        // Send text message
        $activity = $collabService->sendMessage($order, $buyer, 'text', 'Hello, looking forward to starting!');
        $this->assertEquals('text', $activity->activity_type);

        // Mark as read
        $readState = $collabService->markAsRead($activity->id, $seller);
        $this->assertEquals(1, $readState->read);
    }
}

