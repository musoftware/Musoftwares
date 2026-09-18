<?php

namespace App\Console\Commands;

use App\Jobs\SendLoyaltyProgressEmailJob;
use App\Models\Invoice;
use App\Models\LoyaltyNotificationLog;
use App\Models\LoyaltyTier;
use App\Models\User;
use Illuminate\Console\Command;

class ProcessLoyaltyDueReminders extends Command
{
    protected $signature = 'loyalty:process-reminders';

    protected $description = 'Check tier progress thresholds and invoice due dates, dispatch loyalty reminder emails.';

    public function handle(): int
    {
        $this->processProgressReminders();
        $this->processInvoiceDueReminders();
        $this->processQuarterlyExpiryReminders();

        return self::SUCCESS;
    }

    /**
     * Find users at 70-85% of their next tier and dispatch a progress email if not already sent.
     */
    private function processProgressReminders(): void
    {
        $tiers = LoyaltyTier::where('is_active', true)->orderBy('min_lifetime_points')->get();

        if ($tiers->count() < 2) {
            return;
        }

        foreach ($tiers as $index => $tier) {
            $nextTier = $tiers->get($index + 1);

            if ($nextTier === null) {
                continue;
            }

            $rangeMin = $tier->min_lifetime_points;
            $rangeMax = $nextTier->min_lifetime_points;
            $threshold70 = $rangeMin + (int) round(($rangeMax - $rangeMin) * 0.70);
            $threshold85 = $rangeMin + (int) round(($rangeMax - $rangeMin) * 0.85);

            User::whereBetween('loyalty_lifetime_points', [$threshold70, $threshold85 - 1])
                ->chunk(50, function ($users) use ($nextTier, $rangeMin, $rangeMax) {
                    foreach ($users as $user) {
                        $progressPct = $rangeMax > $rangeMin
                            ? (int) round((($user->loyalty_lifetime_points - $rangeMin) / ($rangeMax - $rangeMin)) * 100)
                            : 0;

                        $fingerprint = "tier_progress:{$user->id}:{$nextTier->id}:" . floor($user->loyalty_lifetime_points / 10);

                        if (LoyaltyNotificationLog::alreadySent($fingerprint)) {
                            continue;
                        }

                        SendLoyaltyProgressEmailJob::dispatch($user->id, $nextTier->id, $progressPct);

                        LoyaltyNotificationLog::create([
                            'user_id'                   => $user->id,
                            'notification_type'         => 'tier_progress',
                            'deduplication_fingerprint' => $fingerprint,
                            'status'                    => 'sent',
                            'sent_at'                   => now(),
                            'metadata'                  => [
                                'progress_pct'  => $progressPct,
                                'next_tier_id'  => $nextTier->id,
                            ],
                        ]);
                    }
                });
        }

        $this->info('Tier progress reminders processed.');
    }

    /**
     * Find invoices due in 5 days and remind the client of the points they can earn by paying today.
     */
    private function processInvoiceDueReminders(): void
    {
        $dueDate = now()->addDays(5)->toDateString();

        Invoice::where('status', 'unpaid')
            ->whereDate('due_date', $dueDate)
            ->whereNull('deleted_at')
            ->with('user')
            ->chunk(50, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $user = $invoice->user;

                    if ($user === null || empty($user->email)) {
                        continue;
                    }

                    $fingerprint = "invoice_due_reminder:{$invoice->id}";

                    if (LoyaltyNotificationLog::alreadySent($fingerprint)) {
                        continue;
                    }

                    \App\Mail\InvoiceDueLoyaltyReminderMail::sendToUser($user, $invoice);

                    LoyaltyNotificationLog::create([
                        'user_id'                   => $user->id,
                        'notification_type'         => 'invoice_due_reminder',
                        'deduplication_fingerprint' => $fingerprint,
                        'status'                    => 'sent',
                        'sent_at'                   => now(),
                        'metadata'                  => ['invoice_id' => $invoice->id],
                    ]);
                }
            });

        $this->info('Invoice due reminders processed.');
    }

    /**
     * Find users with active loyalty points when approaching the end of the quarter (within 14 days)
     * and send them the expiration reminder email.
     */
    private function processQuarterlyExpiryReminders(): void
    {
        /** @var \App\Services\LoyaltyService $loyaltyService */
        $loyaltyService = app(\App\Services\LoyaltyService::class);
        $result = $loyaltyService->sendQuarterlyPointsExpiryReminders(14);

        $this->info("Quarterly points expiry reminders: {$result['sent_count']} sent (Days remaining: {$result['days_remaining']}).");
    }
}
