<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use App\Models\Ticket;

class NewGuestTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public Ticket $ticket;

    /**
     * Create a new notification instance.
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_guest_ticket',
            'title' => 'طلب خدمة حصرية جديد',
            'message' => 'هناك طلب جديد للخدمة الحصرية من ' . $this->ticket->getDisplayName(),
            'ticket_id' => $this->ticket->id,
            'url' => route('admin.tickets.show', $this->ticket->id),
        ];
    }
}
