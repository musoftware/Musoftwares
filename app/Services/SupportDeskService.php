<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;

class SupportDeskService extends BaseService
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

    public function replyToTicket(Ticket $ticket, int $adminId, string $body, ?string $attachment = null, bool $isInternal = false): Message
    {
        return $this->executeInTransaction(function () use ($ticket, $adminId, $body, $attachment, $isInternal) {
            $conversation = $ticket->conversation;
            
            if (!$conversation) {
                // If it doesn't have a conversation yet (legacy ticket), create one
                $conversation = $ticket->conversation()->create([
                    'type' => 'support_ticket',
                    'status' => 'open'
                ]);
            }

            $message = $conversation->messages()->create([
                'sender_id'  => $adminId,
                'body'       => $body,
                'attachment' => $attachment,
                'is_system'  => false,
                'is_internal'=> $isInternal,
            ]);

            // Update ticket status only if not internal note
            if (!$isInternal) {
                $ticket->update([
                    'ticket_status' => 'agent_replied',
                ]);
            }

            return $message;
        });
    }

    public function closeTicket(Ticket $ticket): void
    {
        $this->executeInTransaction(function () use ($ticket) {
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

    public function createTicket(\App\Models\User $user, array $data, bool $isAdmin = false): Ticket
    {
        return $this->executeInTransaction(function () use ($user, $data, $isAdmin) {
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'ticket_subject' => $data['subject'],
                'ticket_message' => $data['description'],
                'ticket_status' => 'open',
                'priority' => strtolower($data['priority']),
            ]);

            $conversation = Conversation::create([
                'conversable_type' => Ticket::class,
                'conversable_id' => $ticket->id,
                'type' => 'support_ticket',
                'status' => 'open',
            ]);

            $conversation->participants()->create([
                'user_id' => $user->id,
                'role' => $isAdmin ? 'admin' : 'client',
            ]);

            $conversation->messages()->create([
                'sender_id' => $user->id,
                'body' => $data['description'],
                'is_system' => false,
            ]);

            $admins = \App\Models\User::role('admin')->get();
            foreach ($admins as $admin) {
                if ($admin->id !== $user->id) {
                    $conversation->participants()->create([
                        'user_id' => $admin->id,
                        'role' => 'admin',
                    ]);
                }
            }

            return $ticket;
        });
    }

    public function createGuestTicket(array $data): Ticket
    {
        return $this->executeInTransaction(function () use ($data) {
            $message = $data['description'] . "\n\nرقم الهاتف: " . $data['phone'];

            $ticket = Ticket::create([
                'user_id' => null,
                'anonymous_name' => $data['name'],
                'anonymous_email' => $data['email'],
                'ticket_subject' => 'طلب خدمة حصرية',
                'ticket_message' => $message,
                'ticket_status' => 'open',
                'priority' => 'high',
            ]);

            $admins = \App\Models\User::role(['admin', 'Admin'])->get();
            \Illuminate\Support\Facades\Notification::send($admins, new \App\Notifications\NewGuestTicketNotification($ticket));

            try {
                $messaging = app('firebase.messaging');
                $notification = \Kreait\Firebase\Messaging\Notification::create(
                    'طلب خدمة حصرية جديد',
                    'طلب جديد من ' . $data['name']
                );

                $tokens = $admins->whereNotNull('fcm_token')->pluck('fcm_token')->toArray();
                if (!empty($tokens)) {
                    $messageObj = \Kreait\Firebase\Messaging\CloudMessage::new()
                        ->withNotification($notification)
                        ->withData([
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                            'url' => route('admin.tickets.show', $ticket->id)
                        ]);
                    $messaging->sendMulticast($messageObj, $tokens);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Firebase Notification Failed: ' . $e->getMessage());
            }

            return $ticket;
        });
    }
}
