<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_onboarding_screen_can_be_rendered(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/onboarding');

        $response->assertStatus(200);
    }

    public function test_users_with_onboarding_completed_are_redirected_to_dashboard(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => true,
        ]);

        $response = $this->actingAs($user)->get('/onboarding');

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_user_can_autosave_onboarding_progress(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->post('/onboarding', [
            'action' => 'autosave',
            'step' => 1,
            'country' => 'United States',
            'city' => 'New York',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $user->refresh();
        $this->assertEquals('United States', $user->country);
        $this->assertEquals('New York', $user->city);
        $this->assertFalse((bool) $user->onboarding_completed);
    }

    public function test_user_can_complete_onboarding(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->post('/onboarding', [
            'action' => 'complete',
            'step' => 2,
            'country' => 'United States',
            'city' => 'New York',
            'mobile_1' => '+15550000000',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();
        $this->assertTrue((bool) $user->onboarding_completed);
        $this->assertEquals('United States', $user->country);
        $this->assertEquals('New York', $user->city);
        $this->assertEquals('+15550000000', $user->mobile_1);
    }

    public function test_user_cannot_access_dashboard_if_onboarding_not_completed(): void
    {
        $user = User::factory()->create([
            'onboarding_completed' => false,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/onboarding');
    }
}
