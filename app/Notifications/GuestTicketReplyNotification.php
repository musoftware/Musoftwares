<?php

namespace App\Notifications;

use App\Models\GuestTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GuestTicketReplyNotification extends Notification
{
    use Queueable;

    public GuestTicket $ticket;

    public function __construct(GuestTicket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'guest_ticket_reply',
            'title' => 'New reply on guest ticket #'.$this->ticket->id,
            'message' => $this->ticket->name.' replied to guest ticket #'.$this->ticket->id,
            'ticket_id' => $this->ticket->id,
            'url' => route('admin.guest-tickets.show', $this->ticket->id),
        ];
    }
}
