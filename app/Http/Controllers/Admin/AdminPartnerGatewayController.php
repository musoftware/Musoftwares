<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PartnerClient;
use App\Models\User;
use App\Services\PartnerGatewayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class AdminPartnerGatewayController extends Controller
{
    public function __construct(
        protected PartnerGatewayService $gatewayService
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $clientsQuery = PartnerClient::with(['user:id,name,email'])
            ->withCount([
                'leases as active_leases_count' => fn($q) => $q->where('status', 'ACTIVE'),
            ]);

        if ($search) {
            $clientsQuery->where(function ($q) use ($search) {
                $q->where('client_name', 'like', "%{$search}%")
                    ->orWhere('client_key', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $clients = $clientsQuery->latest()->paginate(20)->withQueryString();

        $totals = [
            'total_clients' => PartnerClient::count(),
            'active_clients' => PartnerClient::where('is_active', true)->count(),
            'total_balance_usd' => (float)PartnerClient::sum('wallet_balance'),
            'active_leases_count' => \App\Models\PartnerCreditLease::where('status', 'ACTIVE')->count(),
        ];

        // Eligible users list for quick activation
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/PartnerGateway/Index', [
            'clients' => $clients,
            'totals' => $totals,
            'users' => $users,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'client_name' => ['required', 'string', 'max:100'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'cost_per_message' => ['required', 'numeric', 'min:0.0001', 'max:100'],
            'pricing_model' => ['required', 'in:PAYG_PER_MSG,SUBSCRIPTION,HYBRID'],
            'low_balance_threshold' => ['nullable', 'numeric', 'min:0'],
        ]);

        $initialBalance = (float)($validated['initial_balance'] ?? 0);

        $client = PartnerClient::createClient(
            name: $validated['client_name'],
            initialBalance: $initialBalance,
            rate: (float)$validated['cost_per_message'],
            pricingModel: $validated['pricing_model'],
            userId: (int)$validated['user_id']
        );

        if ($validated['low_balance_threshold'] !== null) {
            $client->update(['low_balance_threshold' => (float)$validated['low_balance_threshold']]);
        }

        if ($initialBalance > 0) {
            \App\Models\PartnerUsageLog::create([
                'partner_client_id' => $client->id,
                'type' => 'TOP_UP',
                'amount' => $initialBalance,
                'balance_after' => $initialBalance,
                'description' => 'Initial Partner Balance on Activation',
                'metadata' => ['admin_id' => auth()->id()],
            ]);
        }

        return redirect()->back()->with('success', "Partner credentials generated successfully for {$client->client_name}.");
    }

    public function update(Request $request, PartnerClient $partnerClient): RedirectResponse
    {
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:100'],
            'cost_per_message' => ['required', 'numeric', 'min:0.0001', 'max:100'],
            'pricing_model' => ['required', 'in:PAYG_PER_MSG,SUBSCRIPTION,HYBRID'],
            'low_balance_threshold' => ['required', 'numeric', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ]);

        $partnerClient->update($validated);

        return redirect()->back()->with('success', 'Partner client settings updated successfully.');
    }

    public function adjustBalance(Request $request, PartnerClient $partnerClient): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'not_in:0'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->gatewayService->adjustBalanceAdmin(
                $partnerClient,
                (float)$validated['amount'],
                $validated['reason'],
                auth()->id()
            );

            return redirect()->back()->with('success', 'Partner balance adjusted successfully.');
        } catch (RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function regenerateSecret(PartnerClient $partnerClient): RedirectResponse
    {
        $newSecret = $partnerClient->regenerateSecret();

        return redirect()->back()->with('success', 'Partner Secret Key rotated successfully.');
    }

    public function destroy(PartnerClient $partnerClient): RedirectResponse
    {
        $partnerClient->delete();

        return redirect()->back()->with('success', 'Partner Client deleted successfully.');
    }
}
