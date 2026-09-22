<?php

use App\Models\Conversation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $tickets = Ticket::whereDoesntHave('conversation')->get();

        $adminUsers = rescue(fn () => User::role('admin')->get(), collect());

        foreach ($tickets as $ticket) {
            $conversation = Conversation::create([
                'conversable_type' => Ticket::class,
                'conversable_id' => $ticket->id,
                'type' => 'support_ticket',
                'status' => in_array($ticket->ticket_status, ['closed', 'resolved']) ? 'closed' : 'open',
                'created_at' => $ticket->created_at ?? now(),
                'updated_at' => $ticket->updated_at ?? now(),
            ]);

            if ($ticket->user_id) {
                $conversation->participants()->firstOrCreate([
                    'user_id' => $ticket->user_id,
                    'role' => 'client',
                ]);
            }

            foreach ($adminUsers as $admin) {
                if ($admin->id !== $ticket->user_id) {
                    $conversation->participants()->firstOrCreate([
                        'user_id' => $admin->id,
                        'role' => 'admin',
                    ]);
                }
            }

            if (! empty($ticket->ticket_message)) {
                $conversation->messages()->create([
                    'sender_id' => $ticket->user_id,
                    'body' => $ticket->ticket_message,
                    'is_system' => false,
                    'created_at' => $ticket->created_at ?? now(),
                    'updated_at' => $ticket->created_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Safe no-op to prevent data loss
    }
};
