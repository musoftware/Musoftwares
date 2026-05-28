<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\Project;
use App\Models\Ticket;
use Modules\ERP\Models\Activity;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Services\ActivityLogger;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClientController extends Controller
{
    private function resolveTenantId(): int
    {
        return Tenant::where('user_id', Auth::id())->firstOrFail()->id;
    }

    /**
     * Show the complete operational workflow for a specific client.
     */
    public function show(TenantClient $client)
    {
        $tenantId = $this->resolveTenantId();

        // Ensure the client belongs to the active tenant
        if ($client->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to client.');
        }

        // Load relationships
        $client->load(['projects', 'tickets']);

        // Fetch related operational data
        $invoices = Invoice::where('client_id', $client->id)->latest()->get();
        $activities = Activity::where('subject_type', TenantClient::class)
            ->where('subject_id', $client->id)
            ->with('causer')
            ->latest()
            ->get()
            ->map(function ($activity) {
                return [
                    'title' => $activity->action,
                    'time' => $activity->created_at?->diffForHumans(),
                    'description' => $activity->description,
                    'user' => $activity->causer?->name ?? 'System',
                ];
            });

        return Inertia::render('ERP/Clients/Show', [
            'client' => $client,
            'projects' => $client->projects,
            'tickets' => $client->tickets,
            'invoices' => $invoices,
            'activities' => $activities,
        ]);
    }

    /**
     * Update client status (e.g., Lead -> Active -> Retained)
     */
    public function updateStatus(Request $request, TenantClient $client)
    {
        $tenantId = $this->resolveTenantId();

        if ($client->tenant_id !== $tenantId) {
            abort(403);
        }

        $request->validate([
            'status' => 'required|in:lead,active,paying,retained,archived'
        ]);

        $oldStatus = $client->status;
        $client->update(['status' => $request->status]);

        ActivityLogger::log(
            'client_status_changed',
            "Client status changed from {$oldStatus} to {$client->status}.",
            $client,
            $client->id
        );

        return back()->with('success', 'Client status updated.');
    }

    public function create()
    {
        return Inertia::render('ERP/Clients/Create', [
            'currencies' => \App\Models\Currency::all(),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $tenant = Tenant::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name . "'s Workspace", 'status' => 'active']
        );

        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'nullable|email|max:255',
            'phone'    => 'nullable|string|max:20',
            'address'  => 'nullable|string|max:255',
            'currency' => 'required|string|size:3',
            'status'   => 'nullable|in:lead,active,paying,retained,archived',
        ]);

        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name'      => $validated['name'],
            'email'     => $validated['email'] ?? null,
            'phone'     => $validated['phone'] ?? null,
            'address'   => $validated['address'] ?? null,
            'currency_id' => \App\Models\Currency::where('currency', $validated['currency'])->value('id'),
            'status'    => $validated['status'] ?? 'lead',
        ]);

        // Auto-create client wallet
        \Modules\ERP\Models\ClientWallet::firstOrCreate(
            ['tenant_id' => $tenant->id, 'client_id' => $client->id],
            ['balance' => 0, 'currency_id' => $client->currency_id]
        );

        ActivityLogger::log(
            'client_created',
            "Client '{$client->name}' was added.",
            $client,
            $client->id
        );

        return redirect()->route('erp.dashboard', ['section' => 'clients'])->with('success', 'Client created successfully.');
    }

    public function edit(TenantClient $client)
    {
        $tenantId = $this->resolveTenantId();

        if ($client->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to client.');
        }

        return Inertia::render('ERP/Clients/Edit', [
            'client' => $client->load('currency'),
            'currencies' => \App\Models\Currency::all(),
        ]);
    }

    public function update(Request $request, TenantClient $client)
    {
        $tenantId = $this->resolveTenantId();

        if ($client->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to client.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'currency' => 'required|string|size:3',
        ]);

        $validated['currency_id'] = \App\Models\Currency::where('currency', $validated['currency'])->value('id');
        unset($validated['currency']);

        $client->update($validated);

        ActivityLogger::log(
            'client_updated',
            "Client '{$client->name}' profile was updated.",
            $client,
            $client->id
        );

        return redirect()->route('erp.dashboard', ['section' => 'clients'])->with('success', 'Client updated successfully.');
    }

    public function destroy(TenantClient $client)
    {
        $tenantId = $this->resolveTenantId();

        if ($client->tenant_id !== $tenantId) {
            abort(403, 'Unauthorized access to client.');
        }

        $hasOpenInvoices = $client->invoices()->whereNotIn('status', ['cancelled', 'paid'])->exists();
        if ($hasOpenInvoices) {
            return back()->withErrors(['client' => 'Cannot delete a client with open invoices. Cancel or pay them first.']);
        }

        $clientName = $client->name;
        $client->delete();

        ActivityLogger::log(
            'client_deleted',
            "Client '{$clientName}' was deleted.",
            null,
            null,
            ['tenant_id' => $tenantId]
        );

        return back()->with('success', 'Client deleted successfully.');
    }
}
