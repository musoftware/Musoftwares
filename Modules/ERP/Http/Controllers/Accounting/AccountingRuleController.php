<?php

namespace Modules\ERP\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Accounting\AccountingRule;
use Modules\ERP\Models\Accounting\ChartOfAccount;

class AccountingRuleController extends Controller
{
    public function index(Request $request)
    {
        $rules = AccountingRule::with(['debitAccount', 'creditAccount'])->get();
        $accounts = ChartOfAccount::orderBy('name')->get();
        return Inertia::render('ERP/Accounting/Rules/Index', [
            'rules' => $rules,
            'accounts' => $accounts
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_name' => 'required|string|max:255',
            'debit_account_id' => 'required|exists:erp_chart_of_accounts,id',
            'credit_account_id' => 'required|exists:erp_chart_of_accounts,id',
        ]);

        $validated['tenant_id'] = $request->user()->tenant_id ?? 1;
        AccountingRule::create($validated);

        return redirect()->back()->with('success', 'Rule created successfully.');
    }

    public function destroy(AccountingRule $rule)
    {
        $rule->delete();
        return redirect()->back()->with('success', 'Rule deleted successfully.');
    }
}
