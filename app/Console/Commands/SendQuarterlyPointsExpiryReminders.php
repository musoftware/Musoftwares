<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class SendQuarterlyPointsExpiryReminders extends Command
{
    protected $signature = 'loyalty:send-expiry-reminders 
                            {--days=14 : Days threshold before quarter end to trigger reminders} 
                            {--dry-run : Simulate sending without actually dispatching emails} 
                            {--user= : Target a specific user ID for testing}';

    protected $description = 'Send quarterly loyalty points expiry warnings to incentivize clients to redeem their points before they reset.';

    public function handle(LoyaltyService $loyaltyService): int
    {
        $days = (int) $this->option('days');
        $dryRun = (bool) $this->option('dry-run');
        $userId = $this->option('user');

        $info = $loyaltyService->getQuarterlyExpiryInfo();
        $this->info("Quarterly Cycle: {$info['quarter_name']}");
        $this->info("Expiry Deadline: {$info['expiry_date_formatted']} (Cairo Time)");
        $this->info("Days Remaining:  {$info['days_remaining']} days");

        if ($userId) {
            $user = User::find($userId);
            if (! $user) {
                $this->error("User ID {$userId} not found.");
                return self::FAILURE;
            }

            $this->info("Sending direct test reminder to: {$user->name} ({$user->email}) with {$user->loyalty_points_balance} points...");
            if (! $dryRun) {
                \App\Mail\LoyaltyPointsExpiringMail::sendToUser(
                    $user,
                    (int) $user->loyalty_points_balance,
                    $info['days_remaining'],
                    $info['expiry_date']
                );
                $this->info("Dispatched successfully to {$user->email}.");
            } else {
                $this->warn("[DRY RUN] Would dispatch email to {$user->email}.");
            }

            return self::SUCCESS;
        }

        $this->info("Dispatching reminders to all eligible clients (threshold: <= {$days} days)...");
        $result = $loyaltyService->sendQuarterlyPointsExpiryReminders($days, $dryRun);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Quarter', $result['quarter_name'] ?? $info['quarter_name']],
                ['Days Remaining', $result['days_remaining'] ?? $info['days_remaining']],
                ['Emails Dispatched', $result['sent_count'] ?? 0],
                ['Dry Run', $dryRun ? 'Yes' : 'No'],
            ]
        );

        return self::SUCCESS;
    }
}
