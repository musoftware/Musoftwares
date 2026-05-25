<?php

namespace Modules\Booking\app\Features\GcalSync\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\Models\Booking;
use Modules\Booking\app\Features\GcalSync\Models\GoogleCalendar;
use Modules\Booking\app\Features\GcalSync\Services\GoogleCalendarSyncService;
use Modules\Booking\app\Features\GcalSync\Events\BookingSyncedToGoogle;
use Modules\Booking\app\Features\GcalSync\Events\GoogleSyncFailed;
use Modules\Booking\app\Features\GcalSync\Models\GoogleSyncLog;
use Modules\Booking\app\Features\GcalSync\Models\GoogleSyncEvent;

class PushBookingToGoogleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $booking;
    public $tries = 3;
    public $backoff = 60; // 1 min exponential backoff

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function handle(GoogleCalendarSyncService $syncService): void
    {
        // Find all active calendars syncing outwards for this tenant
        $calendars = GoogleCalendar::where('tenant_id', $this->booking->tenant_id)
            ->where('is_active', true)
            ->whereIn('sync_direction', ['two-way', 'push'])
            ->get();

        foreach ($calendars as $calendar) {
            try {
                $syncService->pushBooking($this->booking, $calendar);
                
                // Track in db
                GoogleSyncEvent::updateOrCreate(
                    ['tenant_id' => $this->booking->tenant_id, 'booking_id' => $this->booking->id],
                    ['calendar_id' => $calendar->id, 'google_event_id' => 'mock_google_id_' . $this->booking->id]
                );

                GoogleSyncLog::create([
                    'tenant_id' => $this->booking->tenant_id,
                    'calendar_id' => $calendar->id,
                    'direction' => 'push',
                    'status' => 'success',
                    'events_synced' => 1,
                ]);

                event(new BookingSyncedToGoogle($this->booking, $calendar));
                
            } catch (\Exception $e) {
                GoogleSyncLog::create([
                    'tenant_id' => $this->booking->tenant_id,
                    'calendar_id' => $calendar->id,
                    'direction' => 'push',
                    'status' => 'failed',
                    'error_reason' => $e->getMessage()
                ]);

                event(new GoogleSyncFailed($calendar, $e->getMessage()));
                
                if ($this->attempts() >= $this->tries) {
                    $this->fail($e);
                }
                throw $e;
            }
        }
    }
}
