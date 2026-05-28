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
    protected ModulePlan $freePlan;
    protected ModulePlan $paidPlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles & permissions
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

        // Create standard client user
        $this->user = User::factory()->create(['onboarding_completed' => true]);
        $this->user->assignRole('client');

        // Create a Free Plan
        $this->freePlan = ModulePlan::create([
            'module' => 'marketplace',
            'name' => 'Marketplace Free',
            'price' => 0.00,
            'billing' => 'monthly',
            'features' => [],
            'is_active' => true,
        ]);

        // Create a Paid Plan
        $this->paidPlan = ModulePlan::create([
            'module' => 'booking',
            'name' => 'Booking Premium',
            'price' => 49.00,
            'billing' => 'monthly',
            'features' => [],
            'is_active' => true,
        ]);
    }

    public function test_free_subscription_auto_renew(): void
    {
        $expiryTime = Carbon::now()->subMinutes(10);

        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->freePlan->id,
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
        // Expiry should be extended by exactly 1 month from the original expiry date
        $expectedNewExpiry = Carbon::parse($expiryTime)->addMonth()->toDateTimeString();
        $this->assertEquals($expectedNewExpiry, $subscription->expires_at->toDateTimeString());
    }

    public function test_paid_subscription_auto_renew_with_sufficient_balance(): void
    {
        // Setup morphOne wallet and credit it with 100 USD
        $wallet = $this->user->getWallet();
        $wallet->update(['balance' => 100.00]);

        $expiryTime = Carbon::now()->subMinutes(5);

        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->paidPlan->id,
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

        // Wallet balance should be debited by 49.00 USD
        $this->assertEquals(51.00, (float) $wallet->fresh()->balance);

        // Double-entry ledger validation: check journal entries
        $this->assertDatabaseHas('journal_entries', [
            'reference_type' => 'subscription_renewal',
            'reference_id' => (string) $subscription->id,
        ]);

        // Double-entry ledger validation: check wallet transaction
        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'debit',
            'amount' => 49.00,
            'reference_type' => 'subscription_renewal',
            'reference_id' => (string) $subscription->id,
        ]);
    }

    public function test_paid_subscription_auto_renew_fails_with_insufficient_balance(): void
    {
        // Setup morphOne wallet and credit it with only 10 USD (plan is 49 USD)
        $wallet = $this->user->getWallet();
        $wallet->update(['balance' => 10.00]);

        $expiryTime = Carbon::now()->subMinutes(5);

        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->paidPlan->id,
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

        // Wallet balance should remain 10 USD
        $this->assertEquals(10.00, (float) $wallet->fresh()->balance);
    }

    public function test_does_not_renew_if_auto_renew_is_false(): void
    {
        $expiryTime = Carbon::now()->subMinutes(5);

        $subscription = UserSubscription::create([
            'user_id' => $this->user->id,
            'plan_id' => $this->freePlan->id,
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
            'plan_id' => $this->freePlan->id,
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
