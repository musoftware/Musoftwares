<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\DebtTransaction;
use Modules\ERP\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DebtTransactionController extends Controller
{
    private function resolveTenantUser()
    {
        $user = auth('erp_team')->user();
        if (auth('erp_team')->check()) {
            $teamMember = auth('erp_team')->user();
            $user = $teamMember?->tenant?->user;
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
        return auth('erp_team')->user()->tenant_id;
    }

    public function create()
    {
        $this->checkAddon();

        return Inertia::render('ERP/Debts/Transactions/Create', [
            'baseCurrency' => Tenant::find($this->getTenantId())->baseCurrency,
        ]);
    }

    public function store(Request $request)
    {
        $this->checkAddon();

        $request->validate([
            'client_id' => 'nullable|exists:erp_tenant_clients,id',
            'new_client_name' => 'required_without:client_id|string|max:255',
            'new_client_phone' => 'nullable|string|max:255',
            'type' => 'required|in:given,received',
            'amount' => 'required|numeric|min:0.01',
            'note' => 'nullable|string|max:255',
            'date' => 'required|date',
        ]);

        $tenantId = $this->getTenantId();

        if ($request->client_id) {
            $client = TenantClient::where('id', $request->client_id)->where('tenant_id', $tenantId)->firstOrFail();
        } else {
            $user = $this->resolveTenantUser();
            $tenantModel = Tenant::find($tenantId);
            $client = TenantClient::create([
                'tenant_id' => $tenantId,
                'user_id' => $user->id,
                'name' => $request->new_client_name,
                'phone' => $request->new_client_phone,
                'currency_id' => $tenantModel->base_currency_id,
                'status' => 'active',
            ]);
        }

        DebtTransaction::create([
            'tenant_id' => $tenantId,
            'client_id' => $client->id,
            'type' => $request->type,
            'amount' => $request->amount,
            'note' => $request->note,
            'date' => $request->date,
        ]);

        return redirect()->route('erp.debts.index')->with('success', __('debts.transaction_added_successfully'));
    }

    public function destroy(DebtTransaction $transaction)
    {
        $this->checkAddon();

        if ($transaction->tenant_id !== $this->getTenantId()) {
            abort(403);
        }

        $transaction->delete();

        return redirect()->back()->with('success', __('debts.transaction_deleted_successfully'));
    }
}
