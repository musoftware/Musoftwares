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

        // Give the user a "gold-saver" subscription (which is the actual DB object)
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
        
        // Assert that the active gold-saver subscription is returned
        $this->assertEquals('active', $data[$user->id]['gold-saver']['status'] ?? 'cancelled', 'Failed: returns cancelled because it looks for goldsaversys% instead of gold-saver%');
    }
}
