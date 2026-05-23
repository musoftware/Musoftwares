<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\ERP\Models\UserSubscription;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class RenewSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:renew';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically renew active subscriptions that have reached their expiration date';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting subscription auto-renewal check...');

        $now = Carbon::now();

        // 1. Find all active subscriptions that have expired or are expiring now, and have auto_renew set to true
        $expiringSubscriptions = UserSubscription::with(['client', 'plan'])
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->where('expires_at', '<=', $now)
            ->get();

        $this->info("Found {$expiringSubscriptions->count()} expiring subscriptions to process.");

        foreach ($expiringSubscriptions as $subscription) {
            $this->info("Processing subscription ID: {$subscription->id} for client: {$subscription->client->name} (Plan: {$subscription->plan->name})");

            try {
                $user = $subscription->client;
                $plan = $subscription->plan;
                // Check plan price
                $price = (float) $plan->price;

                if ($price <= 0) {
                    // Free plan - just renew without balance deduction
                    $this->renewSubscription($subscription, $plan);
                    $this->info("Subscription ID: {$subscription->id} renewed successfully (Free Plan).");
                    continue;
                }

                // Debit balance
                try {
                    if ((float) $user->available_balance() < $price) {
                        throw new Exception("Insufficient balance");
                    }
                    $user->add_balance(-1 * $price, 'Subscription Renewal: ' . $plan->name, 'used');

                    // If debit succeeded, renew subscription
                    $this->renewSubscription($subscription, $plan);
                    $this->info("Subscription ID: {$subscription->id} renewed successfully via balance debit of {$price} USD.");
                } catch (Exception $balanceException) {
                    // Insufficient funds or error
                    $this->warn("Failed to debit balance for Subscription ID: {$subscription->id}. Reason: " . $balanceException->getMessage());
                    
                    // Mark subscription as expired
                    $subscription->update([
                        'status' => 'expired'
                    ]);
                    $this->error("Subscription ID: {$subscription->id} has been marked as expired due to failed payment.");
                }
            } catch (Exception $e) {
                Log::error("Error processing subscription renewal for ID {$subscription->id}: " . $e->getMessage());
                $this->error("Error processing subscription ID: {$subscription->id}. See logs.");
            }
        }

        $this->info('Subscription auto-renewal check completed.');
    }

    /**
     * Helper to extend expires_at based on billing cycle.
     */
    protected function renewSubscription(UserSubscription $subscription, $plan): void
    {
        $newExpiresAt = $subscription->expires_at ? Carbon::parse($subscription->expires_at) : Carbon::now();

        if ($plan->billing === 'yearly') {
            $newExpiresAt->addYear();
        } else {
            $newExpiresAt->addMonth(); // default to monthly
        }

        $subscription->update([
            'status' => 'active',
            'expires_at' => $newExpiresAt,
        ]);
    }
}
