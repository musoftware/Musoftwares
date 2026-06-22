<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UserSubscriptionDatetimeOverflowTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that extreme datetime values (beyond 2038) can be stored in the expires_at column 
     * of the user_subscriptions table, ensuring the DATETIME column migration is working correctly.
     */
    public function test_user_subscription_can_store_extreme_dates()
    {
        // Add required roles and permissions first
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);
        $this->seed(\Database\Seeders\CurrenciesSeeder::class);

        // Create a dummy user
        $user = \App\Models\User::factory()->create();

        // Target an extreme date (Year 2108)
        $extremeDate = '2108-08-11 17:53:49';

        // Insert directly into user_subscriptions to avoid complex service logic requirements
        $subscriptionId = DB::table('user_subscriptions')->insertGetId([
            'user_id' => $user->id,
            'object' => '{"module": "erp", "plan": "lifetime"}',
            'status' => 'active',
            'started_at' => now(),
            'expires_at' => $extremeDate,
            'auto_renew' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('user_subscriptions', [
            'id' => $subscriptionId,
            'expires_at' => $extremeDate,
        ]);
        
        $retrieved = DB::table('user_subscriptions')->where('id', $subscriptionId)->first();
        $this->assertEquals($extremeDate, $retrieved->expires_at, "The extreme date should be retrieved correctly without overflow errors.");
    }
}
