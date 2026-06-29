<?php

namespace Tests\Feature\Subscriptions;

use App\Models\User;
use App\Models\UserSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionSyncApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_subscription_for_goldsaversys_when_user_has_gold_saver_subscription()
    {
        $user = User::factory()->create();

        // Create an old "cancelled" subscription to simulate multiple subscriptions
        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'object' => 'gold-saver',
            'status' => 'cancelled',
            'expires_at' => now()->subYear(),
        ]);

        // Give the user an "active" gold-saver subscription
        UserSubscription::factory()->create([
            'user_id' => $user->id,
            'object' => 'gold-saver',
            'status' => 'active',
            'expires_at' => now()->addYear(),
        ]);

        // The GoldSaverSys module will request sync using module = 'goldsaversys'
        $response = $this->postJson('/api/sso/subscriptions/sync', [
            'module' => 'goldsaversys',
            'user_ids' => [$user->id],
        ]);

        $response->assertStatus(200);

        $data = $response->json('data');
        
        $this->assertArrayHasKey($user->id, $data);
        
        // Assert that the active gold-saver subscription is prioritized and returned
        $this->assertEquals('active', $data[$user->id]['gold-saver']['status'] ?? 'cancelled', 'Failed: expected the active subscription to be prioritized over the cancelled one.');
    }
}
