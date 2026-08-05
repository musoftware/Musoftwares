<?php

namespace Modules\Marketplace\Console;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Marketplace\Emails\DiscountDigestMail;
use Modules\Marketplace\Models\Service;

class SendDailyDiscountNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:send-daily-discounts {--limit=30 : Number of random users to notify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily email notifications about active marketplace discounts to random daily users.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $this->info("Fetching active marketplace services with discounts...");

        // Fetch active services having packages with current price less than old_price
        $discountedServices = Service::with(['seller', 'packages' => function ($query) {
                $query->whereNotNull('old_price')
                    ->whereColumn('old_price', '>', 'price')
                    ->where('price', '>=', 0);
            }])
            ->where('status', 'active')
            ->whereHas('packages', function ($query) {
                $query->whereNotNull('old_price')
                    ->whereColumn('old_price', '>', 'price')
                    ->where('price', '>=', 0);
            })
            ->latest()
            ->take(10)
            ->get();

        if ($discountedServices->isEmpty()) {
            $this->info("No active marketplace services with discounts found today.");
            return self::SUCCESS;
        }

        $users = User::whereNotNull('email')
            ->where('email', '!=', '')
            ->inRandomOrder()
            ->limit($limit)
            ->get();

        if ($users->isEmpty()) {
            $this->warn("No users found to receive discount notifications.");
            return self::SUCCESS;
        }

        $sentCount = 0;
        foreach ($users as $user) {
            try {
                Mail::to($user->email)->send(new DiscountDigestMail($user, $discountedServices));
                $sentCount++;
            } catch (\Throwable $e) {
                Log::error("[DailyDiscountNotify] Failed to send discount email to user #{$user->id} ({$user->email}): " . $e->getMessage());
            }
        }

        $this->info("Successfully sent daily discount digest emails to {$sentCount} random users.");
        Log::info("[DailyDiscountNotify] Successfully sent daily discount digest emails to {$sentCount} random users.");

        return self::SUCCESS;
    }
}
