<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Modules\ERP\Models\Tenant;
use Modules\ERP\Models\Expense;
use Modules\ERP\Services\ActivityLogger;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    /**
     * Resolve the active tenant, supporting both owner and team guards.
     */
    private function resolveTenant(): ?Tenant
    {
        if (Auth::guard('erp_team')->check()) {
            return Auth::guard('erp_team')->user()->tenant;
        }
        return Tenant::where('user_id', Auth::id())->first();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return redirect()->route('erp.dashboard', ['section' => 'expenses']);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('ERP/Expenses/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        $tenant = $this->resolveTenant();

        if (!$tenant) {
            return back()->withErrors(['error' => 'No active workspace found.']);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'category' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $expense = Expense::create([
            'tenant_id' => $tenant->id,
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'currency_id' => $validated['currency_id'] ?? null,
            'category' => $validated['category'] ?? null,
            'date' => $validated['date'] ?? now()->format('Y-m-d'),
            'description' => $validated['description'] ?? null,
            'created_by' => $user->id,
        ]);

        ActivityLogger::log(
            'expense_created',
            "Expense '{$expense->title}' (amount: {$expense->amount}) was logged.",
            $expense
        );

        return redirect()->route('erp.dashboard', ['section' => 'expenses'])->with('success', __('general.expense_logged_successfully'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $tenant = $this->resolveTenant();
        if (!$tenant) {
            abort(404, __('general.no_active_workspace_found'));
        }
        $expense = Expense::findOrFail($id);

        if ($expense->tenant_id !== $tenant->id) {
            abort(403, __('general.unauthorized_access_to_expense'));
        }

        return Inertia::render('ERP/Expenses/Edit', [
            'expense' => [
                'id' => $expense->id,
                'title' => $expense->title,
                'amount' => $expense->amount,
                'category' => $expense->category ?? '',
                'date' => $expense->date ? $expense->date->format('Y-m-d') : '',
                'description' => $expense->description ?? '',
            ]
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $tenant = $this->resolveTenant();
        $expense = Expense::findOrFail($id);

        if (!$tenant || $expense->tenant_id !== $tenant->id) {
            abort(403, __('general.unauthorized_access_to_expense'));
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency_id' => 'nullable|exists:currencies,id',
            'category' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $expense->update([
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'currency_id' => $validated['currency_id'] ?? $expense->currency_id,
            'category' => $validated['category'] ?? null,
            'date' => $validated['date'] ?? $expense->date,
            'description' => $validated['description'] ?? null,
        ]);

        ActivityLogger::log(
            'expense_updated',
            "Expense '{$expense->title}' was updated.",
            $expense
        );

        return redirect()->route('erp.dashboard', ['section' => 'expenses'])->with('success', __('general.expense_updated_successfully'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $tenant = $this->resolveTenant();
        $expense = Expense::findOrFail($id);

        if (!$tenant || $expense->tenant_id !== $tenant->id) {
            abort(403, __('general.unauthorized_access_to_expense'));
        }

        $title = $expense->title;
        $expense->delete();

        ActivityLogger::log(
            'expense_deleted',
            "Expense '{$title}' was deleted.",
            null
        );

        return redirect()->route('erp.dashboard', ['section' => 'expenses'])->with('success', __('general.expense_deleted_successfully'));
    }
}
