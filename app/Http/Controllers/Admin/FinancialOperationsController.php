<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Finance\LedgerCategory;
use App\Models\Finance\PlatformLedgerEntry;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialOperationsController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', 'expenses'); // expenses, income, salaries

        $query = PlatformLedgerEntry::with(['category', 'user']);

        if ($tab === 'expenses') {
            $query->where('type', 'expense');
        } elseif ($tab === 'income') {
            $query->where('type', 'income');
        } elseif ($tab === 'salaries') {
            $query->where('type', 'salary');
        }

        $entries = $query->orderBy('next_due_date', 'asc')
                         ->orderBy('transaction_date', 'desc')
                         ->paginate(20)
                         ->withQueryString();

        $categories = LedgerCategory::where('type', $tab === 'salaries' ? 'expense' : $tab)->get();
        // Since salaries are technically expenses, but we might have a specific salary category type or just reuse expense
        if ($tab === 'salaries') {
            $categories = LedgerCategory::whereIn('type', ['salary', 'expense'])->get();
        }
        
        $users = User::select('id', 'name', 'email')->where('role', 'admin')->orWhere('role', 'user')->get(); // Mock getting employees/users

        return Inertia::render('Admin/Finance/Index', [
            'entries' => $entries,
            'categories' => $categories,
            'users' => $users,
            'currentTab' => $tab,
            'stats' => [
                'total_monthly_expenses' => PlatformLedgerEntry::where('type', 'expense')->where('is_recurring', true)->where('recurrence_interval', 'monthly')->sum('amount'),
                'total_monthly_income' => PlatformLedgerEntry::where('type', 'income')->where('is_recurring', true)->where('recurrence_interval', 'monthly')->sum('amount'),
                'total_monthly_salaries' => PlatformLedgerEntry::where('type', 'salary')->where('is_recurring', true)->where('recurrence_interval', 'monthly')->sum('amount'),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:platform_ledger_categories,id',
            'user_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string',
            'type' => 'required|in:income,expense,salary',
            'is_recurring' => 'boolean',
            'recurrence_interval' => 'nullable|required_if:is_recurring,true|in:weekly,monthly,yearly',
            'transaction_date' => 'nullable|date',
            'next_due_date' => 'nullable|required_if:is_recurring,true|date',
            'status' => 'required|in:pending,completed,overdue,cancelled',
        ]);

        PlatformLedgerEntry::create($validated);

        return redirect()->back()->with('success', 'Ledger entry created successfully.');
    }

    public function update(Request $request, PlatformLedgerEntry $entry)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:platform_ledger_categories,id',
            'user_id' => 'nullable|exists:users,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|string',
            'type' => 'required|in:income,expense,salary',
            'is_recurring' => 'boolean',
            'recurrence_interval' => 'nullable|required_if:is_recurring,true|in:weekly,monthly,yearly',
            'transaction_date' => 'nullable|date',
            'next_due_date' => 'nullable|required_if:is_recurring,true|date',
            'status' => 'required|in:pending,completed,overdue,cancelled',
        ]);

        $entry->update($validated);

        return redirect()->back()->with('success', 'Ledger entry updated.');
    }

    public function destroy(PlatformLedgerEntry $entry)
    {
        $entry->delete();
        return redirect()->back()->with('success', 'Ledger entry deleted.');
    }

    public function markAsPaid(PlatformLedgerEntry $entry)
    {
        if ($entry->is_recurring && $entry->next_due_date) {
            // Push the due date forward based on interval
            $nextDate = \Carbon\Carbon::parse($entry->next_due_date);
            if ($entry->recurrence_interval === 'monthly') {
                $nextDate->addMonth();
            } elseif ($entry->recurrence_interval === 'yearly') {
                $nextDate->addYear();
            } elseif ($entry->recurrence_interval === 'weekly') {
                $nextDate->addWeek();
            }
            
            $entry->update([
                'next_due_date' => $nextDate,
                'status' => 'pending' // Still pending for the next cycle
            ]);
            
            // In a real accounting system, we would spawn a copy of this entry as "completed" for the history log.
            // For simplicity here, we just push the date forward.
            return redirect()->back()->with('success', 'Marked as paid. Next due date updated to ' . $nextDate->format('M d, Y'));
        }

        $entry->update(['status' => 'completed', 'transaction_date' => now()]);
        return redirect()->back()->with('success', 'Marked as completed.');
    }
}
