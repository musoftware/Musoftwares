<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class TrialSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Seed roles & permissions if required
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
    }

    public function test_user_can_subscribe_to_trial_plan()
    {
        // 1. Create a User with 0 balance
        $user = User::factory()->create([
            'user_balance' => 0,
        ]);
        
        $this->actingAs($user);

        // 2. Ensure Trial Plan exists
        $trialPlan = Plan::updateOrCreate(
            ['plan_name' => 'Trial'],
            ['plan_price' => 0, 'plan_duration' => 1, 'plan_status' => true, 'plan_currency' => 1]
        );

        // 3. Post to subscribe route
        $response = $this->post(route('subscriptions.subscribe'), [
            'plan_id' => $trialPlan->id,
            'billing_cycle' => '1_year', // billing_cycle gets ignored for Trial
        ]);

        // 4. Assert Redirect and Success Message
        $response->assertRedirect(route('subscriptions.manage'));
        $response->assertSessionHas('success', "Subscribed to {$trialPlan->plan_name} successfully!");

        // 5. Assert User model is updated correctly
        $user->refresh();
        $this->assertEquals('Trial', $user->subscription_plan);
        $this->assertEquals($trialPlan->id, $user->plan_id);
        $this->assertEquals(1, $user->subscription_force);

        $expectedDate = Carbon::now()->addDays(1)->format('Y-m-d');
        $this->assertEquals($expectedDate, $user->subscription_date);
    }
}
