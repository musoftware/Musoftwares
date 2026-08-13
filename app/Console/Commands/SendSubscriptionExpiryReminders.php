<?php

namespace App\Console\Commands;

use App\Models\UserSubscription;
use App\Mail\SubscriptionExpiredReminderMail;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendSubscriptionExpiryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:send-expiry-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send email reminders for expired subscriptions immediately and once a week for 3 weeks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting subscription expiry reminder check...');
        $now = now();

        // 1. Mark naturally expired active subscriptions as 'expired'
        try {
            $expiredCount = UserSubscription::where('status', 'active')
                ->where('expires_at', '<=', $now)
                ->update(['status' => 'expired']);

            if ($expiredCount > 0) {
                $this->info("Updated {$expiredCount} naturally expired subscriptions to 'expired' status.");
            }
        } catch (Throwable $e) {
            Log::error('Failed to update expired subscriptions: ' . $e->getMessage());
            $this->error('Failed to update expired subscriptions. See logs.');
        }

        // 2. Fetch all expired subscriptions that need reminders (reminders_sent < 4)
        $expiredSubscriptions = UserSubscription::with(['user'])
            ->where('status', 'expired')
            ->where('expired_reminders_sent', '<', 4)
            ->get();

        $this->info("Found {$expiredSubscriptions->count()} expired subscriptions to check for reminders.");

        foreach ($expiredSubscriptions as $subscription) {
            $user = $subscription->user;
            if (!$user || !$user->email) {
                $this->warn("Subscription ID: {$subscription->id} has no valid associated user/email. Skipping.");
                continue;
            }

            $sentCount = $subscription->expired_reminders_sent;
            $lastSentAt = $subscription->last_expired_reminder_sent_at;

            $shouldSend = false;

            if ($sentCount === 0) {
                // Immediate notification (Week 0)
                $shouldSend = true;
            } elseif ($lastSentAt) {
                // Weekly reminders (Week 1, Week 2, Week 3)
                // Use Cairo timezone to calculate differences or standard Carbon comparisons
                $daysDiff = Carbon::parse($lastSentAt)->diffInDays($now);
                if ($daysDiff >= 7) {
                    $shouldSend = true;
                }
            }

            if ($shouldSend) {
                try {
                    $this->info("Sending reminder #".($sentCount + 1)." for subscription ID: {$subscription->id} to user: {$user->email} (Module: {$subscription->object})");
                    
                    // Determine user locale
                    $locale = $user->lang ?? $user->locale ?? 'ar';
                    
                    // Send the email
                    Mail::to($user->email)->send(
                        (new SubscriptionExpiredReminderMail($subscription, $user))->locale($locale)
                    );

                    // Update tracking columns
                    $subscription->update([
                        'expired_reminders_sent' => $sentCount + 1,
                        'last_expired_reminder_sent_at' => $now,
                    ]);

                } catch (Throwable $mailException) {
                    Log::error("Failed to send subscription expired email to {$user->email} for sub ID {$subscription->id}: " . $mailException->getMessage());
                    $this->error("Failed to send reminder for sub ID {$subscription->id}. See logs.");
                }
            }
        }

        $this->info('Subscription expiry reminder check completed.');
    }
}
