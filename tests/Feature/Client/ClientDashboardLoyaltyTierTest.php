<?php

namespace Tests\Feature\Client;

use App\Models\Currency;
use App\Models\LoyaltyTier;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClientDashboardLoyaltyTierTest extends TestCase
{
    use RefreshDatabase;

    protected Currency $currency;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->currency = Currency::firstOrCreate(
            ['currency' => 'USD'],
            ['name' => 'US Dollar', 'symbol' => '$', 'string_format' => '%s%.2f']
        );
    }

    public function test_user_tier_accessor_returns_silver(): void
    {
        $silverTier = LoyaltyTier::firstOrCreate(
            ['slug' => 'silver'],
            [
                'name' => 'Silver',
                'min_lifetime_points' => 200,
                'order_index' => 2,
                'is_active' => true,
            ]
        );

        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'loyalty_tier_id' => $silverTier->id,
            'loyalty_points_balance' => 200,
            'loyalty_lifetime_points' => 200,
        ]);

        $this->assertEquals($silverTier->id, $user->loyalty_tier_id);
        $this->assertEquals('silver', $user->tier);
    }

    public function test_client_with_silver_tier_receives_silver_user_tier_on_dashboard(): void
    {
        // 1. Ensure Silver tier exists
        $silverTier = LoyaltyTier::firstOrCreate(
            ['slug' => 'silver'],
            [
                'name' => 'Silver',
                'min_lifetime_points' => 200,
                'discount_percentage' => 5.00,
                'order_index' => 2,
                'is_active' => true,
            ]
        );

        // 2. Create user who has Silver tier and 200 points
        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'tier' => 'standard', // Legacy column
            'loyalty_tier_id' => $silverTier->id,
            'onboarding_completed' => true,
        ]);
        $user->updateQuietly([
            'loyalty_points_balance' => 200,
            'loyalty_lifetime_points' => 200,
        ]);
        $user->assignRole('client');

        // 3. Request /dashboard
        $response = $this->actingAs($user, 'web')->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Dashboard')
            ->where('userTier', 'silver')
            ->where('userLoyaltyPoints', 200)
        );
    }

    public function test_client_with_points_qualifying_for_silver_is_synced_and_shown_as_silver(): void
    {
        // 1. Ensure Bronze and Silver tiers exist
        LoyaltyTier::firstOrCreate(
            ['slug' => 'bronze'],
            [
                'name' => 'Bronze',
                'min_lifetime_points' => 0,
                'order_index' => 1,
                'is_active' => true,
            ]
        );

        LoyaltyTier::firstOrCreate(
            ['slug' => 'silver'],
            [
                'name' => 'Silver',
                'min_lifetime_points' => 200,
                'discount_percentage' => 5.00,
                'order_index' => 2,
                'is_active' => true,
            ]
        );

        // 2. Create user with 200 points but loyalty_tier_id not yet updated (or null)
        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'tier' => 'standard',
            'loyalty_tier_id' => null,
            'loyalty_points_balance' => 200,
            'loyalty_lifetime_points' => 200,
            'onboarding_completed' => true,
        ]);
        $user->assignRole('client');

        // 3. Request /dashboard
        $response = $this->actingAs($user, 'web')->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Client/Dashboard')
            ->where('userTier', 'silver')
        );
    }

    public function test_sync_loyalty_tiers_command_updates_stale_tiers(): void
    {
        $silverTier = LoyaltyTier::firstOrCreate(
            ['slug' => 'silver'],
            [
                'name' => 'Silver',
                'min_lifetime_points' => 200,
                'order_index' => 2,
                'is_active' => true,
            ]
        );

        $user = User::factory()->create([
            'currency_id' => $this->currency->id,
            'tier' => 'standard',
            'loyalty_tier_id' => null,
            'loyalty_points_balance' => 200,
            'loyalty_lifetime_points' => 200,
        ]);

        $exitCode = $this->artisan('loyalty:sync-tiers', ['--user' => $user->id]);
        $this->assertEquals(0, $exitCode);

        $this->assertEquals($silverTier->id, $user->fresh()->loyalty_tier_id);
        $this->assertEquals('silver', $user->fresh()->getRawOriginal('tier'));
    }
}
