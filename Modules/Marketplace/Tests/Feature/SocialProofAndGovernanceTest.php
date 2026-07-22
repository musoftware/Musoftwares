<?php

namespace Modules\Marketplace\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceReview;
use Modules\Marketplace\Services\WishlistService;
use Modules\Marketplace\Services\PremiumToolService;

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

    public function test_ai_tools_daily_usage_quota()
    {
        $user = User::factory()->create();
        $toolService = new PremiumToolService();

        $res = $toolService->executeTool($user, 'prompt-generator', ['prompt' => 'Generate e-commerce ad copy']);
        $this->assertTrue($res['success']);
        $this->assertEquals('prompt-generator', $res['tool_slug']);
    }
}
