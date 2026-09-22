<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class SyncUserLoyaltyTiersCommand extends Command
{
    protected $signature = 'loyalty:sync-tiers {--user= : Optional specific user ID to sync}';

    protected $description = 'Synchronize loyalty tiers and persistent tier columns for users based on lifetime points';

    public function handle(LoyaltyService $loyaltyService): int
    {
        $userId = $this->option('user');

        $query = User::query();
        if ($userId) {
            $query->where('id', $userId);
        } else {
            $query->where(function ($q) {
                $q->where('loyalty_points_balance', '>', 0)
                  ->orWhere('loyalty_lifetime_points', '>', 0)
                  ->orWhereNull('loyalty_tier_id')
                  ->orWhere('tier', 'standard');
            });
        }

        $count = $query->count();
        $this->info("Found {$count} user(s) to evaluate for loyalty tier synchronization.");

        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $updated = 0;

        $query->chunkById(100, function ($users) use ($loyaltyService, $bar, &$updated) {
            foreach ($users as $user) {
                $prevTierId = $user->loyalty_tier_id;
                $prevTierSlug = $user->tier;

                $loyaltyService->syncLoyaltyTier($user);
                $user->refresh();

                if ($user->loyalty_tier_id !== $prevTierId || $user->tier !== $prevTierSlug) {
                    $updated++;
                }

                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();
        $this->info("Successfully synchronized loyalty tiers. {$updated} user(s) updated.");

        return self::SUCCESS;
    }
}
