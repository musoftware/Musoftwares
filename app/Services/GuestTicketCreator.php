<?php

namespace App\Services;

use App\Mail\GuestTicketConfirmationMail;
use App\Models\GuestTicket;
use App\Models\GuestTicketMessage;
use App\Models\User;
use App\Notifications\GuestTicketReplyNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class GuestTicketCreator extends BaseService
{
    public function __construct(private readonly GuestTicketMailer $mailer)
    {
    }

    public function create(array $data): GuestTicket
    {
        return $this->executeInTransaction(function () use ($data) {
            $ticket = GuestTicket::create([
                'name'    => $data['name'],
                'email'   => $data['email'],
                'mobile'  => $data['mobile'] ?? $data['phone'] ?? '',
                'subject' => $data['subject'] ?? ('Support request from ' . $data['name']),
                'body'    => $data['body'] ?? $data['description'] ?? '',
                'status'  => 'pending',
            ]);

            $messageId = $this->mailer->generateMessageId($ticket);
            $subject = $this->mailer->generateSubject($ticket, 'We received your request');

            GuestTicketMessage::create([
                'guest_ticket_id' => $ticket->id,
                'direction'       => GuestTicket::DIRECTION_OUTBOUND,
                'from_email'      => config('mail.from.address'),
                'to_email'        => $ticket->email,
                'subject'         => $subject,
                'body_text'       => 'Thank you for reaching out. We received your request and will reply shortly.',
                'message_id'      => $messageId,
                'sent_at'         => now(),
            ]);

            $ticket->update([
                'last_message_at'         => now(),
                'last_message_message_id' => $messageId,
            ]);

            $this->sendConfirmation($ticket, $messageId, $subject);
            $this->notifyAdmins($ticket);

            return $ticket;
        });
    }

    private function sendConfirmation(GuestTicket $ticket, string $messageId, string $subject): void
    {
        try {
            Mail::send(new GuestTicketConfirmationMail($ticket, $messageId, $subject));
        } catch (\Throwable $e) {
            Log::warning('Guest ticket confirmation mail failed', ['ticket_id' => $ticket->id, 'error' => $e->getMessage()]);
        }
    }

    private function notifyAdmins(GuestTicket $ticket): void
    {
        try {
            $admins = User::role(['admin', 'Admin'])->get();
            if ($admins->isNotEmpty()) {
                Notification::send($admins, new GuestTicketReplyNotification($ticket));
            }
        } catch (\Throwable $e) {
            Log::warning('Admin notification failed for guest ticket', ['ticket_id' => $ticket->id, 'error' => $e->getMessage()]);
        }
    }
}
