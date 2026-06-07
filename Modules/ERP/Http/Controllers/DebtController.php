<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DebtController extends Controller
{
    private function resolveTenantUser()
    {
        $user = Auth::user();
        if (auth('erp_team')->check()) {
            $user = auth('erp_team')->user()?->tenant?->user;
        }
        return $user;
    }

    private function checkAddon()
    {
        $user = $this->resolveTenantUser();
        if (!$user || !$user->hasModuleSubscription('erp-debts')) {
            abort(403, __('errors.unauthorized_addon'));
        }
    }

    private function getTenantId()
    {
        $user = $this->resolveTenantUser();
        return Tenant::where('user_id', $user->id)->value('id');
    }

    public function index(Request $request)
    {
        $this->checkAddon();
        $tenantId = $this->getTenantId();

        $search = $request->input('q');

        $clients = TenantClient::with('debtTransactions')
            ->where('tenant_id', $tenantId)
            ->whereHas('debtTransactions')
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(20)
            ->through(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->name,
                    'phone' => $client->phone,
                    'debt_balance' => $client->debt_balance,
                ];
            });

        $allClients = TenantClient::where('tenant_id', $tenantId)->whereHas('debtTransactions')->get();
        $totalOwedToMe = $allClients->sum(function ($client) {
            $given = $client->debtTransactions()->where('type', 'given')->sum('business_amount');
            $received = $client->debtTransactions()->where('type', 'received')->sum('business_amount');
            $bal = $given - $received;
            return $bal > 0 ? $bal : 0;
        });
        $totalIOwe = $allClients->sum(function ($client) {
            $given = $client->debtTransactions()->where('type', 'given')->sum('business_amount');
            $received = $client->debtTransactions()->where('type', 'received')->sum('business_amount');
            $bal = $given - $received;
            return $bal < 0 ? abs($bal) : 0;
        });

        return Inertia::render('ERP/Debts/Index', [
            'clients' => $clients,
            'filters' => $request->only(['q']),
            'totalOwedToMe' => $totalOwedToMe,
            'totalIOwe' => $totalIOwe,
            'baseCurrency' => Tenant::find($tenantId)->baseCurrency,
        ]);
    }

    public function show(TenantClient $client)
    {
        $this->checkAddon();
        
        if ($client->tenant_id !== $this->getTenantId()) {
            abort(403);
        }

        $transactions = $client->debtTransactions()->latest('date')->latest('id')->paginate(20);

        return Inertia::render('ERP/Debts/Show', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'phone' => $client->phone,
                'debt_balance' => $client->debt_balance,
            ],
            'transactions' => $transactions,
            'baseCurrency' => Tenant::find($this->getTenantId())->baseCurrency,
        ]);
    }
}
