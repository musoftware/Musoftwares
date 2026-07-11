<?php

namespace App\Mail;

use App\Models\GuestTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestTicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public GuestTicket $ticket;

    public string $body;

    public function __construct(GuestTicket $ticket, string $body)
    {
        $this->ticket = $ticket;
        $this->body = $body;
    }

    public function envelope(): Envelope
    {
        $domain = (string) config('imap.domain', 'musoftwares.com');

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            to: [new Address($this->ticket->email, $this->ticket->name)],
            replyTo: [new Address($this->ticket->reply_email)],
            subject: $this->ticket->subject_tag.' '.($this->ticket->subject ?: 'Support'),
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->body,
        );
    }
}
