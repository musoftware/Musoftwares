<?php

namespace Modules\ERP\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Accounting\ChartOfAccount;

class ChartOfAccountController extends Controller
{
    public function index(Request $request)
    {
        $accounts = ChartOfAccount::orderBy('code')->get();
        return Inertia::render('ERP/Accounting/ChartOfAccounts/Index', [
            'accounts' => $accounts
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'is_active' => 'boolean'
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id ?? 1; // Fallback or global scope handles this
        ChartOfAccount::create($validated);

        return redirect()->back()->with('success', 'Account created successfully.');
    }

    public function update(Request $request, ChartOfAccount $chart_of_account)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'is_active' => 'boolean'
        ]);

        $chart_of_account->update($validated);
        return redirect()->back()->with('success', 'Account updated successfully.');
    }

    public function destroy(ChartOfAccount $chart_of_account)
    {
        $chart_of_account->delete();
        return redirect()->back()->with('success', 'Account deleted successfully.');
    }
}
