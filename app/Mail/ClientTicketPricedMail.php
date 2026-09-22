<?php

namespace App\Mail;

use App\Models\Currency;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ClientTicketPricedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?User $client = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "تم تسعير طلبك للتذكرة (#{$this->ticket->id}) — {$this->ticket->ticket_subject}",
        );
    }

    public function content(): Content
    {
        $user = $this->client ?? $this->ticket->user;
        $clientName = $user?->name ?? 'عميلنا العزيز';
        $currencySymbol = 'EGP';
        if ($this->ticket->currency_id) {
            $currencySymbol = Currency::find($this->ticket->currency_id)?->symbol ?? 'EGP';
        }
        $formattedPrice = number_format((float) ($this->ticket->price ?? 0), 2);
        $baseUrl = rtrim(config('app.url'), '/');
        $ticketUrl = $baseUrl . "/tickets/{$this->ticket->id}";

        return new Content(
            view: 'emails.tickets.priced_client',
            with: [
                'ticketId' => $this->ticket->id,
                'ticketSubject' => $this->ticket->ticket_subject,
                'clientName' => $clientName,
                'formattedPrice' => $formattedPrice,
                'currencySymbol' => $currencySymbol,
                'pricingNotes' => $this->ticket->pricing_notes,
                'ticketUrl' => $ticketUrl,
            ],
        );
    }
}
