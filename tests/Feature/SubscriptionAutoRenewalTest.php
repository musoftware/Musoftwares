<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use App\Models\ModulePlan;
use App\Models\UserSubscription;
use Tests\TestCase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionAutoRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create standard client user
        $this->user = User::factory()->create(['onboarding_completed' => true, 'currency_id' => 1]);
        $this->user->assignRole('client');
    }

    public function test_free_subscription_auto_renew(): void
    {
        $expiryTime = Carbon::now()->subMinutes(10);

        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'marketplace',
            'status' => 'active',
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => $expiryTime,
            'auto_renew' => true,
        ]);
        
        // Since marketplace is not in saas config, it defaults to price 0

        // Run auto-renewal artisan command
        $exitCode = Artisan::call('subscription:renew');

        $this->assertEquals(0, $exitCode);

        // Fetch fresh subscription details
        $subscription = $subscription->fresh();

        $this->assertEquals('active', $subscription->status);
        // Expiry should be extended by exactly 1 month from the original expiry date
        $expectedNewExpiry = Carbon::parse($expiryTime)->addMonth()->toDateTimeString();
        $this->assertEquals($expectedNewExpiry, $subscription->expires_at->toDateTimeString());
    }

    public function test_paid_subscription_auto_renew_with_sufficient_balance(): void
    {
        $pricingService = app(\App\Services\PricingService::class);
        $serviceItems = $pricingService->getServiceItems();
        $item = collect($serviceItems)->firstWhere('id', 'erp');
        $expectedPrice = $item['monthly_price'] ?? 499.99;

        $this->user->user_balance = $expectedPrice + 100.00;
        $this->user->save();

        $expiryTime = Carbon::now()->subMinutes(5);

        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => $expiryTime,
            'auto_renew' => true,
        ]);

        // Run auto-renewal artisan command
        $exitCode = Artisan::call('subscription:renew');

        $this->assertEquals(0, $exitCode);

        // Fetch fresh subscription details
        $subscription = $subscription->fresh();

        $this->assertEquals('active', $subscription->status);
        
        $expectedNewExpiry = Carbon::parse($expiryTime)->addMonth()->toDateTimeString();
        $this->assertEquals($expectedNewExpiry, $subscription->expires_at->toDateTimeString());

        // Wallet balance should be debited by expectedPrice
        $this->assertEquals(100.00, round((float) $this->user->fresh()->user_balance, 2));

        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->user->id,
            'type' => 'used',
            'amount' => -1 * $expectedPrice,
        ]);
    }

    public function test_paid_subscription_auto_renew_fails_with_insufficient_balance(): void
    {
        $pricingService = app(\App\Services\PricingService::class);
        $serviceItems = $pricingService->getServiceItems();
        $item = collect($serviceItems)->firstWhere('id', 'erp');
        $expectedPrice = $item['monthly_price'] ?? 499.99;

        // Give less than required for even 1-day proration (price/30)
        $this->user->user_balance = ($expectedPrice / 30) - 1.00;
        if ($this->user->user_balance < 0) {
            $this->user->user_balance = 0;
        }
        $initialBalance = $this->user->user_balance;
        $this->user->save();

        $expiryTime = Carbon::now()->subMinutes(5);

        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'erp',
            'status' => 'active',
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => $expiryTime,
            'auto_renew' => true,
        ]);

        // Run auto-renewal artisan command
        $exitCode = Artisan::call('subscription:renew');

        $this->assertEquals(0, $exitCode);

        // Fetch fresh subscription details
        $subscription = $subscription->fresh();

        // Subscription should mark as expired
        $this->assertEquals('expired', $subscription->status);
        // Expiry should NOT be extended
        $this->assertEquals($expiryTime->toDateTimeString(), $subscription->expires_at->toDateTimeString());

        // Wallet balance should remain unchanged
        $this->assertEquals(round($initialBalance, 2), round((float) $this->user->fresh()->user_balance, 2));
    }

    public function test_does_not_renew_if_auto_renew_is_false(): void
    {
        $expiryTime = Carbon::now()->subMinutes(5);

        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'marketplace',
            'status' => 'active',
            'started_at' => Carbon::now()->subMonth(),
            'expires_at' => $expiryTime,
            'auto_renew' => false, // auto renew disabled
        ]);

        // Run auto-renewal artisan command
        $exitCode = Artisan::call('subscription:renew');

        $this->assertEquals(0, $exitCode);

        // Expiry & status should remain unchanged
        $subscription = $subscription->fresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals($expiryTime->toDateTimeString(), $subscription->expires_at->toDateTimeString());
    }

    public function test_does_not_renew_if_not_expired_yet(): void
    {
        $expiryTime = Carbon::now()->addDays(5); // future expiry

        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'object' => 'marketplace',
            'status' => 'active',
            'started_at' => Carbon::now(),
            'expires_at' => $expiryTime,
            'auto_renew' => true,
        ]);

        // Run auto-renewal artisan command
        $exitCode = Artisan::call('subscription:renew');

        $this->assertEquals(0, $exitCode);

        // Expiry & status should remain unchanged
        $subscription = $subscription->fresh();
        $this->assertEquals('active', $subscription->status);
        $this->assertEquals($expiryTime->toDateTimeString(), $subscription->expires_at->toDateTimeString());
    }
}

