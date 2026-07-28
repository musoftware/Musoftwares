<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\WebsiteService;
use Modules\Marketplace\Models\Service;
use App\Notifications\DailyNewServicesDigestNotification;
use Illuminate\Console\Command;

class SendDailyNewServicesDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'services:send-daily-digest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily new services email and FCM digest to all users at the end of the day';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting daily services digest dispatch...');

        // Boundaries for "today" in Cairo timezone, converted to UTC for DB query compatibility
        $todayStartUtc = now()->timezone('Africa/Cairo')->startOfDay()->setTimezone('UTC');
        $todayEndUtc = now()->timezone('Africa/Cairo')->endOfDay()->setTimezone('UTC');

        $this->info("Querying services added/approved between: {$todayStartUtc->toDateTimeString()} UTC and {$todayEndUtc->toDateTimeString()} UTC (Cairo today)");

        // Fetch website services created today (Cairo)
        $newWebsiteServices = WebsiteService::whereBetween('created_at', [$todayStartUtc, $todayEndUtc])->get();

        // Fetch marketplace services approved/active today (Cairo)
        $newMarketplaceServices = Service::where('status', 'active')
            ->whereBetween('approved_at', [$todayStartUtc, $todayEndUtc])
            ->get();

        $this->info("Found {$newWebsiteServices->count()} new website services.");
        $this->info("Found {$newMarketplaceServices->count()} new marketplace services.");

        if ($newWebsiteServices->isEmpty() && $newMarketplaceServices->isEmpty()) {
            $this->info('No new services added or approved today. Digest email skipped.');
            return Command::SUCCESS;
        }

        // Send notifications to all active users with a valid email
        $userCount = 0;
        User::whereNotNull('email')
            ->where('email', '!=', '')
            ->chunk(100, function ($users) use ($newWebsiteServices, $newMarketplaceServices, &$userCount) {
                foreach ($users as $user) {
                    $user->notify(new DailyNewServicesDigestNotification($newWebsiteServices, $newMarketplaceServices));
                    $userCount++;
                }
            });

        $this->info("Dispatched daily new services digest to {$userCount} users.");

        return Command::SUCCESS;
    }
}
