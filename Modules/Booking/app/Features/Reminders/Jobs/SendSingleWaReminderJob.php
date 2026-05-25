<?php

namespace Modules\Booking\app\Features\Reminders\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\Reminders\Models\BookingWaReminder;
use Modules\Booking\app\Features\Reminders\Repositories\WaReminderRepository;
use Modules\Booking\app\Features\Reminders\Events\BookingWaReminderSent;
use Modules\Booking\app\Features\Reminders\Events\BookingWaReminderFailed;
use Modules\Booking\app\Features\Reminders\Services\WaReminderLimitsService;
use Exception;

class SendSingleWaReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $reminder;
    public $tries = 3;

    public function __construct(BookingWaReminder $reminder)
    {
        $this->reminder = $reminder;
    }

    /**
     * Execute the job.
     */
    public function handle(WaReminderRepository $repository, WaReminderLimitsService $limitsService)
    {
        // Double check limits before sending
        if (!$limitsService->canUse($this->reminder->tenant_id)) {
            $repository->markAsFailed($this->reminder, 'Tenant limit reached or feature inactive.');
            return;
        }

        try {
            // =========================================================================
            // ABSTRACT API CALL HERE
            // Depending on the external provider (Twilio, Wati, Tool WA API)
            // =========================================================================
            
            // Example simulated API call:
            // $response = Http::post('https://api.whatsapp.com/send', [ ... ]);
            // if (!$response->successful()) { throw new Exception($response->body()); }
            
            // Simulate a successful send
            $isSuccess = true;

            if ($isSuccess) {
                $repository->markAsSent($this->reminder);
                $limitsService->increaseUsage($this->reminder->tenant_id);
                
                event(new BookingWaReminderSent($this->reminder));
            } else {
                throw new Exception('API returned failure response.');
            }

        } catch (Exception $e) {
            $repository->markAsFailed($this->reminder, $e->getMessage());
            event(new BookingWaReminderFailed($this->reminder, $e->getMessage()));
            
            // Optionally re-throw to trigger a queue retry
            // throw $e;
        }
    }
}
