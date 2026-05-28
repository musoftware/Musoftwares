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
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return back()->withErrors(['error' => 'No active workspace found.']);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'nullable|exists:erp_tenant_clients,id',
            'value' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:Draft,Active,Completed,Cancelled',
        ]);

        // TODO: Create contract record once model/migration is ready

        return back()->with('success', 'Contract created.');
    }
}
