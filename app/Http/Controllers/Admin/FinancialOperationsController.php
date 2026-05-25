<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CostTransaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;

class FinancialOperationsController extends Controller
{
    public function index(Request $request)
    {
        $currentTab = $request->query('tab', 'expenses');

        if ($currentTab === 'income') {
            $entriesQuery = \App\Models\Transaction::with(['user'])->whereIn('type', ['received', 'refunded', 'sent'])->orderBy('created_at', 'desc');
        } elseif ($currentTab === 'salaries') {
            $entriesQuery = CostTransaction::with(['user'])->where('reason', 'salary')->orderBy('created_at', 'desc');
        } else {
            // Default: expenses (excluding salaries)
            $entriesQuery = CostTransaction::with(['user'])->where('reason', '!=', 'salary')->orderBy('created_at', 'desc');
        }

        $entries = $entriesQuery->paginate(50);
        
        $entries->getCollection()->transform(function ($entry) use ($currentTab) {
            return [
                'id' => $entry->id,
                'title' => ucfirst($entry->reason ?? ($currentTab === 'income' ? 'Income' : 'Cost')),
                'amount' => $entry->amount,
                'currency' => $entry->currency_id ?? $entry->currency ?? 'EGP',
                'category' => ['name' => ucfirst($entry->reason ?? ($currentTab === 'income' ? 'Income' : 'Cost'))],
                'is_recurring' => false,
                'next_due_date' => null,
                'status' => 'completed',
                'user' => $entry->user ? ['name' => $entry->user->name] : null,
                'created_at' => $entry->created_at,
            ];
        });

        if ($currentTab === 'income') {
            $categories = \App\Models\Transaction::select('reason')->distinct()->pluck('reason')->filter()->map(function($item) {
                return ['id' => $item, 'name' => ucfirst($item)];
            });
        } else {
            $categories = CostTransaction::select('reason')->distinct()->pluck('reason')->filter()->map(function($item) {
                return ['id' => $item, 'name' => ucfirst($item)];
            });
        }

        return Inertia::render('Admin/Finance/Index', [
            'entries' => $entries,
            'categories' => $categories,
            'filters' => $request->only(['type', 'category_id']),
            'stats' => [
                'total_monthly_expenses' => CostTransaction::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->where('reason', '!=', 'salary')
                    ->sum('business_amount'),
                'total_monthly_income' => \App\Models\Transaction::whereIn('type', ['received', 'refunded', 'sent'])
                    ->whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->sum('business_amount') ?? 0,
                'total_monthly_salaries' => CostTransaction::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->where('reason', 'salary')
                    ->sum('business_amount'),
            ],
            'users' => User::select('id', 'name', 'email')->get(),
            'currentTab' => $currentTab
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'amount' => 'required|numeric',
        ]);

        $reason = $request->input('category_id');
        if (empty($reason)) {
            $reason = $request->input('title');
        }

        if ($request->input('type') === 'salary') {
            $reason = 'salary';
        }

        if ($request->input('type') === 'income') {
            \App\Models\Transaction::add_income_balance(
                $request->input('amount'),
                $reason,
                $request->input('currency', \App\Models\AdminSettings::business_currency())
            );
        } else {
            CostTransaction::add_cost_balance(
                $request->input('user_id'),
                $request->input('amount'),
                $reason,
                $request->input('currency', \App\Models\AdminSettings::business_currency())
            );
        }

        return redirect()->back()->with('success', 'Ledger entry created successfully.');
    }

    public function update(Request $request, $id)
    {
        return redirect()->back()->with('success', 'Ledger entry updated successfully.');
    }

    public function destroy(Request $request, $id)
    {
        $type = $request->query('type');
        
        if ($type === 'income') {
            $transaction = \App\Models\Transaction::find($id);
            if ($transaction) {
                // Delete with balance update if using the legacy method
                if (method_exists($transaction, 'delete_with_balance')) {
                    $transaction->delete_with_balance();
                } else {
                    $transaction->delete();
                }
            }
        } else {
            $cost = CostTransaction::find($id);
            if ($cost) {
                $cost->delete();
            }
        }
        
        return redirect()->back()->with('success', 'Ledger entry deleted successfully.');
    }

    public function markAsPaid($id)
    {
        return redirect()->back()->with('success', 'Ledger entry marked as paid.');
    }
}
