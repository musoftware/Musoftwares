<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserSubscription;
use App\Notifications\SubscriptionPaymentFailedNotification;
use App\Services\PricingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RenewSubscriptions extends Command
{
    private const PRORATION_CYCLE_DAYS = 30;

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

    private PricingService $pricingService;

    /**
     * Execute the console command.
     */
    public function handle(PricingService $pricingService): int
    {
        $this->pricingService = $pricingService;
        $this->info('Starting subscription auto-renewal check...');

        $expiringSubscriptions = UserSubscription::with(['user'])
            ->where('status', 'active')
            ->where('auto_renew', true)
            ->where('expires_at', '<=', Carbon::now())
            ->get();

        $this->info("Found {$expiringSubscriptions->count()} expiring subscriptions to process.");

        $serviceItems = collect($pricingService->getServiceItems())->keyBy('id');

        foreach ($expiringSubscriptions as $subscription) {
            $this->processSubscription($subscription, $serviceItems->get($subscription->object));
        }

        $this->info('Subscription auto-renewal check completed.');

        return Command::SUCCESS;
    }

    /**
     * Charge and renew one subscription. The debit and the renewal share one
     * transaction: if the renewal throws, the user is not charged and the
     * subscription is left as is so the next run can retry.
     */
    private function processSubscription(UserSubscription $subscription, ?array $item): void
    {
        $user = $subscription->user;
        if (! $user) {
            $this->warn("Subscription ID: {$subscription->id} has no associated user. Skipping.");

            return;
        }

        $itemName = $item['name'] ?? ucfirst(str_replace('-', ' ', $subscription->object));
        $this->info("Processing subscription ID: {$subscription->id} for user: {$user->name} (Item: {$itemName})");

        try {
            $result = DB::transaction(fn () => $this->chargeAndRenew($subscription->id, $user->id, $item, $itemName));
        } catch (Throwable $e) {
            Log::error('RenewSubscriptions: renewal failed, nothing was charged', [
                'subscription_id' => $subscription->id,
                'user_id' => $user->id,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
            $this->error("Error processing subscription ID: {$subscription->id}. See logs.");

            return;
        }

        if ($result === null) {
            $this->expireForFailedPayment($subscription, $user, $itemName);

            return;
        }

        $this->info("Subscription ID: {$subscription->id} {$result}");
    }

    /**
     * Must run inside a transaction. Locks the subscription and the wallet,
     * converts the base (EGP) price into the wallet currency, then debits and renews.
     *
     * @return string|null Summary of what was done, or null when the wallet cannot pay even one day.
     */
    private function chargeAndRenew(int $subscriptionId, int $userId, ?array $item, string $itemName): ?string
    {
        $subscription = UserSubscription::whereKey($subscriptionId)->lockForUpdate()->firstOrFail();
        if ($subscription->status !== 'active' || Carbon::parse($subscription->expires_at)->isFuture()) {
            return 'was already renewed by another run.';
        }

        $basePrice = (float) ($item['monthly_price'] ?? 0);
        if ($basePrice <= 0) {
            $this->renewSubscription($subscription);

            return 'renewed successfully (Free Plan).';
        }

        $user = User::whereKey($userId)->lockForUpdate()->firstOrFail();
        $price = $this->pricingService->convertBasePrice($basePrice, (int) $user->currency_id);

        $currency = $user->currency_name();
        $balance = round((float) $user->user_balance, 2);
        if ($balance >= $price) {
            $user->add_balance(-1 * $price, 'Subscription Renewal: '.$itemName, 'used');
            $this->renewSubscription($subscription);

            return "renewed successfully via balance debit of {$price} {$currency}.";
        }

        $proratedDays = $balance > 0 ? (int) floor(($balance / $price) * self::PRORATION_CYCLE_DAYS) : 0;
        if ($proratedDays < 1) {
            return null;
        }

        $proratedPrice = round(($proratedDays / self::PRORATION_CYCLE_DAYS) * $price, 2);
        $user->add_balance(-1 * $proratedPrice, 'Prorated Subscription Renewal: '.$itemName, 'used');
        $this->renewSubscription($subscription, $proratedDays);

        return "prorated renewed for {$proratedDays} days via balance debit of {$proratedPrice} {$currency}.";
    }

    private function expireForFailedPayment(UserSubscription $subscription, User $user, string $itemName): void
    {
        $this->warn("Failed to debit balance for Subscription ID: {$subscription->id}. Reason: Insufficient balance for even a 1-day proration.");

        $subscription->update(['status' => 'expired']);
        $user->notify(new SubscriptionPaymentFailedNotification($itemName));

        $this->error("Subscription ID: {$subscription->id} has been marked as expired due to failed payment.");
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
