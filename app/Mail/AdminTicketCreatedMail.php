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

class AdminTicketCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?User $client = null
    ) {}

    public function envelope(): Envelope
    {
        $user = $this->client ?? $this->ticket->user;
        $clientName = $user?->name ?? $this->ticket->anonymous_name ?? 'العميل';

        return new Envelope(
            subject: "تذكرة دعم فني جديدة (#{$this->ticket->id}) من العميل {$clientName}",
        );
    }

    public function content(): Content
    {
        $user = $this->client ?? $this->ticket->user;
        $clientName = $user?->name ?? $this->ticket->anonymous_name ?? 'العميل';
        $clientEmail = $user?->email ?? $this->ticket->anonymous_email ?? 'غير محدد';
        $projectName = $this->ticket->project ? ($this->ticket->project->project_name ?? $this->ticket->project->name) : null;
        $createdAtCairo = Carbon::parse($this->ticket->created_at)->setTimezone('Africa/Cairo')->format('Y-m-d h:i A');
        $baseUrl = rtrim(config('app.url'), '/');
        $adminTicketUrl = $baseUrl . "/admin/tickets/{$this->ticket->id}";

        return new Content(
            view: 'emails.tickets.created_admin',
            with: [
                'ticketId' => $this->ticket->id,
                'ticketSubject' => $this->ticket->ticket_subject,
                'ticketDescription' => $this->ticket->ticket_description,
                'clientName' => $clientName,
                'clientEmail' => $clientEmail,
                'projectName' => $projectName,
                'priority' => $this->ticket->priority ?? 'Normal',
                'createdAtCairo' => $createdAtCairo,
                'adminTicketUrl' => $adminTicketUrl,
            ],
        );
    }
}
