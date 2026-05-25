<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

class SupportDeskService
{
    public function getTickets(array $filters, int $perPage = 15)
    {
        $query = Ticket::with('user')->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            if ($filters['status'] === 'closed') {
                $query->where('ticket_status', 'closed');
            } else {
                $query->where('ticket_status', $filters['status']);
            }
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->paginate($perPage);
    }

    public function replyToTicket(Ticket $ticket, int $adminId, string $body, ?string $attachment = null): Message
    {
        return DB::transaction(function () use ($ticket, $adminId, $body, $attachment) {
            $conversation = $ticket->conversation;
            
            if (!$conversation) {
                // If it doesn't have a conversation yet (legacy ticket), create one
                $conversation = $ticket->conversation()->create([]);
            }

            $message = $conversation->messages()->create([
                'sender_id'  => $adminId,
                'body'       => $body,
                'attachment' => $attachment,
                'is_system'  => false,
            ]);

            // Update ticket status
            $ticket->update([
                'ticket_status' => 'agent_replied',
            ]);

            return $message;
        });
    }

    public function closeTicket(Ticket $ticket): void
    {
        $ticket->close();
    }
    
    public function getTicketStats(): array
    {
        return [
            'total'         => Ticket::count(),
            'open'          => Ticket::where('ticket_status', 'open')->count(),
            'waiting'       => Ticket::where('ticket_status', 'user_replied')->count(),
            'agent_replied' => Ticket::where('ticket_status', 'agent_replied')->count(),
            'closed'        => Ticket::where('ticket_status', 'closed')->count(),
        ];
    }
}
