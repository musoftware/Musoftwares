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

class AdminTicketRepliedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly Message $ticketMessage,
        public readonly User $client
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "رد جديد من العميل {$this->client->name} على التذكرة (#{$this->ticket->id})",
        );
    }

    public function content(): Content
    {
        $clientName = $this->client->name ?? 'العميل';
        $clientEmail = $this->client->email ?? 'غير محدد';
        $createdAtCairo = Carbon::parse($this->ticketMessage->created_at)->setTimezone('Africa/Cairo')->format('Y-m-d h:i A');
        $baseUrl = rtrim(config('app.url'), '/');
        $adminTicketUrl = $baseUrl . "/admin/tickets/{$this->ticket->id}";

        return new Content(
            view: 'emails.tickets.replied_admin',
            with: [
                'ticketId' => $this->ticket->id,
                'ticketSubject' => $this->ticket->ticket_subject,
                'clientName' => $clientName,
                'clientEmail' => $clientEmail,
                'replyBody' => $this->ticketMessage->body,
                'createdAtCairo' => $createdAtCairo,
                'adminTicketUrl' => $adminTicketUrl,
            ],
        );
    }
}
