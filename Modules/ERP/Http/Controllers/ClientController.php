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
        $user = Auth::user();

        // Ensure the client belongs to the active tenant
        if ($client->tenant_id !== $tenantId) {
            abort(403, __('erp.unauthorized_client_access'));
        }

        // Load base relationships
        $client->load(['projects', 'currency']);

        // Conditionally load tickets if addon is active
        $hasTickets = $user->hasModuleSubscription('erp-tickets');
        if ($hasTickets) {
            $client->load('tickets');
        }

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
            'tickets' => $hasTickets ? $client->tickets : [],
            'invoices' => $invoices,
            'activities' => $activities,
            'hasTickets' => $hasTickets,
            'balance' => $client->balance(),
            'lockedBalance' => $client->lockedBalance(),
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

        return back()->with('success', __('erp.client_status_updated'));
    }

    public function create()
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();
        $hasMultiCurrency = $user->hasModuleSubscription('erp-multi-currency');
        $baseCurrency = $tenant?->baseCurrency?->currency;

        return Inertia::render('ERP/Clients/Create', [
            'currencies' => $hasMultiCurrency ? \App\Models\Currency::all() : [],
            'tenant' => $tenant,
            'hasMultiCurrency' => $hasMultiCurrency,
            'baseCurrency' => $baseCurrency,
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
            'currency' => 'nullable|string|size:3',
        ]);

        // If no currency provided (no multi-currency addon), use tenant's base currency
        $currencyCode = $validated['currency'] ?? null;
        if (!$currencyCode) {
            $currencyCode = $tenant->baseCurrency?->currency;
        }
        $currencyId = \App\Models\Currency::where('currency', $currencyCode)->value('id');

        $client = TenantClient::create([
            'tenant_id' => $tenant->id,
            'name'      => $validated['name'],
            'email'     => $validated['email'] ?? null,
            'phone'     => $validated['phone'] ?? null,
            'address'   => $validated['address'] ?? null,
            'currency_id' => $currencyId,
        ]);

        ActivityLogger::log(
            'client_created',
            "Client '{$client->name}' was added.",
            $client,
            $client->id
        );

        return redirect()->route('erp.dashboard', ['section' => 'clients'])->with('success', __('erp.client_created_success'));
    }

    public function edit(TenantClient $client)
    {
        $tenantId = $this->resolveTenantId();

        if ($client->tenant_id !== $tenantId) {
            abort(403, __('erp.unauthorized_client_access'));
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
            abort(403, __('erp.unauthorized_client_access'));
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

        return redirect()->route('erp.dashboard', ['section' => 'clients'])->with('success', __('erp.client_updated_success'));
    }

    /**
     * JSON search endpoint for async client combobox.
     * GET /erp/clients/search?q=term&limit=20
     */
    public function search(Request $request)
    {
        $tenantId = $this->resolveTenantId();
        $query = TenantClient::with('currency')
            ->where('tenant_id', $tenantId)
            ->select('id', 'name', 'email', 'currency_id');

        if ($request->filled('q')) {
            $term = $request->q;
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%");
            });
        }

        $limit = min((int) $request->input('limit', 20), 50);

        $clients = $query->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'email' => $c->email,
                'currency_code' => $c->currency?->currency,
            ]);

        return response()->json($clients);
    }

    public function destroy(TenantClient $client)
    {
        $tenantId = $this->resolveTenantId();

        if ($client->tenant_id !== $tenantId) {
            abort(403, __('erp.unauthorized_client_access'));
        }

        $hasOpenInvoices = $client->invoices()->whereNotIn('status', ['cancelled', 'paid'])->exists();
        if ($hasOpenInvoices) {
            return back()->withErrors(['client' => __('erp.cannot_delete_client_open_invoices')]);
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

        return back()->with('success', __('erp.client_deleted_success'));
    }
}
