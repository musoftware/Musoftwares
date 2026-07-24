<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceReview;
use Modules\Marketplace\Services\WishlistService;

class SocialProofAndGovernanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_ratings_and_reviews_submission_and_moderation()
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $category = ServiceCategory::create(['name' => 'Audio', 'slug' => 'audio']);
        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'Voiceover Recording',
            'category_id' => $category->id,
            'description' => 'Professional Arabic voiceover',
            'status' => 'active',
        ]);

        $package = \Modules\Marketplace\Models\ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Package',
            'description' => 'Desc',
            'price' => 100,
            'currency_id' => 1,
            'delivery_days' => 1,
        ]);

        $order = \Modules\Marketplace\Models\ServiceOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'amount' => 100,
            'commission_amount' => 10,
            'currency_id' => 1,
            'status' => 'completed',
        ]);

        $review = ServiceReview::create([
            'service_id' => $service->id,
            'reviewer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'order_id' => $order->id,
            'rating' => 5,
            'review' => 'Amazing quality and fast response!',
            'is_public' => false,
        ]);


        $this->assertFalse((bool)$review->is_public);

        // Admin publishes review
        $review->update(['is_public' => true]);
        $this->assertTrue((bool)$review->fresh()->is_public);
    }

    public function test_cannot_review_uncompleted_order()
    {
        $this->withoutMiddleware();

        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $category = ServiceCategory::create(['name' => 'Audio 2', 'slug' => 'audio-2']);
        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'Mixing Service',
            'category_id' => $category->id,
            'description' => 'Mixing',
            'status' => 'active',
        ]);

        $package = \Modules\Marketplace\Models\ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Basic',
            'description' => 'Desc',
            'price' => 50,
            'currency_id' => 1,
            'delivery_days' => 1,
        ]);

        $order = \Modules\Marketplace\Models\ServiceOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'amount' => 50,
            'commission_amount' => 5,
            'currency_id' => 1,
            'status' => \Modules\Marketplace\Enums\ServiceOrderStatus::PENDING,
        ]);

        $this->actingAs($buyer);
        $response = $this->post("/marketplace/orders/{$order->id}/review", [
            'rating' => 5,
            'review' => 'Great work!',
        ]);

        $this->assertEquals(0, ServiceReview::count());
    }

    public function test_cannot_submit_duplicate_review_for_same_order()
    {
        $buyer = User::factory()->create();
        $seller = User::factory()->create();

        $category = ServiceCategory::create(['name' => 'Audio 3', 'slug' => 'audio-3']);
        $service = Service::create([
            'seller_id' => $seller->id,
            'title' => 'Mastering',
            'category_id' => $category->id,
            'description' => 'Mastering',
            'status' => 'active',
        ]);

        $package = \Modules\Marketplace\Models\ServicePackage::create([
            'service_id' => $service->id,
            'name' => 'Pro',
            'description' => 'Desc',
            'price' => 100,
            'currency_id' => 1,
            'delivery_days' => 1,
        ]);

        $order = \Modules\Marketplace\Models\ServiceOrder::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'package_id' => $package->id,
            'amount' => 100,
            'commission_amount' => 10,
            'currency_id' => 1,
            'status' => \Modules\Marketplace\Enums\ServiceOrderStatus::COMPLETED,
        ]);

        // Create first review
        ServiceReview::create([
            'service_id' => $service->id,
            'order_id' => $order->id,
            'reviewer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'rating' => 5,
            'review' => 'First review',
            'is_public' => true,
        ]);

        $this->assertEquals(1, ServiceReview::count());

        // Second review attempt for same order and reviewer should fail unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        ServiceReview::create([
            'service_id' => $service->id,
            'order_id' => $order->id,
            'reviewer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'rating' => 1,
            'review' => 'Duplicate attempt',
            'is_public' => true,
        ]);
    }

    public function test_wishlist_favorites_toggling()
    {
        $buyer = User::factory()->create();
        $category = ServiceCategory::create(['name' => 'Design', 'slug' => 'design-fav']);
        $service = Service::create(['seller_id' => User::factory()->create()->id, 'title' => 'Banner Design', 'category_id' => $category->id, 'description' => 'Banner', 'status' => 'active']);

        $wishlistService = new WishlistService();

        // 1. Toggle ON
        $added = $wishlistService->toggleFavorite($buyer, $service);
        $this->assertTrue($added);

        // 2. Toggle OFF
        $removed = $wishlistService->toggleFavorite($buyer, $service);
        $this->assertFalse($removed);
    }
}
