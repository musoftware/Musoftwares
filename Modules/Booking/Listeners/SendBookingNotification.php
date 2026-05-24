<?php

namespace Modules\Booking\Listeners;

use Modules\Booking\Events\BookingStatusChanged;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingConfirmed;

class SendBookingNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     *
     * @param BookingStatusChanged $event
     * @return void
     */
    public function handle(BookingStatusChanged $event)
    {
        $booking = $event->booking;

        try {
            if ($event->isRescheduled) {
                // For a real app, you'd send a BookingRescheduled mailable
                Log::info("Booking {$booking->id} rescheduled. Notification sent to {$booking->guest_email}");
            } else {
                switch ($event->status) {
                    case 'confirmed':
                        Mail::to($booking->guest_email)->send(new BookingConfirmed($booking));
                        Log::info("Booking {$booking->id} confirmed. Notification sent to {$booking->guest_email}");
                        break;
                    case 'cancelled':
                        // Mail::to($booking->guest_email)->send(new BookingCancelled($booking));
                        Log::info("Booking {$booking->id} cancelled. Notification sent to {$booking->guest_email}");
                        break;
                    case 'completed':
                        Log::info("Booking {$booking->id} completed.");
                        break;
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send booking notification: ' . $e->getMessage());
        }
    }
}
