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
        $search = $request->query('search');
        $category = $request->query('category');
        $status = $request->query('status');
        $userId = $request->query('user_id');
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');

        $allowedSortBy = ['created_at', 'amount', 'reason', 'due_date', 'status'];
        if (!in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $calendarEvents = [];

        if ($currentTab === 'calendar') {
            $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfMonth()->startOfWeek(\Carbon\Carbon::MONDAY);
            $endDate = \Carbon\Carbon::create($year, $month, 1)->endOfMonth()->endOfWeek(\Carbon\Carbon::SUNDAY);

            $incomeList = \App\Models\Transaction::with(['user', 'project'])
                ->whereIn('type', ['received', 'refunded', 'sent'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $expenseList = CostTransaction::with(['user', 'project'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $currencies = \App\Models\Currency::as_array();

            foreach ($incomeList as $entry) {
                $dateStr = $entry->created_at->toDateString();
                $currId = $entry->currency_id ?? $entry->currency;
                $currRow = isset($currencies[$currId]) ? $currencies[$currId] : null;
                $currencyCode = $currRow ? $currRow->currency : 'USD';
                $currencySymbol = $currRow ? $currRow->symbol : '$';

                $calendarEvents[$dateStr][] = [
                    'id' => $entry->id,
                    'type' => 'income',
                    'title' => ucfirst($entry->reason ?: 'Income'),
                    'amount' => $entry->amount,
                    'business_amount' => $entry->business_amount,
                    'currency' => $currencyCode,
                    'currency_symbol' => $currencySymbol,
                    'currency_id' => $currId,
                    'category' => ['name' => ucfirst($entry->reason ?: 'Income')],
                    'is_recurring' => false,
                    'next_due_date' => $entry->due_date,
                    'status' => $entry->status ?? 'completed',
                    'user' => $entry->user ? ['id' => $entry->user->id, 'name' => $entry->user->name, 'email' => $entry->user->email] : null,
                    'project' => $entry->project ? ['id' => $entry->project->id, 'name' => $entry->project->name] : null,
                    'created_at' => $entry->created_at->toIso8601String(),
                ];
            }

            foreach ($expenseList as $entry) {
                $dateStr = $entry->created_at->toDateString();
                $currId = $entry->currency_id ?? $entry->currency;
                $currRow = isset($currencies[$currId]) ? $currencies[$currId] : null;
                $currencyCode = $currRow ? $currRow->currency : 'USD';
                $currencySymbol = $currRow ? $currRow->symbol : '$';

                $calendarEvents[$dateStr][] = [
                    'id' => $entry->id,
                    'type' => $entry->reason === 'salary' ? 'salary' : 'expense',
                    'title' => ucfirst($entry->reason ?: 'Expense'),
                    'amount' => $entry->amount,
                    'business_amount' => $entry->business_amount,
                    'currency' => $currencyCode,
                    'currency_symbol' => $currencySymbol,
                    'currency_id' => $currId,
                    'category' => ['name' => ucfirst($entry->reason ?: 'Expense')],
                    'is_recurring' => false,
                    'next_due_date' => $entry->due_date,
                    'status' => $entry->status ?? 'completed',
                    'user' => $entry->user ? ['id' => $entry->user->id, 'name' => $entry->user->name, 'email' => $entry->user->email] : null,
                    'project' => $entry->project ? ['id' => $entry->project->id, 'name' => $entry->project->name] : null,
                    'created_at' => $entry->created_at->toIso8601String(),
                ];
            }

            $entries = new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50);
        } else {
            if ($currentTab === 'income') {
                $entriesQuery = \App\Models\Transaction::with(['user', 'project'])->whereIn('type', ['received', 'refunded', 'sent']);
            } elseif ($currentTab === 'salaries') {
                $entriesQuery = CostTransaction::with(['user', 'project'])->where('reason', 'salary');
            } else {
                // Default: expenses (excluding salaries)
                $entriesQuery = CostTransaction::with(['user', 'project'])->where('reason', '!=', 'salary');
            }

            // Apply filters
            if ($search) {
                $entriesQuery->where(function($q) use ($search) {
                    $q->where('reason', 'like', "%{$search}%");
                });
            }
            if ($category) {
                if ($currentTab === 'income') {
                    $entriesQuery->where('reason', $category);
                } else {
                    $entriesQuery->where(function($q) use ($category) {
                        $q->where('reason', $category)
                          ->orWhereExists(function ($sub) use ($category) {
                              $sub->select(\Illuminate\Support\Facades\DB::raw(1))
                                  ->from('recurring_cost_transactions')
                                  ->join('recurring_costs', 'recurring_cost_transactions.recurring_cost_id', '=', 'recurring_costs.id')
                                  ->whereColumn('recurring_cost_transactions.cost_transaction_id', 'cost_transactions.id')
                                  ->where('recurring_costs.reason', $category);
                          });
                      });
                }
            }
            if ($status) {
                $entriesQuery->where('status', $status);
            }
            if ($userId) {
                $entriesQuery->where('user_id', $userId);
            }

            $entriesQuery->orderBy($sortBy, $sortDir);
            $entries = $entriesQuery->paginate(50)->withQueryString();

            $currencies = \App\Models\Currency::as_array();
            
            $entries->getCollection()->transform(function ($entry) use ($currentTab, $currencies) {
                $currId = $entry->currency_id ?? $entry->currency;
                $currRow = isset($currencies[$currId]) ? $currencies[$currId] : null;
                $currencyCode = $currRow ? $currRow->currency : 'EGP';
                $currencySymbol = $currRow ? $currRow->symbol : 'e£';

                $isRecurring = false;
                $categoryName = $entry->reason;
                $title = $entry->reason;

                if ($entry instanceof CostTransaction) {
                    try {
                        $recTx = \Illuminate\Support\Facades\DB::table('recurring_cost_transactions')
                            ->join('recurring_costs', 'recurring_cost_transactions.recurring_cost_id', '=', 'recurring_costs.id')
                            ->where('recurring_cost_transactions.cost_transaction_id', $entry->id)
                            ->select('recurring_costs.title as source_title', 'recurring_costs.reason as source_reason')
                            ->first();
                        
                        if ($recTx) {
                            $isRecurring = true;
                            $categoryName = $recTx->source_reason;
                            $title = $entry->reason ?: $recTx->source_title;
                        }
                    } catch (\Throwable $e) {
                        $isRecurring = false;
                    }
                }

                return [
                    'id' => $entry->id,
                    'title' => ucfirst($title ?? ($currentTab === 'income' ? 'Income' : 'Cost')),
                    'amount' => $entry->amount,
                    'business_amount' => $entry->business_amount,
                    'currency' => $currencyCode,
                    'currency_symbol' => $currencySymbol,
                    'currency_id' => $currId,
                    'category' => ['name' => ucfirst($categoryName ?? ($currentTab === 'income' ? 'Income' : 'Cost'))],
                    'is_recurring' => $isRecurring,
                    'next_due_date' => $entry->due_date,
                    'status' => $entry->status ?? 'completed',
                    'user' => $entry->user ? ['id' => $entry->user->id, 'name' => $entry->user->name, 'email' => $entry->user->email] : null,
                    'project' => $entry->project ? ['id' => $entry->project->id, 'name' => $entry->project->name] : null,
                    'created_at' => $entry->created_at,
                    'type' => $entry instanceof \App\Models\Transaction ? $entry->type : ($entry->reason === 'salary' ? 'salary' : 'expense'),
                ];
            });
        }

        if ($currentTab === 'income') {
            $categories = \App\Models\Transaction::select('reason')->distinct()->pluck('reason')->filter()->values()->map(function($item) {
                return ['id' => $item, 'name' => ucfirst($item)];
            });
        } else {
            $costReasons = CostTransaction::select('reason')->distinct()->pluck('reason');
            $recurringReasons = \App\Models\RecurringCost::select('reason')->distinct()->pluck('reason');
            
            $categories = $costReasons->concat($recurringReasons)->unique()->filter()->values()->map(function($item) {
                return ['id' => $item, 'name' => ucfirst($item)];
            });
        }

        return Inertia::render('Admin/Finance/Index', [
            'entries' => $entries,
            'categories' => $categories,
            'filters' => $request->only(['type', 'category', 'status', 'user_id', 'search', 'sort_by', 'sort_dir']),
            'all_currencies' => \App\Models\Currency::all(),
            'stats' => [
                'total_monthly_expenses' => CostTransaction::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->where('reason', '!=', 'salary')
                    ->sum('business_amount'),
                'total_monthly_income' => (function() {
                    $incomeQuery = \App\Models\Transaction::whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month);
                    $received = (clone $incomeQuery)->where('type', 'received')->sum('business_amount') ?? 0;
                    $refunded = (clone $incomeQuery)->where('type', 'refunded')->sum('business_amount') ?? 0;
                    $sent = (clone $incomeQuery)->where('type', 'sent')->sum('business_amount') ?? 0;
                    return max(0, $received - $refunded - $sent);
                })(),
                'total_monthly_salaries' => CostTransaction::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->where('reason', 'salary')
                    ->sum('business_amount'),
                'business_currency_code' => (function() {
                    $bCurrencyId = \App\Models\AdminSettings::business_currency();
                    $bCurrency = \App\Models\Currency::find($bCurrencyId);
                    return $bCurrency ? $bCurrency->currency : 'EGP';
                })(),
                'business_currency_symbol' => (function() {
                    $bCurrencyId = \App\Models\AdminSettings::business_currency();
                    $bCurrency = \App\Models\Currency::find($bCurrencyId);
                    return $bCurrency ? $bCurrency->symbol : 'e£';
                })(),
                'monthly_trends' => (function() {
                    $trends = [];
                    for ($i = 5; $i >= 0; $i--) {
                        $date = now()->subMonths($i);
                        $year = $date->year;
                        $month = $date->month;
                        $monthName = $date->format('M Y');

                        // Income
                        $received = \App\Models\Transaction::whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)
                            ->where('type', 'received')
                            ->sum('business_amount') ?? 0;
                        $refunded = \App\Models\Transaction::whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)
                            ->where('type', 'refunded')
                            ->sum('business_amount') ?? 0;
                        $sent = \App\Models\Transaction::whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)
                            ->where('type', 'sent')
                            ->sum('business_amount') ?? 0;
                        $income = max(0, $received - $refunded - $sent);

                        // Expenses
                        $expenses = CostTransaction::whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)
                            ->where('reason', '!=', 'salary')
                            ->sum('business_amount') ?? 0;

                        // Salaries
                        $salaries = CostTransaction::whereYear('created_at', $year)
                            ->whereMonth('created_at', $month)
                            ->where('reason', 'salary')
                            ->sum('business_amount') ?? 0;

                        $trends[] = [
                            'month' => $monthName,
                            'income' => (float)$income,
                            'expenses' => (float)$expenses,
                            'payroll' => (float)$salaries,
                        ];
                    }
                    return $trends;
                })(),
                'expense_categories' => CostTransaction::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->where('reason', '!=', 'salary')
                    ->select('reason', \Illuminate\Support\Facades\DB::raw('SUM(business_amount) as total'))
                    ->groupBy('reason')
                    ->orderByDesc('total')
                    ->get()
                    ->map(function($item) {
                        return [
                            'name' => ucfirst($item->reason ?: 'Other'),
                            'value' => (float)$item->total
                        ];
                    })->values()->all(),
                'income_categories' => \App\Models\Transaction::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->where('type', 'received')
                    ->select('reason', \Illuminate\Support\Facades\DB::raw('SUM(business_amount) as total'))
                    ->groupBy('reason')
                    ->orderByDesc('total')
                    ->get()
                    ->map(function($item) {
                        return [
                            'name' => ucfirst($item->reason ?: 'Other'),
                            'value' => (float)$item->total
                        ];
                    })->values()->all(),
                'status_distribution' => (function() use ($currentTab) {
                    if ($currentTab === 'income') {
                        return \App\Models\Transaction::whereYear('created_at', now()->year)
                            ->whereMonth('created_at', now()->month)
                            ->select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), \Illuminate\Support\Facades\DB::raw('SUM(business_amount) as total'))
                            ->groupBy('status')
                            ->get()
                            ->map(function($item) {
                                return [
                                    'status' => ucfirst($item->status ?: 'completed'),
                                    'count' => (int)$item->count,
                                    'amount' => (float)$item->total
                                ];
                            })->values()->all();
                    } else {
                        $reasonOp = $currentTab === 'salaries' ? '=' : '!=';
                        return CostTransaction::whereYear('created_at', now()->year)
                            ->whereMonth('created_at', now()->month)
                            ->where('reason', $reasonOp, 'salary')
                            ->select('status', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'), \Illuminate\Support\Facades\DB::raw('SUM(business_amount) as total'))
                            ->groupBy('status')
                            ->get()
                            ->map(function($item) {
                                return [
                                    'status' => ucfirst($item->status ?: 'completed'),
                                    'count' => (int)$item->count,
                                    'amount' => (float)$item->total
                                ];
                            })->values()->all();
                    }
                })(),
            ],
            'users' => User::select('id', 'name', 'email')->get(),
            'currentTab' => $currentTab,
            'calendarEvents' => $calendarEvents,
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'amount' => 'required|numeric|gt:0',
        ]);

        $category = $request->input('category_id');
        if (empty($category)) {
            $category = $request->input('title');
        }

        $type = $request->input('type');
        if ($type === 'salary') {
            $category = 'salary';
        }

        $currencyId = $request->input('currency_id', \App\Models\AdminSettings::business_currency());
        $status = $request->input('status', 'completed');
        $dueDate = $request->input('due_date');
        $userId = $request->input('user_id');
        $transactionDate = $request->input('transaction_date') ? \Carbon\Carbon::parse($request->input('transaction_date')) : now();

        if ($request->input('is_recurring') && $type !== 'income') {
            $recurringCost = new \App\Models\RecurringCost();
            $recurringCost->title = $request->input('title');
            $recurringCost->amount = $request->input('amount');
            $recurringCost->currency_id = $currencyId;
            $recurringCost->reason = $category;
            $recurringCost->recurring = $request->input('recurrence_interval', 'month');
            $recurringCost->recurring_times = 1;
            $recurringCost->start_date = $transactionDate->format('Y-m-d');
            $recurringCost->current_date = $transactionDate->format('Y-m-d');
            $recurringCost->save();

            $c_id = CostTransaction::add_cost_balance($userId, $request->input('amount'), $request->input('title'), $currencyId);
            $cost = CostTransaction::find($c_id);
            if ($cost) {
                $cost->status = $status;
                $cost->due_date = $dueDate;
                $cost->created_at = $transactionDate;
                $cost->save();

                $recurringCost->transactions()->attach($cost->id, [
                    'unique_id' => $recurringCost->id . '-' . $transactionDate->format('Y-m-d')
                ]);
            }
        } else {
            if ($type === 'income') {
                $t = new \App\Models\Transaction();
                $t->user_id = $userId;
                $t->amount = $request->input('amount');
                $t->reason = $request->input('title');
                $t->type = 'received';
                $t->currency_id = $currencyId;
                $t->status = $status;
                $t->due_date = $dueDate;
                $t->created_at = $transactionDate;
                $t->save();
            } else {
                $c_id = CostTransaction::add_cost_balance($userId, $request->input('amount'), $request->input('title'), $currencyId);
                $cost = CostTransaction::find($c_id);
                if ($cost) {
                    $cost->status = $status;
                    $cost->due_date = $dueDate;
                    $cost->created_at = $transactionDate;
                    $cost->save();
                }
            }
        }

        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                if ($type === 'income') {
                    \App\Helpers\BalancesHelper::UpdateBalance($user);
                } else {
                    \App\Helpers\BalancesHelper::instance()->CalcCostBalance($user);
                }
            }
        }

        return redirect()->back()->with('success', __('general.ledger_entry_created_successfully'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'amount' => 'required|numeric|gt:0',
            'type' => 'required|string',
        ]);

        $type = $request->input('type');
        $currencyId = $request->input('currency_id', \App\Models\AdminSettings::business_currency());
        $status = $request->input('status', 'completed');
        $dueDate = $request->input('due_date');
        $userId = $request->input('user_id');
        $transactionDate = $request->input('transaction_date') ? \Carbon\Carbon::parse($request->input('transaction_date')) : now();

        $oldUserId = null;

        if ($type === 'income') {
            $transaction = \App\Models\Transaction::find($id);
            if ($transaction) {
                $oldUserId = $transaction->user_id;
                $transaction->user_id = $userId;
                $transaction->amount = $request->input('amount');
                $transaction->reason = $request->input('title');
                $transaction->currency_id = $currencyId;
                $transaction->status = $status;
                $transaction->due_date = $dueDate;
                $transaction->created_at = $transactionDate;
                $transaction->save();
            }
        } else {
            $cost = CostTransaction::find($id);
            if ($cost) {
                $oldUserId = $cost->user_id;
                $cost->user_id = $userId;
                $cost->amount = $request->input('amount');
                $cost->reason = $request->input('title');
                $cost->currency_id = $currencyId;
                $cost->status = $status;
                $cost->due_date = $dueDate;
                $cost->created_at = $transactionDate;
                $cost->save();
            }
        }

        if ($oldUserId) {
            $oldUser = \App\Models\User::find($oldUserId);
            if ($oldUser) {
                if ($type === 'income') {
                    \App\Helpers\BalancesHelper::UpdateBalance($oldUser);
                } else {
                    \App\Helpers\BalancesHelper::instance()->CalcCostBalance($oldUser);
                }
            }
        }
        if ($userId && $userId != $oldUserId) {
            $newUser = \App\Models\User::find($userId);
            if ($newUser) {
                if ($type === 'income') {
                    \App\Helpers\BalancesHelper::UpdateBalance($newUser);
                } else {
                    \App\Helpers\BalancesHelper::instance()->CalcCostBalance($newUser);
                }
            }
        }

        return redirect()->back()->with('success', __('general.ledger_entry_updated_successfully'));
    }

    public function destroy(Request $request, $id)
    {
        $type = $request->query('type');
        $oldUserId = null;

        if ($type === 'income') {
            $transaction = \App\Models\Transaction::find($id);
            if ($transaction) {
                $oldUserId = $transaction->user_id;
                if (method_exists($transaction, 'delete_with_balance')) {
                    $transaction->delete_with_balance();
                } else {
                    $transaction->delete();
                }
            }
        } else {
            $cost = CostTransaction::find($id);
            if ($cost) {
                $oldUserId = $cost->user_id;
                $cost->delete();
            }
        }

        if ($oldUserId) {
            $user = \App\Models\User::find($oldUserId);
            if ($user) {
                if ($type === 'income') {
                    \App\Helpers\BalancesHelper::UpdateBalance($user);
                } else {
                    \App\Helpers\BalancesHelper::instance()->CalcCostBalance($user);
                }
            }
        }
        
        return redirect()->back()->with('success', __('general.ledger_entry_deleted_successfully'));
    }

    public function markAsPaid(Request $request, $id)
    {
        $type = $request->query('type');
        $userId = null;

        if ($type === 'income') {
            $transaction = \App\Models\Transaction::find($id);
            if ($transaction) {
                $transaction->status = 'completed';
                $transaction->save();
                $userId = $transaction->user_id;
            }
        } else {
            $cost = CostTransaction::find($id);
            if ($cost) {
                $cost->status = 'completed';
                $cost->save();
                $userId = $cost->user_id;
            }
        }

        if ($userId) {
            $user = \App\Models\User::find($userId);
            if ($user) {
                if ($type === 'income') {
                    \App\Helpers\BalancesHelper::UpdateBalance($user);
                } else {
                    \App\Helpers\BalancesHelper::instance()->CalcCostBalance($user);
                }
            }
        }

        return redirect()->back()->with('success', __('general.ledger_entry_marked_as_paid'));
    }
}
