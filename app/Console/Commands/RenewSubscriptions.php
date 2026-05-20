<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\ERP\Models\UserSubscription;
use Modules\Core\Services\WalletService;
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

    protected WalletService $walletService;

    /**
     * Create a new command instance.
     */
    public function __construct(WalletService $walletService)
    {
        parent::__construct();
        $this->walletService = $walletService;
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
                $wallet = $user->getWallet();

                // Check plan price
                $price = (float) $plan->price;

                if ($price <= 0) {
                    // Free plan - just renew without wallet transaction
                    $this->renewSubscription($subscription, $plan);
                    $this->info("Subscription ID: {$subscription->id} renewed successfully (Free Plan).");
                    continue;
                }

                // Debit wallet
                try {
                    $this->walletService->debitAvailable(
                        $wallet,
                        $price,
                        'USD', // Assuming plan pricing is in USD
                        'subscription_renewal',
                        (string) $subscription->id,
                        "Auto-renewal of subscription for plan: {$plan->name}"
                    );

                    // If debit succeeded, renew subscription
                    $this->renewSubscription($subscription, $plan);
                    $this->info("Subscription ID: {$subscription->id} renewed successfully via wallet debit of {$price} USD.");
                } catch (Exception $walletException) {
                    // Insufficient funds or wallet error
                    $this->warn("Failed to debit wallet for Subscription ID: {$subscription->id}. Reason: " . $walletException->getMessage());
                    
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
