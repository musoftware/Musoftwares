<?php

namespace App\Mail;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly User $assignedAgent
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "تم تعيين تذكرة دعم فني جديدة لك (#{$this->ticket->id}) — {$this->ticket->ticket_subject}",
        );
    }

    public function content(): Content
    {
        $client = $this->ticket->user;
        $clientName = $client?->name ?? $this->ticket->anonymous_name ?? 'غير محدد';
        $clientEmail = $client?->email ?? $this->ticket->anonymous_email ?? 'غير محدد';
        $baseUrl = rtrim(config('app.url'), '/');
        $adminTicketUrl = $baseUrl . "/admin/tickets/{$this->ticket->id}";

        return new Content(
            view: 'emails.tickets.assigned_agent',
            with: [
                'ticketId' => $this->ticket->id,
                'ticketSubject' => $this->ticket->ticket_subject,
                'ticketDescription' => $this->ticket->ticket_description,
                'agentName' => $this->assignedAgent->name,
                'clientName' => $clientName,
                'clientEmail' => $clientEmail,
                'priority' => $this->ticket->priority ?? 'Normal',
                'adminTicketUrl' => $adminTicketUrl,
            ],
        );
    }
}
