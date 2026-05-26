<?php

namespace Modules\PaymentGateway\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Modules\PaymentGateway\Models\GatewayClient;
use Modules\PaymentGateway\Models\GatewayPayment;
use Modules\PaymentGateway\Services\PaymentGatewayService;
use Modules\PaymentGateway\Http\Requests\StoreGatewayClientRequest;
use Modules\PaymentGateway\Http\Requests\UpdateGatewayClientRequest;
use Modules\PaymentGateway\Http\Resources\GatewayClientResource;
use Modules\PaymentGateway\Http\Resources\GatewayPaymentResource;

class AdminGatewayClientController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $service
    ) {}

    /**
     * List all gateway clients with quick stats.
     */
    public function index()
    {
        $clients = GatewayClient::withCount('payments')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn($c) => (new GatewayClientResource($c))->resolve());

        // Platform-wide totals for the header stats cards
        $totals = [
            'total_clients'    => GatewayClient::count(),
            'active_clients'   => GatewayClient::where('status', 'active')->count(),
            'total_volume'     => (float) GatewayPayment::where('status', 'success')->sum('amount'),
            'total_commission' => (float) GatewayPayment::where('status', 'success')->sum('commission_amount'),
            'total_payments'   => GatewayPayment::where('status', 'success')->count(),
        ];

        return Inertia::render('Admin/PaymentGateway/Index', [
            'clients' => $clients,
            'totals'  => $totals,
        ]);
    }

    /**
     * Show a single client with their payment history.
     */
    public function show(GatewayClient $gatewayClient)
    {
        $payments = GatewayPayment::where('client_id', $gatewayClient->id)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->through(fn($p) => (new GatewayPaymentResource($p))->resolve());

        $stats = $this->service->getClientStats($gatewayClient);

        return Inertia::render('Admin/PaymentGateway/Show', [
            'client'   => (new GatewayClientResource($gatewayClient))->resolve(),
            'payments' => $payments,
            'stats'    => $stats,
        ]);
    }

    /**
     * Create a new gateway client.
     */
    public function store(StoreGatewayClientRequest $request)
    {
        $this->service->createClient($request->validated());

        return redirect()
            ->route('admin.musoftware-clients.index')
            ->with('success', 'Client created successfully.');
    }

    /**
     * Update a gateway client.
     */
    public function update(UpdateGatewayClientRequest $request, GatewayClient $gatewayClient)
    {
        $this->service->updateClient($gatewayClient, $request->validated());

        return redirect()->back()->with('success', 'Client updated successfully.');
    }

    /**
     * Regenerate client_secret + webhook_secret.
     */
    public function regenerateSecret(GatewayClient $gatewayClient)
    {
        $this->service->regenerateClientSecret($gatewayClient);

        return redirect()->back()->with('success', 'Secrets regenerated. Make sure to update your integration.');
    }

    /**
     * Delete a client (only if no successful payments).
     */
    public function destroy(GatewayClient $gatewayClient)
    {
        if ($gatewayClient->payments()->where('status', 'success')->exists()) {
            return redirect()->back()->with('danger', 'Cannot delete a client with successful payments.');
        }

        $gatewayClient->delete();

        return redirect()
            ->route('admin.musoftware-clients.index')
            ->with('success', 'Client deleted successfully.');
    }
}
