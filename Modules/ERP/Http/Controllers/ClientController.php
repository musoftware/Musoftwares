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
use Modules\ERP\Transformers\ActivityResource;
use Modules\ERP\Transformers\ClientSearchResource;
use Modules\ERP\Http\Requests\StoreClientRequest;
use Modules\ERP\Http\Requests\UpdateClientRequest;
use Modules\ERP\Services\ClientService;

class ClientController extends Controller
{
    protected $clientService;

    public function __construct(ClientService $clientService)
    {
        $this->clientService = $clientService;
    }
    private function resolveTenantId(): int
    {
        return Tenant::where('user_id', Auth::id())->firstOrFail()->id;
    }

    /**
     * Show the complete operational workflow for a specific client.
     */
    public function show(TenantClient $client)
    {
        $this->authorize('view', $client);

        $user = Auth::user();
        $hasTickets = $user->hasModuleSubscription('erp-tickets');

        $data = $this->clientService->getOperationalData($client, $hasTickets);

        return Inertia::render('ERP/Clients/Show', [
            'client' => $data['client'],
            'projects' => $data['projects'],
            'tickets' => $data['tickets'],
            'invoices' => $data['invoices'],
            'activities' => ActivityResource::collection($data['activities'])->resolve(),
            'hasTickets' => $hasTickets,
            'balance' => $data['balance'],
            'lockedBalance' => $data['lockedBalance'],
        ]);
    }

    /**
     * Update client status (e.g., Lead -> Active -> Retained)
     */
    public function updateStatus(Request $request, TenantClient $client)
    {
        $this->authorize('update', $client);

        $request->validate([
            'status' => 'required|in:lead,active,paying,retained,archived'
        ]);

        $this->clientService->updateStatus($client, $request->status);

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

    public function store(StoreClientRequest $request)
    {
        $user = Auth::user();
        $tenant = Tenant::firstOrCreate(
            ['user_id' => $user->id],
            ['name' => $user->name . "'s Workspace", 'status' => 'active']
        );

        $this->clientService->createClient($request->validated(), $tenant);

        return redirect()->route('erp.dashboard', ['section' => 'clients'])->with('success', __('erp.client_created_success'));
    }

    public function edit(TenantClient $client)
    {
        $this->authorize('update', $client);

        return Inertia::render('ERP/Clients/Edit', [
            'client' => $client->load('currency'),
            'currencies' => \App\Models\Currency::all(),
        ]);
    }

    public function update(UpdateClientRequest $request, TenantClient $client)
    {
        $this->authorize('update', $client);

        $this->clientService->updateClient($client, $request->validated());

        return redirect()->route('erp.dashboard', ['section' => 'clients'])->with('success', __('erp.client_updated_success'));
    }

    /**
     * JSON search endpoint for async client combobox.
     * GET /erp/clients/search?q=term&limit=20
     */
    public function search(Request $request)
    {
        $tenantId = $this->resolveTenantId();
        $limit = min((int) $request->input('limit', 20), 50);
        $term = $request->q;

        $clients = $this->clientService->searchClients($tenantId, $term, $limit);

        return response()->json(ClientSearchResource::collection($clients)->resolve());
    }

    public function destroy(TenantClient $client)
    {
        $this->authorize('delete', $client);

        $this->clientService->deleteClient($client, $this->resolveTenantId());

        return back()->with('success', __('erp.client_deleted_success'));
    }
}
