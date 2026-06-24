<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserSubscription;
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
        $expiringSubscriptions = UserSubscription::with(['user'])
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->where('expires_at', '<=', $now)
            ->get();

        $this->info("Found {$expiringSubscriptions->count()} expiring subscriptions to process.");

        $pricingService = app(\App\Services\PricingService::class);
        $serviceItems = $pricingService->getServiceItems();

        foreach ($expiringSubscriptions as $subscription) {
            $user = $subscription->user;
            if (!$user) {
                $this->warn("Subscription ID: {$subscription->id} has no associated user. Skipping.");
                continue;
            }

            $item = collect($serviceItems)->firstWhere('id', $subscription->object);
            $itemName = $item['name'] ?? ucfirst(str_replace('-', ' ', $subscription->object));

            $this->info("Processing subscription ID: {$subscription->id} for user: {$user->name} (Item: {$itemName})");

            try {
                // Check price
                $price = $item['monthly_price'] ?? 0;

                if ($price <= 0) {
                    // Free plan - just renew without balance deduction
                    $this->renewSubscription($subscription);
                    $this->info("Subscription ID: {$subscription->id} renewed successfully (Free Plan).");
                    continue;
                }

                // Debit balance
                try {
                    $userBalance = (float) $user->user_balance;
                    if ($userBalance < $price) {
                        // Calculate prorations if partial balance exists
                        if ($userBalance > 0 && $price > 0) {
                            $proratedDays = floor(($userBalance / $price) * 30);
                            if ($proratedDays >= 1) {
                                $proratedPrice = ($proratedDays / 30) * $price;
                                $user->add_balance(-1 * $proratedPrice, 'Prorated Subscription Renewal: ' . $itemName, 'used');
                                $this->renewSubscription($subscription, $proratedDays);
                                $this->info("Subscription ID: {$subscription->id} prorated renewed for {$proratedDays} days via balance debit of {$proratedPrice} USD.");
                                continue;
                            }
                        }
                        throw new Exception("Insufficient balance for even a 1-day proration.");
                    }
                    $user->add_balance(-1 * $price, 'Subscription Renewal: ' . $itemName, 'used');

                    // If debit succeeded, renew subscription
                    $this->renewSubscription($subscription);
                    $this->info("Subscription ID: {$subscription->id} renewed successfully via balance debit of {$price} USD.");
                } catch (Exception $balanceException) {
                    // Insufficient funds or error
                    $this->warn("Failed to debit balance for Subscription ID: {$subscription->id}. Reason: " . $balanceException->getMessage());
                    
                    // Mark subscription as expired
                    $subscription->update([
                        'status' => 'expired'
                    ]);



                    // Notify the user about the downgrade
                    $user->notify(new \App\Notifications\SubscriptionPaymentFailedNotification($itemName));

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
    protected function renewSubscription(UserSubscription $subscription, ?int $proratedDays = null): void
    {
        $newExpiresAt = $subscription->expires_at ? Carbon::parse($subscription->expires_at) : Carbon::now();

        if ($proratedDays !== null) {
            $newExpiresAt->addDays($proratedDays);
        } else {
            $newExpiresAt->addMonth(); // default to monthly
        }

        $subscription->update([
            'status' => 'active',
            'expires_at' => $newExpiresAt,
        ]);


    }
}
