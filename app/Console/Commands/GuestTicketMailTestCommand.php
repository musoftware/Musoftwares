<?php

namespace App\Console\Commands;

use App\Models\GuestTicket;
use App\Services\GuestTicketMailer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class GuestTicketMailTestCommand extends Command
{
    protected $signature = 'guest-tickets:mail-test {ticketId : The guest ticket ID to send a test email to} {--subject= : Subject override} {--body= : Body override}';

    protected $description = 'Send a smoke test email for a guest ticket without persisting a message';

    public function handle(GuestTicketMailer $mailer): int
    {
        $ticketId = (int) $this->argument('ticketId');
        $ticket = GuestTicket::find($ticketId);
        if (! $ticket) {
            $this->error("GuestTicket #{$ticketId} not found.");

            return self::FAILURE;
        }

        $messageId = $mailer->generateMessageId($ticket);
        $subject = $this->option('subject') ?: $mailer->generateSubject($ticket, 'Smoke test');
        $body = $this->option('body') ?: 'This is a smoke test email from the Guest Tickets system.';

        Mail::raw($body, function ($raw) use ($ticket, $subject, $messageId) {
            $raw
                ->from(config('mail.from.address'), config('mail.from.name'))
                ->to($ticket->email, $ticket->name)
                ->replyTo($ticket->reply_email)
                ->subject($subject)
                ->withSymfonyMessage(function ($symfony) use ($messageId, $ticket) {
                    $symfony->getHeaders()->addTextHeader('Message-ID', $messageId);
                    $symfony->getHeaders()->addTextHeader('X-Guest-Ticket-Id', (string) $ticket->id);
                });
        });

        $this->info("Smoke test sent to {$ticket->email} with Message-ID: {$messageId}");

        return self::SUCCESS;
    }
}
