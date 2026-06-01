<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleSubscriptionSecurityTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        $this->user = User::factory()->create([
            'role' => 'client',
            'onboarding_completed' => true,
        ]);
    }

    public function test_marketplace_blocked_without_subscription(): void
    {
        // Mock SubscriptionService to return false for hasActiveSubscription
        $this->mock(SubscriptionService::class, function ($mock) {
            $mock->shouldReceive('hasActiveSubscription')
                ->with(\Mockery::any(), 'marketplace')
                ->andReturn(false);
        });

        $response = $this->actingAs($this->user)->get('/marketplace/dashboard');

        $response->assertRedirect(route('subscriptions.plans', ['module' => 'marketplace']));
    }

    public function test_booking_blocked_without_subscription(): void
    {
        // Mock SubscriptionService to return false for hasActiveSubscription
        $this->mock(SubscriptionService::class, function ($mock) {
            $mock->shouldReceive('hasActiveSubscription')
                ->with(\Mockery::any(), 'booking')
                ->andReturn(false);
        });

        $response = $this->actingAs($this->user)->get('/booking');

        $response->assertRedirect(route('subscriptions.plans', ['module' => 'booking']));
    }

    public function test_intelligence_blocked_without_subscription(): void
    {
        // Mock SubscriptionService to return false for hasActiveSubscription
        $this->mock(SubscriptionService::class, function ($mock) {
            $mock->shouldReceive('hasActiveSubscription')
                ->with(\Mockery::any(), 'intelligence')
                ->andReturn(false);
        });

        $response = $this->actingAs($this->user)->get('/intelligence');

        $response->assertRedirect(route('subscriptions.plans', ['module' => 'intelligence']));
    }
}
