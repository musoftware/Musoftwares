<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SupportTicket;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        $query = SupportTicket::with(['tenantClient', 'platformClient', 'conversation.messages.sender']);

        if (!$isAdmin) {
            $query->where('client_id', $user->id);
        }

        $tickets = $query->latest()->paginate(15);

        return Inertia::render('Support/Tickets/Index', [
            'tickets' => $tickets,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:Low,Medium,High',
            'description' => 'required|string',
        ]);

        $user = Auth::user();

        DB::beginTransaction();

        try {
            // 1. Create the support ticket
            $ticket = SupportTicket::create([
                'client_id' => $user->id,
                'subject' => $validated['subject'],
                'status' => 'open',
                'priority' => strtolower($validated['priority']),
            ]);

            // 2. Create the associated chat conversation
            $conversation = Conversation::create([
                'conversable_type' => SupportTicket::class,
                'conversable_id' => $ticket->id,
                'type' => 'support_ticket',
                'status' => 'open',
            ]);

            // Add the user as a participant
            $conversation->participants()->create([
                'user_id' => $user->id,
                'role' => 'client',
            ]);

            // Add all admin users as participants so they can view and reply
            $admins = \App\Models\User::role('admin')->get();
            foreach ($admins as $admin) {
                $conversation->participants()->create([
                    'user_id' => $admin->id,
                    'role' => 'admin',
                ]);
            }

            // 3. Create the description as the first message
            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $user->id,
                'body' => $validated['description'],
            ]);

            DB::commit();

            return redirect()->back()->with('success', 'Support ticket opened successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to create ticket: ' . $e->getMessage()]);
        }
    }

    public function resolve($id)
    {
        $ticket = SupportTicket::findOrFail($id);

        // Authorize (only owner or admin)
        if (Auth::id() !== $ticket->client_id && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        DB::transaction(function () use ($ticket) {
            $ticket->update(['status' => 'resolved']);
            
            if ($ticket->conversation) {
                $ticket->conversation->update(['status' => 'closed']);
            }
        });

        return redirect()->back()->with('success', 'Ticket resolved.');
    }
}

