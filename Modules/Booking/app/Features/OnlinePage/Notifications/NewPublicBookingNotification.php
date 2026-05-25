<?php

namespace Modules\Booking\app\Features\OnlinePage\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Modules\Booking\Models\Booking;

class NewPublicBookingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $booking;

    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'message' => "New public booking created by {$this->booking->customer->name} for {$this->booking->starts_at->format('M d, H:i')}.",
            'booking_id' => $this->booking->id,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'message' => "New public booking created by {$this->booking->customer->name}.",
            'booking_id' => $this->booking->id,
        ]);
    }
}
