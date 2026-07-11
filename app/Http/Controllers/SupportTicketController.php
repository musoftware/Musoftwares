<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Rules\Recaptcha;
use App\Services\GuestTicketCreator;
use App\Services\SupportDeskService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SupportTicketController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isAdmin = $user->isAdmin();

        $query = Ticket::with(['user', 'conversation.messages.sender']);

        if (! $isAdmin) {
            $query->where('user_id', $user->id);
        }

        $tickets = $query->latest()->paginate(15);

        return Inertia::render('Client/Support/Tickets/Index', [
            'tickets' => $tickets,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function store(Request $request, SupportDeskService $service)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:Low,Medium,High',
            'description' => 'required|string',
        ]);

        try {
            $service->createTicket(Auth::user(), $validated, Auth::user()->isAdmin());

            return redirect()->back()->with('success', __('general.support_ticket_opened_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to create ticket: '.$e->getMessage()]);
        }
    }

    public function resolve($id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        // Authorize (only owner or admin)
        $isAdmin = $user && $user->isAdmin();
        if (! $user || ($user->id !== $ticket->user_id && ! $isAdmin)) {
            abort(403);
        }

        app(SupportDeskService::class)->closeTicket($ticket);

        return redirect()->back()->with('success', __('general.ticket_resolved'));
    }

    public function close($id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        $isAdmin = $user && $user->isAdmin();
        if (! $user || ($user->id !== $ticket->user_id && ! $isAdmin)) {
            abort(403);
        }

        app(SupportDeskService::class)->closeTicket($ticket);

        return redirect()->back()->with('success', __('general.ticket_closed'));
    }

    public function destroy($id)
    {
        $ticket = Ticket::findOrFail($id);
        $user = Auth::user();

        $isAdmin = $user && $user->isAdmin();
        if (! $user || ($user->id !== $ticket->user_id && ! $isAdmin)) {
            abort(403);
        }

        $ticket->delete();

        return redirect()->back()->with('success', __('general.ticket_deleted'));
    }

    public function guestStore(Request $request, GuestTicketCreator $creator)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'description' => 'required|string',
            'subject' => 'nullable|string|max:255',
            'g-recaptcha-response' => ['required', new Recaptcha],
        ], [
            'g-recaptcha-response.required' => __('general.recaptcha_required') ?? 'يرجى التحقق من الكابتشا (Google reCAPTCHA).',
        ]);

        try {
            $creator->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['phone'],
                'body' => $validated['description'],
                'subject' => $validated['subject'] ?? null,
            ]);

            return redirect()->back()->with('success', 'تم ارسال الطلب بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to create ticket: '.$e->getMessage()]);
        }
    }
}
