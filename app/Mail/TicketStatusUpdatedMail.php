<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketStatusUpdatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly string $statusArabic,
        public readonly ?string $comment = null,
        public readonly ?User $client = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "تحديث حالة التذكرة (#{$this->ticket->id}): {$this->statusArabic}",
        );
    }

    public function content(): Content
    {
        $user = $this->client ?? $this->ticket->user;
        $clientName = $user?->name ?? 'عميلنا العزيز';
        $updatedAtCairo = Carbon::now('Africa/Cairo')->format('Y-m-d h:i A');
        $baseUrl = rtrim(config('app.url'), '/');
        $ticketUrl = $baseUrl . "/tickets/{$this->ticket->id}";

        return new Content(
            view: 'emails.tickets.status_updated',
            with: [
                'ticketId' => $this->ticket->id,
                'ticketSubject' => $this->ticket->ticket_subject,
                'clientName' => $clientName,
                'statusArabic' => $this->statusArabic,
                'comment' => $this->comment,
                'updatedAtCairo' => $updatedAtCairo,
                'ticketUrl' => $ticketUrl,
            ],
        );
    }
}
