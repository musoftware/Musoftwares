<?php

namespace App\Mail;

use App\Models\GuestTicket;
use App\Models\GuestTicketMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class GuestTicketOutboundReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public GuestTicket $ticket;

    public GuestTicketMessage $message;

    public function __construct(GuestTicket $ticket, GuestTicketMessage $message)
    {
        $this->ticket = $ticket;
        $this->message = $message;
    }

    public function envelope(): Envelope
    {
        $domain = (string) config('imap.domain', 'musoftwares.com');

        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            to: [new Address($this->ticket->email, $this->ticket->name)],
            replyTo: [new Address($this->ticket->reply_email)],
            subject: $this->message->subject,
        );
    }

    public function headers(): Headers
    {
        $references = [];
        if ($this->message->message_id) {
            $references[] = (string) $this->message->message_id;
        }

        $textHeaders = [
            'X-Guest-Ticket-Id' => (string) $this->ticket->id,
        ];

        if ($this->message->in_reply_to) {
            $textHeaders['In-Reply-To'] = (string) $this->message->in_reply_to;
        }

        return new Headers(
            references: $references,
            text: $textHeaders,
        );
    }

    public function build()
    {
        return $this->html(
            $this->message->body_html ?: ('<pre style="white-space:pre-wrap;font-family:inherit">'.e((string) $this->message->body_text).'</pre>')
        );
    }
}
