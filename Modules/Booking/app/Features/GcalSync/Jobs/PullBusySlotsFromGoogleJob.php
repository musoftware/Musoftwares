<?php

namespace Modules\Booking\app\Features\GcalSync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\app\Features\GcalSync\Models\GoogleCalendar;
use Modules\Booking\app\Features\GcalSync\Services\CalendarAvailabilityImporter;
use Modules\Booking\app\Features\GcalSync\Models\GoogleSyncLog;
use Modules\Booking\app\Features\GcalSync\Events\GoogleSyncFailed;

class PullBusySlotsFromGoogleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $calendar;

    public function __construct(GoogleCalendar $calendar)
    {
        $this->calendar = $calendar;
    }

    public function handle(CalendarAvailabilityImporter $importer): void
    {
        try {
            $importer->importBusySlots($this->calendar);

            GoogleSyncLog::create([
                'tenant_id' => $this->calendar->tenant_id,
                'calendar_id' => $this->calendar->id,
                'direction' => 'pull',
                'status' => 'success',
                'events_synced' => 1,
            ]);

        } catch (\Exception $e) {
             GoogleSyncLog::create([
                'tenant_id' => $this->calendar->tenant_id,
                'calendar_id' => $this->calendar->id,
                'direction' => 'pull',
                'status' => 'failed',
                'error_reason' => $e->getMessage()
            ]);

            event(new GoogleSyncFailed($this->calendar, $e->getMessage()));
            throw $e;
        }
    }
}
