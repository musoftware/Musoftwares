<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\SupportTicket;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Services\ActivityLogger;

class SupportTicketController extends Controller
{
    private function resolveTenant(): Tenant
    {
        return Tenant::where('user_id', Auth::id())->firstOrFail();
    }

    private function authorizeTenantTicket(SupportTicket $ticket)
    {
        $tenant = $this->resolveTenant();
        if ($ticket->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to support ticket.');
        }
    }

    public function store(Request $request)
    {
        $tenant = $this->resolveTenant();

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,urgent',
            'client_id' => 'nullable|exists:tenant_clients,id',
            'client_name' => 'nullable|string|max:255',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $clientId = $validated['client_id'] ?? null;

        // Auto-create/resolve client fallback
        if (!$clientId && $request->filled('client_name')) {
            $clientName = $request->input('client_name');
            $existingClient = TenantClient::where('tenant_id', $tenant->id)
                ->where('name', $clientName)
                ->first();

            if ($existingClient) {
                $clientId = $existingClient->id;
            } else {
                $user = Auth::user();
                $client = TenantClient::create([
                    'tenant_id' => $tenant->id,
                    'name' => $clientName,
                    'currency' => $user->preferred_currency ?? config('app.business_currency', 'USD'),
                    'status' => 'lead',
                ]);

                // Auto-create client wallet
                \Modules\ERP\Models\ClientWallet::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'client_id' => $client->id],
                    ['balance' => 0, 'currency' => $client->currency]
                );

                ActivityLogger::log(
                    'client_created',
                    "Client '{$client->name}' was auto-created during ticket creation.",
                    $client,
                    $client->id
                );

                $clientId = $client->id;
            }
        }

        if (!$clientId) {
            return back()->withErrors(['client_id' => 'A valid client or client name is required to create a ticket.']);
        }

        // Verify the client belongs to this tenant
        $client = TenantClient::where('tenant_id', $tenant->id)->findOrFail($clientId);

        $ticket = SupportTicket::create([
            'tenant_id' => $tenant->id,
            'client_id' => $clientId,
            'project_id' => $validated['project_id'] ?? null,
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'status' => 'open',
            'priority' => $validated['priority'],
            'created_by' => Auth::id(),
        ]);

        ActivityLogger::log(
            'support_ticket_created',
            "Support ticket '{$ticket->subject}' was created.",
            $ticket,
            $ticket->client_id
        );

        return back()->with('success', 'Support ticket created successfully.');
    }

    public function resolve(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTenantTicket($ticket);

        $ticket->update(['status' => 'resolved']);

        ActivityLogger::log(
            'support_ticket_resolved',
            "Support ticket '{$ticket->subject}' was marked as resolved.",
            $ticket,
            $ticket->client_id
        );

        return back()->with('success', 'Support ticket marked as resolved.');
    }

    public function close(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTenantTicket($ticket);

        $ticket->update(['status' => 'closed']);

        ActivityLogger::log(
            'support_ticket_closed',
            "Support ticket '{$ticket->subject}' was marked as closed.",
            $ticket,
            $ticket->client_id
        );

        return back()->with('success', 'Support ticket marked as closed.');
    }

    public function destroy(Request $request, SupportTicket $ticket)
    {
        $this->authorizeTenantTicket($ticket);

        $subject = $ticket->subject;
        $clientId = $ticket->client_id;
        $ticket->delete();

        ActivityLogger::log(
            'support_ticket_deleted',
            "Support ticket '{$subject}' was deleted.",
            null,
            $clientId
        );

        return back()->with('success', 'Support ticket deleted successfully.');
    }
}
