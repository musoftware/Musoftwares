<?php

namespace App\Mail;

use App\Models\GuestTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AdminGuestTicketCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly GuestTicket $guestTicket
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "تذكرة زائر جديدة (#{$this->guestTicket->id}) من {$this->guestTicket->name}",
        );
    }

    public function content(): Content
    {
        $baseUrl = rtrim(config('app.url'), '/');
        $adminGuestTicketUrl = $baseUrl . "/admin/guest-tickets/{$this->guestTicket->id}";

        return new Content(
            view: 'emails.tickets.guest_created_admin',
            with: [
                'ticketId' => $this->guestTicket->id,
                'guestName' => $this->guestTicket->name,
                'guestEmail' => $this->guestTicket->email,
                'guestMobile' => $this->guestTicket->mobile,
                'subject' => $this->guestTicket->subject ?? 'طلب استفسار جديد',
                'bodyText' => $this->guestTicket->body,
                'adminGuestTicketUrl' => $adminGuestTicketUrl,
            ],
        );
    }
}
