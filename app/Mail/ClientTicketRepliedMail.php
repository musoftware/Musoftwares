<?php

namespace App\Mail;

use App\Models\Message;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientTicketRepliedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly Message $ticketMessage,
        public readonly User $sender,
        public readonly ?User $client = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "رد جديد على تذكرتك (#{$this->ticket->id}) — {$this->ticket->ticket_subject}",
        );
    }

    public function content(): Content
    {
        $user = $this->client ?? $this->ticket->user;
        $clientName = $user?->name ?? 'عميلنا العزيز';
        $senderName = $this->sender->name ?? 'فريق الدعم الفني';
        $createdAtCairo = Carbon::parse($this->ticketMessage->created_at)->setTimezone('Africa/Cairo')->format('Y-m-d h:i A');
        $baseUrl = rtrim(config('app.url'), '/');
        $ticketUrl = $baseUrl . "/tickets/{$this->ticket->id}";

        return new Content(
            view: 'emails.tickets.replied_client',
            with: [
                'ticketId' => $this->ticket->id,
                'ticketSubject' => $this->ticket->ticket_subject,
                'clientName' => $clientName,
                'senderName' => $senderName,
                'replyBody' => $this->ticketMessage->body,
                'createdAtCairo' => $createdAtCairo,
                'ticketUrl' => $ticketUrl,
            ],
        );
    }
}
