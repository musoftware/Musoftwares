<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\User;
use App\Rules\Recaptcha;
use App\Services\GuestTicketCreator;
use App\Services\SupportDeskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportTicketController extends Controller
{
    public function __construct(protected SupportDeskService $supportDeskService) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : $user->hasRole(['admin', 'super_admin']);

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

    public function create(): Response
    {
        return Inertia::render('Client/Support/Tickets/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'priority' => 'required|in:Low,Medium,High',
            'description' => 'required|string',
        ]);

        $user = $request->user();
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : $user->hasRole(['admin', 'super_admin']);

        try {
            $ticket = $this->supportDeskService->createTicket($user, $validated, $isAdmin);

            return redirect()->route('tickets.show', $ticket->id)->with('success', __('general.support_ticket_opened_successfully'));
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => 'Failed to create ticket: '.$e->getMessage()]);
        }
    }

    public function show(Request $request, int|string $id): Response
    {
        $ticket = Ticket::with(['user', 'conversation.messages.sender'])->findOrFail($id);
        $user = $request->user();
        $isAdmin = $this->authorizeAccess($ticket, $user);

        return Inertia::render('Client/Support/Tickets/Show', [
            'ticket' => $ticket,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function resolve(Request $request, int|string $id): RedirectResponse
    {
        $ticket = Ticket::findOrFail($id);
        $this->authorizeAccess($ticket, $request->user());

        $this->supportDeskService->closeTicket($ticket);

        return redirect()->back()->with('success', __('general.ticket_resolved'));
    }

    public function close(Request $request, int|string $id): RedirectResponse
    {
        $ticket = Ticket::findOrFail($id);
        $this->authorizeAccess($ticket, $request->user());

        $this->supportDeskService->closeTicket($ticket);

        return redirect()->back()->with('success', __('general.ticket_closed'));
    }

    public function destroy(Request $request, int|string $id): RedirectResponse
    {
        $ticket = Ticket::findOrFail($id);
        $this->authorizeAccess($ticket, $request->user());

        $ticket->delete();

        return redirect()->back()->with('success', __('general.ticket_deleted'));
    }

    public function guestStore(Request $request, GuestTicketCreator $creator): RedirectResponse
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

    /**
     * Authorize user for ticket operations. Returns whether user is admin.
     */
    private function authorizeAccess(Ticket $ticket, ?User $user): bool
    {
        if (! $user) {
            abort(403, 'Unauthorized');
        }

        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : $user->hasRole(['admin', 'super_admin']);

        if ($user->id !== $ticket->user_id && ! $isAdmin) {
            abort(403, 'Unauthorized ticket access');
        }

        return $isAdmin;
    }
}

