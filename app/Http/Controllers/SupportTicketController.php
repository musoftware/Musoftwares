<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Ticket;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        
        $query = Ticket::with(['user', 'conversation.messages.sender']);

        if (!$isAdmin) {
            $query->where('user_id', $user->id);
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
            // 1. Create the support ticket using legacy columns
            $ticket = Ticket::create([
                'user_id' => $user->id,
                'ticket_subject' => $validated['subject'],
                'ticket_message' => $validated['description'],
                'ticket_status' => 'open',
                'priority' => strtolower($validated['priority']),
            ]);

            // 2. Create the associated chat conversation (if using the new reply system)
            $conversation = Conversation::create([
                'conversable_type' => Ticket::class,
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

            DB::commit();

            return redirect()->back()->with('success', 'Support ticket opened successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to create ticket: ' . $e->getMessage()]);
        }
    }

    public function resolve($id)
    {
        $ticket = Ticket::findOrFail($id);

        // Authorize (only owner or admin)
        if (Auth::id() !== $ticket->user_id && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        DB::transaction(function () use ($ticket) {
            $ticket->update(['ticket_status' => 'resolved']);
            
            if ($ticket->conversation) {
                $ticket->conversation->update(['status' => 'closed']);
            }
        });

        return redirect()->back()->with('success', 'Ticket resolved.');
    }

    public function close($id)
    {
        $ticket = Ticket::findOrFail($id);

        if (Auth::id() !== $ticket->user_id && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        DB::transaction(function () use ($ticket) {
            $ticket->update(['ticket_status' => 'closed']);
            
            if ($ticket->conversation) {
                $ticket->conversation->update(['status' => 'closed']);
            }
        });

        return redirect()->back()->with('success', 'Ticket closed.');
    }

    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);

        if (Auth::id() !== $ticket->user_id && !Auth::user()->hasRole('admin')) {
            abort(403);
        }

        $ticket->delete();

        return redirect()->back()->with('success', 'Ticket deleted.');
    }
}
