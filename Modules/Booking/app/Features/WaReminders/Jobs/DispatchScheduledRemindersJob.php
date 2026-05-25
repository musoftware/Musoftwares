<?php

namespace Modules\Booking\app\Features\WaReminders\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\WaReminders\Models\WaSchedule;

class DispatchScheduledRemindersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Find all schedules that are pending and their scheduled time has arrived
        $dueSchedules = WaSchedule::with('booking.customer')
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->chunk(100, function ($schedules) {
                foreach ($schedules as $schedule) {
                    // Dispatch the actual sending job
                    SendWhatsAppMessageJob::dispatch($schedule);
                }
            });
    }
}
