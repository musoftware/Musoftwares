<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\RecurringEntry;

class RecurringController extends Controller
{
    public function index()
    {
        $income = RecurringEntry::where('type', 'income')->get();
        $expense = RecurringEntry::where('type', 'expense')->get();

        return Inertia::render('ERP/Recurring/Index', [
            'income' => $income,
            'expense' => $expense,
        ]);
    }

    public function create()
    {
        return Inertia::render('ERP/Recurring/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'month_of_year' => 'nullable|integer|min:1|max:12',
        ]);

        $validated['status'] = 'active';
        $validated['next_run_date'] = $validated['start_date']; // Should actually calculate based on logic

        RecurringEntry::create($validated);

        return redirect()->route('erp.recurring.index');
    }

    public function show(RecurringEntry $recurring)
    {
        return Inertia::render('ERP/Recurring/Show', [
            'recurring' => $recurring,
        ]);
    }

    public function edit(RecurringEntry $recurring)
    {
        return Inertia::render('ERP/Recurring/Edit', [
            'recurring' => $recurring,
        ]);
    }

    public function update(Request $request, RecurringEntry $recurring)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'day_of_week' => 'nullable|integer|min:0|max:6',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'month_of_year' => 'nullable|integer|min:1|max:12',
        ]);

        $recurring->update($validated);

        return redirect()->route('erp.recurring.index');
    }

    public function destroy(RecurringEntry $recurring)
    {
        $recurring->delete();

        return redirect()->route('erp.recurring.index');
    }

    public function pause(RecurringEntry $recurring)
    {
        $recurring->update(['status' => 'paused']);
        return redirect()->back();
    }

    public function resume(RecurringEntry $recurring)
    {
        $recurring->update(['status' => 'active']);
        return redirect()->back();
    }

    public function logs(RecurringEntry $recurring)
    {
        $logs = $recurring->logs()->latest()->paginate(10);
        return Inertia::render('ERP/Recurring/Logs', [
            'recurring' => $recurring,
            'logs' => $logs,
        ]);
    }
}
