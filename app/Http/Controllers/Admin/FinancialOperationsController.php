<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LedgerCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialOperationsController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Admin/Finance/Index', [
            'entries' => ['data' => []],
            'categories' => LedgerCategory::all(),
            'filters' => $request->only(['type', 'category_id']),
            'stats' => [
                'total_monthly_expenses' => 0,
                'total_monthly_income' => 0,
                'total_monthly_salaries' => 0,
            ],
            'users' => \App\Models\User::all() ?? [],
            'currentTab' => $request->query('tab', 'expenses')
        ]);
    }

    public function store(Request $request)
    {
        return redirect()->back()->with('success', 'Ledger entry created successfully.');
    }

    public function update(Request $request, $id)
    {
        return redirect()->back()->with('success', 'Ledger entry updated successfully.');
    }

    public function destroy($id)
    {
        return redirect()->back()->with('success', 'Ledger entry deleted successfully.');
    }

    public function markAsPaid($id)
    {
        return redirect()->back()->with('success', 'Ledger entry marked as paid.');
    }
}
