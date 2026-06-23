<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;
use Inertia\Inertia;

class ContractController extends Controller
{
    public function create()
    {
        return Inertia::render('ERP/Contracts/Create');
    }

    public function store(Request $request)
    {
        $user = auth('erp_team')->user();
        $tenant = auth('erp_team')->user()->tenant;

        if (!$tenant) {
            return back()->withErrors(['error' => 'No active workspace found.']);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'nullable|exists:erp_tenant_clients,id',
            'value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:Draft,Active,Completed,Cancelled',
        ]);

        $contract = new \Modules\ERP\Models\Contract($validated);
        $contract->tenant_id = $tenant->id;
        
        // Force the currency to match the client's currency if available
        if ($contract->client_id) {
            $client = \Modules\ERP\Models\Client::find($contract->client_id);
            if ($client && $client->currency_id) {
                $contract->currency_id = $client->currency_id;
            }
        }
        
        $contract->save();

        return back()->with('success', __('erp.contract_created_success'));
    }
}
