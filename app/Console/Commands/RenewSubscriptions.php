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
                    if ((float) $user->user_balance < $price) {
                        throw new Exception("Insufficient balance");
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

                    // Downgrade tenant access gracefully
                    $tenant = \Modules\ERP\Models\Tenant::where('user_id', $user->id)->first();
                    if ($tenant) {
                        \App\Models\TenantFeature::where('tenant_id', $tenant->id)
                            ->where('feature_key', $subscription->object)
                            ->update(['expires_at' => now()->subMinute()]);
                    }

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
    protected function renewSubscription(UserSubscription $subscription): void
    {
        $newExpiresAt = $subscription->expires_at ? Carbon::parse($subscription->expires_at) : Carbon::now();

        $newExpiresAt->addMonth(); // default to monthly

        $subscription->update([
            'status' => 'active',
            'expires_at' => $newExpiresAt,
        ]);

        $tenant = \Modules\ERP\Models\Tenant::where('user_id', $subscription->user_id)->first();
        if ($tenant) {
            \App\Models\TenantFeature::updateOrCreate(
                ['tenant_id' => $tenant->id, 'feature_key' => $subscription->object],
                [
                    'module' => str_starts_with($subscription->object, 'crm') ? 'crm' : (str_starts_with($subscription->object, 'erp') ? 'erp' : (str_starts_with($subscription->object, 'tool') ? 'tools' : 'booking')),
                    'expires_at' => $newExpiresAt
                ]
            );
        }
    }
}
