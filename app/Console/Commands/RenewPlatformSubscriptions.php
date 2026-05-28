<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlatformSubscription;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RenewPlatformSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'platform-subscription:renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically renew active platform subscriptions that have reached their expiration date';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting platform subscription auto-renewal check...');

        $now = Carbon::now();

        // 1. Find all active platform subscriptions that have expired or are expiring today, and have auto_renew set to true
        $expiringSubscriptions = PlatformSubscription::with(['user', 'plan'])
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->where('expires_at', '<=', $now)
            ->get();

        $this->info("Found {$expiringSubscriptions->count()} expiring platform subscriptions to process.");

        foreach ($expiringSubscriptions as $subscription) {
            $this->info("Processing subscription ID: {$subscription->id} for user: {$subscription->user->name} (Plan: {$subscription->plan->plan_name})");

            try {
                $user = $subscription->user;
                $plan = $subscription->plan;
                
                // Check plan price
                $price = (float) $subscription->amount;

                if ($price <= 0 || $plan->plan_name === 'Trial') {
                    // Free plan or Trial - should not auto renew usually, but if it does:
                    $this->renewSubscription($subscription, $plan, $user, $price);
                    $this->info("Subscription ID: {$subscription->id} renewed successfully (Free Plan).");
                    continue;
                }

                // Debit balance
                try {
                    DB::transaction(function () use ($user, $price, $plan, $subscription) {
                        if ((float) $user->available_balance() < $price) {
                            throw new Exception("Insufficient balance");
                        }
                        
                        $user->add_balance(-1 * $price, 'Platform Subscription Renewal: ' . $plan->plan_name, 'used');
                        
                        $this->renewSubscription($subscription, $plan, $user, $price);
                    });

                    $this->info("Subscription ID: {$subscription->id} renewed successfully via balance debit of {$price} USD.");
                } catch (Exception $balanceException) {
                    $this->warn("Failed to debit balance for Subscription ID: {$subscription->id}. Reason: " . $balanceException->getMessage());
                    
                    // Mark subscription as expired
                    $subscription->update([
                        'status' => 'expired',
                        'auto_renew' => false
                    ]);

                    // Update legacy user fields
                    $user->update([
                        'subscription_force' => 0
                    ]);

                    $this->error("Subscription ID: {$subscription->id} has been marked as expired due to failed payment.");
                }
            } catch (Exception $e) {
                Log::error("Error processing platform subscription renewal for ID {$subscription->id}: " . $e->getMessage());
                $this->error("Error processing subscription ID: {$subscription->id}. See logs.");
            }
        }

        // 2. Also find all active subscriptions that do NOT have auto-renew, and mark them as expired if past date
        $nonRenewingSubscriptions = PlatformSubscription::with(['user'])
            ->where('status', 'active')
            ->where('auto_renew', false)
            ->where('expires_at', '<=', $now)
            ->get();

        $this->info("Found {$nonRenewingSubscriptions->count()} non-renewing platform subscriptions to expire.");

        foreach ($nonRenewingSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);
            // Legacy sync
            if ($subscription->user) {
                $subscription->user->update(['subscription_force' => 0]);
            }
            $this->info("Subscription ID: {$subscription->id} expired naturally.");
        }

        $this->info('Platform subscription auto-renewal check completed.');
    }

    /**
     * Helper to extend expires_at based on billing cycle.
     */
    protected function renewSubscription(PlatformSubscription $subscription, $plan, User $user, float $price): void
    {
        $newExpiresAt = $subscription->expires_at ? Carbon::parse($subscription->expires_at) : Carbon::now();
        
        $daysToAdd = $plan->plan_duration > 0 ? $plan->plan_duration : 365;

        // Take billing cycle into account
        if ($subscription->billing_cycle === '3_months') {
            $daysToAdd = 90;
        } elseif ($subscription->billing_cycle === '6_months') {
            $daysToAdd = 180;
        } elseif ($subscription->billing_cycle === '1_year' || $subscription->billing_cycle === 'yearly') {
            $daysToAdd = 365;
        } elseif ($subscription->billing_cycle === '3_years') {
            $daysToAdd = 365 * 3;
        }

        $newExpiresAt->addDays($daysToAdd);

        $subscription->update([
            'status' => 'active',
            'expires_at' => $newExpiresAt,
        ]);

        // Legacy user update
        $user->update([
            'subscription_date' => $newExpiresAt->format('Y-m-d'),
            'subscription_plan' => $plan->plan_name,
            'subscription_force' => 1,
        ]);
    }
}
