<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Integrations\GoogleCalendarService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Booking\Models\Booking;

class SyncBookingToGoogleCalendar implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $booking;
    public $user; // The staff member assigned to the booking
    public $action; // 'create', 'update', 'delete'

    /**
     * Create a new job instance.
     */
    public function __construct(Booking $booking, User $user, string $action = 'create')
    {
        $this->booking = $booking;
        $this->user = $user;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(GoogleCalendarService $calendarService): void
    {
        if ($this->action === 'delete') {
            if ($this->booking->google_event_id) {
                $calendarService->deleteEvent($this->user, $this->booking->google_event_id);
            }
            return;
        }

        if (!$this->booking->starts_at || !$this->booking->ends_at) {
            return; // invalid booking format
        }

        $title = "Booking: " . $this->booking->guest_name;
        $description = "Client: " . $this->booking->guest_email . "\nPhone: " . $this->booking->guest_phone . "\nNotes: " . $this->booking->notes;

        if ($this->action === 'create' || !$this->booking->google_event_id) {
            $eventId = $calendarService->createEvent($this->user, $title, $description, $this->booking->starts_at, $this->booking->ends_at);
            if ($eventId) {
                // update quietly to prevent loops
                Booking::withoutEvents(function() use ($eventId) {
                    $this->booking->google_event_id = $eventId;
                    $this->booking->save();
                });
            }
        } elseif ($this->action === 'update' && $this->booking->google_event_id) {
            $calendarService->updateEvent($this->user, $this->booking->google_event_id, $title, $description, $this->booking->starts_at, $this->booking->ends_at);
        }
    }
}
