<?php

namespace Modules\Booking\app\Features\Reminders\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\Reminders\Repositories\WaReminderRepository;
use Illuminate\Support\Facades\Log;

class ProcessDueWaRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     * This is intended to be called by the scheduler every minute.
     */
    public function handle(WaReminderRepository $repository)
    {
        // Fetch up to 100 pending reminders due to send
        $dueReminders = $repository->getDuePendingReminders(100);

        if ($dueReminders->isEmpty()) {
            return;
        }

        Log::info("ProcessDueWaRemindersJob: Found {$dueReminders->count()} due reminders.");

        foreach ($dueReminders as $reminder) {
            // Dispatch a specific job to send the reminder.
            // This ensures if one fails, others aren't affected and we can retry individually.
            SendSingleWaReminderJob::dispatch($reminder);
        }
    }
}
