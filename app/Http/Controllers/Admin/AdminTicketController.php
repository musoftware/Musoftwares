<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\SupportDeskService;
use App\Http\Resources\TicketResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AdminTicketController extends Controller
{
    public function __construct(
        protected SupportDeskService $supportDeskService
    ) {}

    public function index(Request $request)
    {
        $filters = $request->only(['status', 'priority']);
        
        $tickets = $this->supportDeskService->getTickets($filters)
                        ->withQueryString()
                        ->through(fn($t) => clone (new TicketResource($t))->resolve());

        $stats = $this->supportDeskService->getTicketStats();

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
            'filters' => $filters,
            'stats'   => $stats,
        ]);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['user', 'conversation.messages.sender']);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => clone (new TicketResource($ticket))->resolve(),
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $request->validate([
            'action' => 'required|in:close,reopen',
        ]);

        if ($request->action === 'close') {
            $this->supportDeskService->closeTicket($ticket);
            $message = 'Ticket closed successfully.';
        } else {
            $ticket->update(['ticket_status' => 'open']);
            $message = 'Ticket reopened successfully.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $request->validate([
            'body' => 'required|string',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('tickets/attachments', 'public');
        }

        $this->supportDeskService->replyToTicket(
            $ticket,
            Auth::id(),
            $request->body,
            $attachmentPath
        );

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }
}
