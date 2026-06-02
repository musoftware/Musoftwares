<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\SupportTicket;
use Inertia\Inertia;

class TicketController extends Controller
{
    private function getTenant()
    {
        $user = Auth::user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }
        return Tenant::where('user_id', $user->id)->first();
    }

    public function create()
    {
        $tenant = $this->getTenant();
        $user = $tenant ? $tenant->user : Auth::user();

        if (!$user || !$user->hasModuleSubscription('erp-tickets')) {
            abort(403, __('general.upgrade_to_enable_support_tickets'));
        }

        $tenant = $this->getTenant();
        $clients = $tenant ? $tenant->clients()->select('id', 'name')->get() : collect();

        return Inertia::render('ERP/Tickets/Create', [
            'clients' => $clients,
        ]);
    }

    public function store(Request $request)
    {
        $tenant = $this->getTenant();
        if (!$tenant) {
            return back()->withErrors(['error' => 'No active workspace found.']);
        }

        $user = $tenant->user;
        if (!$user || !$user->hasModuleSubscription('erp-tickets')) {
            abort(403, __('general.upgrade_to_enable_support_tickets'));
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'nullable|exists:erp_tenant_clients,id',
            'priority' => 'required|in:low,medium,high,critical',
        ]);

        $authUser = auth('erp_team')->check() ? auth('erp_team')->user() : Auth::user();

        SupportTicket::create([
            'tenant_id' => $tenant->id,
            'client_id' => $validated['client_id'] ?? null,
            'subject' => $validated['title'],
            'description' => $validated['description'] ?? '',
            'priority' => $validated['priority'],
            'status' => 'open',
            'created_by' => $authUser?->id,
        ]);

        return redirect()->route('erp.dashboard', ['section' => 'overview'])
            ->with('success', __('general.ticket_created_successfully'));
    }

    public function resolve(SupportTicket $ticket)
    {
        $tenant = $this->getTenant();
        if (!$tenant || $ticket->tenant_id !== $tenant->id) {
            abort(403);
        }

        $ticket->update(['status' => 'resolved']);
        return back()->with('success', __('general.ticket_resolved'));
    }

    public function close(SupportTicket $ticket)
    {
        $tenant = $this->getTenant();
        if (!$tenant || $ticket->tenant_id !== $tenant->id) {
            abort(403);
        }

        $ticket->update(['status' => 'closed']);
        return back()->with('success', __('general.ticket_closed'));
    }

    public function destroy(SupportTicket $ticket)
    {
        $tenant = $this->getTenant();
        if (!$tenant || $ticket->tenant_id !== $tenant->id) {
            abort(403);
        }

        $ticket->delete();
        return back()->with('success', __('general.ticket_deleted'));
    }
}
