<?php

namespace App\Services;

use App\Models\GuestTicket;
use App\Models\GuestTicketMessage;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class GuestTicketMailer extends BaseService
{
    public function sendReply(GuestTicket $ticket, GuestTicketMessage $message): void
    {
        Mail::send(
            [],
            [],
            function ($rawMessage) use ($ticket, $message) {
                $rawMessage
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->to($ticket->email, $ticket->name)
                    ->replyTo($ticket->reply_email)
                    ->subject($message->subject ?: $ticket->subject_tag.' '.$ticket->subject)
                    ->html($message->body_html ?: nl2br(e($message->body_text ?? '')))
                    ->withSymfonyMessage(function ($symfony) use ($ticket, $message) {
                        $symfony->getHeaders()->addTextHeader('In-Reply-To', (string) $message->in_reply_to);
                        $symfony->getHeaders()->addTextHeader('References', $this->buildReferences($message));
                        $symfony->getHeaders()->addTextHeader('X-Guest-Ticket-Id', (string) $ticket->id);
                    });
            }
        );
    }

    public function generateMessageId(GuestTicket $ticket): string
    {
        $domain = (string) config('imap.domain', 'musoftwares.com');

        return sprintf('guest-ticket-%d-%s@%s', $ticket->id, (string) Str::uuid(), $domain);
    }

    public function generateSubject(GuestTicket $ticket, ?string $base = null): string
    {
        $base = $base ?: ($ticket->subject ?: 'Support request');

        return trim($ticket->subject_tag.' '.$base);
    }

    private function buildReferences(GuestTicketMessage $message): string
    {
        $headers = [];
        if ($message->in_reply_to) {
            $headers[] = $message->in_reply_to;
        }
        $previous = GuestTicketMessage::where('guest_ticket_id', $message->guest_ticket_id)
            ->where('direction', 'outbound')
            ->where('id', '!=', $message->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->pluck('message_id')
            ->filter()
            ->all();
        foreach ($previous as $prev) {
            $headers[] = $prev;
        }

        return implode(' ', array_unique($headers));
    }
}
