<?php

namespace App\Mail;

use App\Models\GuestTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;

class GuestTicketConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public GuestTicket $ticket;

    public string $messageId;

    public string $message_subject;

    public function __construct(GuestTicket $ticket, string $messageId, string $subject)
    {
        $this->ticket          = $ticket;
        $this->messageId       = $messageId;
        $this->message_subject = $subject;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name')),
            to: [new Address($this->ticket->email, $this->ticket->name)],
            replyTo: [new Address($this->ticket->reply_email)],
            subject: $this->message_subject,
        );
    }

    public function headers(): Headers
    {
        return new Headers(
            messageId: $this->messageId,
            text: [
                'X-Guest-Ticket-Id' => (string) $this->ticket->id,
            ],
        );
    }

    public function build()
    {
        return $this->html(
            '<p>Hi ' . e($this->ticket->name) . ',</p>' .
            '<p>Thanks for contacting us. Your ticket #' . $this->ticket->id . ' has been received. We will respond shortly.</p>' .
            '<p>— The Musoftwares Team</p>'
        );
    }
}
