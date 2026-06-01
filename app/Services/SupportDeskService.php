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
        $query = Ticket::with('user');

        // Search
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('ticket_subject', 'like', "%{$search}%")
                  ->orWhere('ticket_message', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                                                    ->orWhere('email', 'like', "%{$search}%"));
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('ticket_status', $filters['status']);
        }

        // Priority filter
        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        // Dynamic sort
        $allowedSorts = ['id', 'created_at', 'ticket_subject', 'priority', 'ticket_status'];
        $sort      = in_array($filters['sort'] ?? '', $allowedSorts) ? $filters['sort'] : 'created_at';
        $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction);

        return $query->paginate($perPage);
    }

    public function replyToTicket(Ticket $ticket, int $adminId, string $body, ?string $attachment = null): Message
    {
        return DB::transaction(function () use ($ticket, $adminId, $body, $attachment) {
            $conversation = $ticket->conversation;
            
            if (!$conversation) {
                // If it doesn't have a conversation yet (legacy ticket), create one
                $conversation = $ticket->conversation()->create([
                    'type' => 'ticket',
                    'status' => 'active'
                ]);
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
        DB::transaction(function () use ($ticket) {
            $ticket->close(); // sets ticket_status=closed and closed_at=now()

            if ($ticket->conversation) {
                $ticket->conversation->update(['status' => 'closed']);
            }
        });
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
