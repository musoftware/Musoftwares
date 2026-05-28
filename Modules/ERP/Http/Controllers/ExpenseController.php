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
        $tenant = Tenant::where('user_id', $user->id)->first();

        if (!$tenant) {
            return back()->withErrors(['error' => 'No active workspace found.']);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $expense = Expense::create([
            'tenant_id' => $tenant->id,
            'title' => $validated['title'],
            'amount' => $validated['amount'],
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

        return redirect()->route('erp.dashboard', ['section' => 'expenses'])->with('success', 'Expense logged successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->firstOrFail();
        $expense = Expense::findOrFail($id);

        if ($expense->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to expense.');
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
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();
        $expense = Expense::findOrFail($id);

        if (!$tenant || $expense->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to expense.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'category' => 'nullable|string|max:255',
            'date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);

        $expense->update([
            'title' => $validated['title'],
            'amount' => $validated['amount'],
            'category' => $validated['category'] ?? null,
            'date' => $validated['date'] ?? $expense->date,
            'description' => $validated['description'] ?? null,
        ]);

        ActivityLogger::log(
            'expense_updated',
            "Expense '{$expense->title}' was updated.",
            $expense
        );

        return redirect()->route('erp.dashboard', ['section' => 'expenses'])->with('success', 'Expense updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = Auth::user();
        $tenant = Tenant::where('user_id', $user->id)->first();
        $expense = Expense::findOrFail($id);

        if (!$tenant || $expense->tenant_id !== $tenant->id) {
            abort(403, 'Unauthorized access to expense.');
        }

        $title = $expense->title;
        $expense->delete();

        ActivityLogger::log(
            'expense_deleted',
            "Expense '{$title}' was deleted.",
            null
        );

        return redirect()->route('erp.dashboard', ['section' => 'expenses'])->with('success', 'Expense deleted successfully.');
    }
}
