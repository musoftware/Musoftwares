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
    private function resolveTenant(): Tenant
    {
        if (Auth::guard('erp_team')->check()) {
            return Auth::guard('erp_team')->user()->tenant;
        }
        return auth('erp_team')->user()->tenant;
    }

    /**
     * Advanced index data table for clients.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', TenantClient::class);
        $tenantId = $this->resolveTenant()->id;

        $query = TenantClient::where('tenant_id', $tenantId)->with('currency')->withCount('invoices');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $sortable  = ['name', 'email', 'created_at', 'id'];
        $sort      = in_array($request->get('sort'), $sortable) ? $request->get('sort') : 'id';
        $direction = $request->get('direction', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction);

        $clients = $query->paginate(25)->withQueryString()->through(function ($client) {
            $unpaid = Invoice::where('client_id', $client->id)
                ->whereIn('status', ['sent', 'partial'])
                ->sum('amount');
            $totalPaid = Invoice::where('client_id', $client->id)
                ->where('status', 'paid')
                ->sum('amount');

            return [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email ?? '-',
                'phone' => $client->phone ?? '-',
                'address' => $client->address ?? '-',
                'currency' => $client->currency,
                'balance' => round($client->balance(), 2),
                'unpaid' => round($unpaid, 2),
                'totalPaid' => round($totalPaid, 2),
                'invoices_count' => $client->invoices_count,
                'status' => $client->status,
                'created_at' => $client->created_at?->format('Y-m-d'),
                'avatar_url' => $client->avatar_url,
            ];
        });

        $stats = [
            'total'           => TenantClient::where('tenant_id', $tenantId)->count(),
            'active'          => TenantClient::where('tenant_id', $tenantId)->whereIn('status', ['active', 'paying', 'retained'])->count(),
            'leads'           => TenantClient::where('tenant_id', $tenantId)->where('status', 'lead')->count(),
            'new_this_month'  => TenantClient::where('tenant_id', $tenantId)->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        return Inertia::render('ERP/Clients/Index', [
            'clients' => $clients,
            'filters' => $request->only(['search', 'status', 'sort', 'direction']),
            'stats'   => $stats,
        ]);
    }

    /**
     * Show the overview tab for a specific client.
     */
    public function show(TenantClient $client)
    {
        $this->authorize('view', $client);

        $user = auth('erp_team')->user();
        $hasTickets = $user->hasModuleSubscription('erp-tickets');

        $baseData = $this->clientService->getClientBaseData($client, $hasTickets);

        $invoices = Invoice::where('client_id', $client->id)->latest()->get();
        $projects = $client->projects;
        $tickets = $hasTickets ? $client->tickets : [];
        $activities = Activity::where('subject_type', TenantClient::class)
            ->where('subject_id', $client->id)
            ->with('causer')
            ->latest()
            ->get();

        return Inertia::render('ERP/Clients/Show', array_merge($baseData, [
            'invoices' => $invoices,
            'projects' => $projects,
            'tickets' => $tickets,
            'activities' => ActivityResource::collection($activities)->resolve(),
        ]));
    }

    /**
     * Show the transactions tab for a specific client.
     */
    public function transactions(TenantClient $client)
    {
        $this->authorize('view', $client);

        $user = auth('erp_team')->user();
        $hasTickets = $user->hasModuleSubscription('erp-tickets');

        $baseData = $this->clientService->getClientBaseData($client, $hasTickets);

        $transactions = \Modules\ERP\Models\WalletTransaction::where('client_id', $client->id)
            ->with(['creator', 'currency'])
            ->latest()
            ->get();

        return Inertia::render('ERP/Clients/Transactions', array_merge($baseData, [
            'transactions' => $transactions,
        ]));
    }

    /**
     * Show the files tab for a specific client.
     */
    public function files(TenantClient $client)
    {
        $this->authorize('view', $client);

        $user = auth('erp_team')->user();
        $hasTickets = $user->hasModuleSubscription('erp-tickets');

        $baseData = $this->clientService->getClientBaseData($client, $hasTickets);

        $files = \Modules\ERP\Models\TenantFile::where('tenant_id', $client->tenant_id)
            ->where('folder', 'client_' . $client->id)
            ->with('uploader')
            ->latest()
            ->get();

        return Inertia::render('ERP/Clients/Files', array_merge($baseData, [
            'files' => $files,
        ]));
    }

    /**
     * Show the notes tab for a specific client.
     */
    public function notes(TenantClient $client)
    {
        $this->authorize('view', $client);

        $user = auth('erp_team')->user();
        $hasTickets = $user->hasModuleSubscription('erp-tickets');

        $baseData = $this->clientService->getClientBaseData($client, $hasTickets);

        $notes = \Modules\ERP\Models\ClientNote::where('client_id', $client->id)
            ->latest()
            ->get();

        return Inertia::render('ERP/Clients/Notes', array_merge($baseData, [
            'notes' => $notes,
        ]));
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
        $user = auth('erp_team')->user();
        $tenant = auth('erp_team')->user()->tenant;
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
        $this->authorize('create', TenantClient::class);
        $tenant = $this->resolveTenant();

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
        $tenantId = $this->resolveTenant()->id;
        $limit = min((int) $request->input('limit', 20), 50);
        $term = $request->q;

        $clients = $this->clientService->searchClients($tenantId, $term, $limit);

        return response()->json(ClientSearchResource::collection($clients)->resolve());
    }

    public function destroy(TenantClient $client)
    {
        $this->authorize('delete', $client);

        $this->clientService->deleteClient($client, $this->resolveTenant()->id);

        return back()->with('success', __('erp.client_deleted_success'));
    }
}
