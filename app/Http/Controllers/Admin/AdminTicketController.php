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
        $filters = $request->only(['status', 'priority', 'search', 'sort', 'direction']);

        $tickets = $this->supportDeskService->getTickets($filters)
                        ->withQueryString()
                        ->through(fn($t) => (new TicketResource($t))->resolve());

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

        $supportAgents = \App\Models\User::role(['admin', 'moderator'])->get(['id', 'name', 'avatar']);
        $cannedResponses = \App\Models\TicketCannedResponse::all();

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => (new TicketResource($ticket))->resolve(),
            'supportAgents' => $supportAgents,
            'cannedResponses' => $cannedResponses,
        ]);
    }

    public function update(Request $request, Ticket $ticket)
    {
        $request->validate([
            'action' => 'required|in:close,reopen',
            'comment' => 'nullable|string',
        ]);

        if ($request->action === 'close') {
            if ($request->filled('comment')) {
                $this->supportDeskService->replyToTicket($ticket, Auth::id(), $request->comment, null, false);
            }
            $this->supportDeskService->closeTicket($ticket);
            $message = 'Ticket closed successfully.';
        } else {
            $ticket->reopen();

            if ($ticket->conversation) {
                $ticket->conversation->update(['status' => 'open']);
            }

            $message = 'Ticket reopened successfully.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function reply(Request $request, Ticket $ticket)
    {
        $request->validate([
            'body' => 'required_without:attachments|string|nullable',
            'attachments.*' => 'nullable|file|max:10240', // 10MB max
            'is_internal' => 'boolean',
        ]);

        $isInternal = $request->boolean('is_internal', false);
        $attachments = $request->file('attachments') ?? [];
        $body = $request->input('body') ?? 'Attached File(s)';

        // First message contains the body and the first attachment (if any)
        $firstAttachment = count($attachments) > 0 ? $attachments[0]->store('tickets/attachments', 'public') : null;

        $this->supportDeskService->replyToTicket(
            $ticket,
            Auth::id(),
            $body,
            $firstAttachment,
            $isInternal
        );

        // If there are more attachments, send them as separate messages
        if (count($attachments) > 1) {
            for ($i = 1; $i < count($attachments); $i++) {
                $path = $attachments[$i]->store('tickets/attachments', 'public');
                $this->supportDeskService->replyToTicket(
                    $ticket,
                    Auth::id(),
                    'Additional Attachment',
                    $path,
                    $isInternal
                );
            }
        }

        return redirect()->back()->with('success', 'Reply sent successfully.');
    }

    public function assign(Request $request, Ticket $ticket)
    {
        $request->validate([
            'assigned_employee_id' => 'required|exists:users,id',
        ]);

        $ticket->update([
            'assigned_employee_id' => $request->assigned_employee_id,
        ]);

        return redirect()->back()->with('success', 'Ticket assigned successfully.');
    }

    public function addCannedResponse(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        \App\Models\TicketCannedResponse::create([
            'user_id' => Auth::id(),
            'title' => $request->title,
            'body' => $request->body,
        ]);

        return redirect()->back()->with('success', 'Canned response added successfully.');
    }
}
