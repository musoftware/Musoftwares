<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\BalancesHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\CostTransaction;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\ExpenseBudget;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\RecurringCost;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

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
        if (! in_array($sortBy, $allowedSortBy)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);
        $calendarEvents = [];

        if ($currentTab === 'calendar') {
            $startDate = Carbon::create($year, $month, 1)->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfWeek(Carbon::SUNDAY);

            $incomeList = Transaction::with(['user', 'project'])
                ->whereIn('type', ['received', 'refunded', 'sent'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $expenseList = CostTransaction::with(['user', 'project'])
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();

            $currencies = Currency::as_array();

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

            $entries = new LengthAwarePaginator([], 0, 50);
        } else {
            if ($currentTab === 'income') {
                $entriesQuery = Transaction::with(['user', 'project'])->whereIn('type', ['received', 'refunded', 'sent']);
            } elseif ($currentTab === 'salaries') {
                $entriesQuery = CostTransaction::with(['user', 'project'])->where('reason', 'salary');
            } elseif ($currentTab === 'projects') {
                $entriesQuery = Project::with(['client', 'client_balance' => function ($q) {
                    $q->whereIn('type', ['received', 'earned']);
                }, 'cost_transactions']);
            } elseif ($currentTab === 'budgets') {
                $entriesQuery = ExpenseBudget::with('currency');
            } else {
                // Default: expenses (excluding salaries)
                $entriesQuery = CostTransaction::with(['user', 'project'])->where('reason', '!=', 'salary');
            }

            if ($entriesQuery) {
                // Apply filters
                if ($currentTab !== 'projects' && $currentTab !== 'budgets') {
                    if ($search) {
                        $entriesQuery->where(function ($q) use ($search) {
                            $q->where('reason', 'like', "%{$search}%");
                        });
                    }
                    if ($category) {
                        if ($currentTab === 'income') {
                            $entriesQuery->where('reason', $category);
                        } else {
                            $entriesQuery->where(function ($q) use ($category) {
                                $q->where('reason', $category)
                                    ->orWhereExists(function ($sub) use ($category) {
                                        $sub->select(DB::raw(1))
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
                    if ($request->filled('project_id')) {
                        $entriesQuery->where('project_id', $request->project_id);
                    }
                    if ($request->filled('from') && $request->filled('to')) {
                        $fromUtc = Carbon::parse($request->from)->startOfDay()->setTimezone('UTC');
                        $toUtc = Carbon::parse($request->to)->endOfDay()->setTimezone('UTC');
                        $entriesQuery->whereBetween('created_at', [$fromUtc, $toUtc]);
                    }
                    $entriesQuery->orderBy($sortBy, $sortDir);
                } elseif ($currentTab === 'projects') {
                    if ($search) {
                        $entriesQuery->where('project_name', 'like', "%{$search}%");
                    }
                    $entriesQuery->orderBy('id', 'desc');
                } elseif ($currentTab === 'budgets') {
                    if ($search) {
                        $entriesQuery->where('category', 'like', "%{$search}%");
                    }
                    $entriesQuery->orderBy('id', 'desc');
                }

                $entries = $entriesQuery->paginate(50)->withQueryString();
            }

            $currencies = Currency::as_array();

            if ($currentTab !== 'projects' && $currentTab !== 'budgets') {
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
                            $recTx = DB::table('recurring_cost_transactions')
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
                            Log::warning('Finance index: recurring cost lookup failed', [
                                'cost_transaction_id' => $entry->id,
                                'error' => $e->getMessage(),
                            ]);
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

                        'type' => $entry instanceof Transaction ? $entry->type : ($entry->reason === 'salary' ? 'salary' : 'expense'),
                    ];
                });
            } elseif ($currentTab === 'projects') {
                $entries->getCollection()->transform(function ($project) {
                    $revenue = $project->client_balance->sum('business_amount');
                    $costs = $project->cost_transactions->sum('business_amount');
                    $margin = $revenue > 0 ? (($revenue - $costs) / $revenue) * 100 : 0;

                    return [
                        'id' => $project->id,
                        'name' => $project->project_name,
                        'client' => $project->client ? ['name' => $project->client->name] : null,
                        'revenue' => $revenue,
                        'costs' => $costs,
                        'margin' => round($margin, 2),
                        'profit' => $revenue - $costs,
                        'type' => 'project',
                    ];
                });
            } elseif ($currentTab === 'budgets') {
                $entries->getCollection()->transform(function ($budget) {
                    $spent = CostTransaction::where('reason', $budget->category)
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->sum('amount');

                    return [
                        'id' => $budget->id,
                        'category' => $budget->category,
                        'amount' => $budget->amount,
                        'spent' => $spent,
                        'period' => $budget->period,
                        'notify_on_exceed' => $budget->notify_on_exceed,
                        'currency_symbol' => $budget->currency ? $budget->currency->symbol : '$',
                        'type' => 'budget',
                    ];
                });
            }
        }

        if ($currentTab === 'income') {
            $categories = Transaction::select('reason')->distinct()->pluck('reason')->filter()->values()->map(function ($item) {
                return ['id' => $item, 'name' => ucfirst($item)];
            });
        } else {
            $costReasons = CostTransaction::select('reason')->distinct()->pluck('reason');
            $recurringReasons = RecurringCost::select('reason')->distinct()->pluck('reason');

            $categories = $costReasons->concat($recurringReasons)->unique()->filter()->values()->map(function ($item) {
                return ['id' => $item, 'name' => ucfirst($item)];
            });
        }

        return Inertia::render('Admin/Finance/Index', [
            'entries' => $entries,
            'categories' => $categories,
            'filters' => $request->only(['type', 'category', 'status', 'user_id', 'search', 'sort_by', 'sort_dir']),
            'all_currencies' => Currency::all(),
            'stats' => [
                'total_monthly_expenses' => CostTransaction::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->where('reason', '!=', 'salary')
                    ->sum('business_amount'),
                'total_monthly_income' => (function () {
                    $incomeQuery = Transaction::whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month);
                    $received = (clone $incomeQuery)->where('type', 'received')->sum('business_amount') ?? 0;
                    $refunded = (clone $incomeQuery)->where('type', 'refunded')->sum('business_amount') ?? 0;
                    $sent = (clone $incomeQuery)->where('type', 'sent')->sum('business_amount') ?? 0;

                    return max(0, abs($received) - abs($refunded) - abs($sent));
                })(),
                'total_monthly_salaries' => CostTransaction::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->where('reason', 'salary')
                    ->sum('business_amount'),
                'total_monthly_net_profit' => (function () {
                    $incomeQuery = Transaction::whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month);
                    $received = (clone $incomeQuery)->where('type', 'received')->sum('business_amount') ?? 0;
                    $refunded = (clone $incomeQuery)->where('type', 'refunded')->sum('business_amount') ?? 0;
                    $sent = (clone $incomeQuery)->where('type', 'sent')->sum('business_amount') ?? 0;
                    $net_revenue = max(0, abs($received) - abs($refunded) - abs($sent));

                    $expenses = CostTransaction::whereYear('created_at', now()->year)
                        ->whereMonth('created_at', now()->month)
                        ->sum('business_amount') ?? 0;

                    return $net_revenue - abs($expenses);
                })(),
                'business_currency_code' => (function () {
                    $bCurrencyId = AdminSettings::business_currency();
                    $bCurrency = Currency::find($bCurrencyId);

                    return $bCurrency ? $bCurrency->currency : 'EGP';
                })(),
                'business_currency_symbol' => (function () {
                    $bCurrencyId = AdminSettings::business_currency();
                    $bCurrency = Currency::find($bCurrencyId);

                    return $bCurrency ? $bCurrency->symbol : 'e£';
                })(),
                'monthly_trends' => $this->buildMonthlyTrends(6),
                'forecast_receivables' => $this->buildReceivablesForecast(),
                'expense_categories' => CostTransaction::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->where('reason', '!=', 'salary')
                    ->select('reason', DB::raw('SUM(business_amount) as total'))
                    ->groupBy('reason')
                    ->orderByDesc('total')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'name' => ucfirst($item->reason ?: 'Other'),
                            'value' => (float) $item->total,
                        ];
                    })->values()->all(),
                'income_categories' => Transaction::whereYear('created_at', now()->year)
                    ->whereMonth('created_at', now()->month)
                    ->where('type', 'received')
                    ->select('reason', DB::raw('SUM(business_amount) as total'))
                    ->groupBy('reason')
                    ->orderByDesc('total')
                    ->get()
                    ->map(function ($item) {
                        return [
                            'name' => ucfirst($item->reason ?: 'Other'),
                            'value' => (float) $item->total,
                        ];
                    })->values()->all(),
                'status_distribution' => (function () use ($currentTab) {
                    if ($currentTab === 'income') {
                        return Transaction::whereYear('created_at', now()->year)
                            ->whereMonth('created_at', now()->month)
                            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(business_amount) as total'))
                            ->groupBy('status')
                            ->get()
                            ->map(function ($item) {
                                return [
                                    'status' => ucfirst($item->status ?: 'completed'),
                                    'count' => (int) $item->count,
                                    'amount' => (float) $item->total,
                                ];
                            })->values()->all();
                    } else {
                        $reasonOp = $currentTab === 'salaries' ? '=' : '!=';

                        return CostTransaction::whereYear('created_at', now()->year)
                            ->whereMonth('created_at', now()->month)
                            ->where('reason', $reasonOp, 'salary')
                            ->select('status', DB::raw('COUNT(*) as count'), DB::raw('SUM(business_amount) as total'))
                            ->groupBy('status')
                            ->get()
                            ->map(function ($item) {
                                return [
                                    'status' => ucfirst($item->status ?: 'completed'),
                                    'count' => (int) $item->count,
                                    'amount' => (float) $item->total,
                                ];
                            })->values()->all();
                    }
                })(),
            ],
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
            'currency_id' => 'required|integer|exists:currencies,id',
        ]);

        $category = $request->input('category_id');
        if (empty($category)) {
            $category = $request->input('title');
        }

        $type = $request->input('type');
        if ($type === 'salary') {
            $category = 'salary';
        }

        $currencyId = $request->input('currency_id');
        $status = $request->input('status', 'completed');
        $dueDate = $request->input('due_date');
        $userId = $request->input('user_id');
        $transactionDate = $request->input('transaction_date') ? Carbon::parse($request->input('transaction_date')) : now();

        if ($request->input('is_recurring') && $type !== 'income') {
            $recurringCost = new RecurringCost;
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
                    'unique_id' => $recurringCost->id.'-'.$transactionDate->format('Y-m-d'),
                ]);
            }
        } else {
            if ($type === 'income') {
                $t = new Transaction;
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
            $user = User::find($userId);
            if ($user) {
                if ($type === 'income') {
                    BalancesHelper::UpdateBalance($user);
                } else {
                    BalancesHelper::instance()->CalcCostBalance($user);
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
            'currency_id' => 'required|integer|exists:currencies,id',
        ]);

        $type = $request->input('type');
        $currencyId = $request->input('currency_id');
        $status = $request->input('status', 'completed');
        $dueDate = $request->input('due_date');
        $userId = $request->input('user_id');
        $transactionDate = $request->input('transaction_date') ? Carbon::parse($request->input('transaction_date')) : now();

        $oldUserId = null;

        if ($type === 'income') {
            $transaction = Transaction::find($id);
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
            $oldUser = User::find($oldUserId);
            if ($oldUser) {
                if ($type === 'income') {
                    BalancesHelper::UpdateBalance($oldUser);
                } else {
                    BalancesHelper::instance()->CalcCostBalance($oldUser);
                }
            }
        }
        if ($userId && $userId != $oldUserId) {
            $newUser = User::find($userId);
            if ($newUser) {
                if ($type === 'income') {
                    BalancesHelper::UpdateBalance($newUser);
                } else {
                    BalancesHelper::instance()->CalcCostBalance($newUser);
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
            $transaction = Transaction::find($id);
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
            $user = User::find($oldUserId);
            if ($user) {
                if ($type === 'income') {
                    BalancesHelper::UpdateBalance($user);
                } else {
                    BalancesHelper::instance()->CalcCostBalance($user);
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
            $transaction = Transaction::find($id);
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
            $user = User::find($userId);
            if ($user) {
                if ($type === 'income') {
                    BalancesHelper::UpdateBalance($user);
                } else {
                    BalancesHelper::instance()->CalcCostBalance($user);
                }
            }
        }

        return redirect()->back()->with('success', __('general.ledger_entry_marked_as_paid'));
    }

    public function export(Request $request)
    {
        $type = $request->query('type', 'pnl'); // pnl or ledger
        $month = $request->has('month') ? (int) $request->query('month') : null;
        $year = $request->has('year') ? (int) $request->query('year') : null;

        $filenameSuffix = ($year && $month) ? "{$year}_{$month}" : "lifetime";

        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=finance_{$type}_{$filenameSuffix}.csv",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($type, $month, $year) {
            $file = fopen('php://output', 'w');

            $startUtc = null;
            $endUtc = null;
            if ($year && $month) {
                $startUtc = Carbon::create($year, $month, 1, 0, 0, 0, 'Africa/Cairo')->setTimezone('UTC');
                $endUtc = Carbon::create($year, $month, 1, 0, 0, 0, 'Africa/Cairo')->endOfMonth()->setTimezone('UTC');
            }

            if ($type === 'pnl') {
                fputcsv($file, ['Category', 'Type', 'Business Amount']);

                // Income
                $incomeQuery = Transaction::query();
                if ($startUtc && $endUtc) {
                    $incomeQuery->whereBetween('created_at', [$startUtc, $endUtc]);
                }

                $received = (clone $incomeQuery)->where('type', 'received')->sum('business_amount') ?? 0;
                $refunded = (clone $incomeQuery)->where('type', 'refunded')->sum('business_amount') ?? 0;
                $sent = (clone $incomeQuery)->where('type', 'sent')->sum('business_amount') ?? 0;

                $net_revenue = max(0, abs($received) - abs($refunded) - abs($sent));

                fputcsv($file, ['Gross Income', 'Income', abs($received)]);
                fputcsv($file, ['Refunds/Sent', 'Income Deduction', -(abs($refunded) + abs($sent))]);
                fputcsv($file, ['Net Revenue', 'Income', $net_revenue]);

                // Expenses
                $expensesQuery = CostTransaction::query();
                if ($startUtc && $endUtc) {
                    $expensesQuery->whereBetween('created_at', [$startUtc, $endUtc]);
                }
                $expenses = $expensesQuery->select('reason', DB::raw('SUM(business_amount) as total'))
                    ->groupBy('reason')
                    ->get();

                $total_expenses = 0;
                foreach ($expenses as $exp) {
                    fputcsv($file, [ucfirst($exp->reason), 'Expense', -abs($exp->total)]);
                    $total_expenses += abs($exp->total);
                }

                fputcsv($file, ['Net Profit', 'Profit', $net_revenue - $total_expenses]);
            } else {
                fputcsv($file, ['Date', 'Title', 'Type', 'Category', 'Original Amount', 'Currency', 'Business Amount']);

                $incomesQuery = Transaction::with(['currency_info'])
                    ->whereIn('type', ['received', 'refunded', 'sent']);
                if ($startUtc && $endUtc) {
                    $incomesQuery->whereBetween('created_at', [$startUtc, $endUtc]);
                }
                $incomes = $incomesQuery->get();

                $costsQuery = CostTransaction::with(['currency_info']);
                if ($startUtc && $endUtc) {
                    $costsQuery->whereBetween('created_at', [$startUtc, $endUtc]);
                }
                $costs = $costsQuery->get();

                $merged = $incomes->concat($costs)->sortByDesc('created_at')->values();

                foreach ($merged as $entry) {
                    $typeStr = $entry instanceof Transaction ? 'Income ('.$entry->type.')' : 'Expense';
                    $currencyCode = $entry->currency_info ? $entry->currency_info->currency : 'SYS';
                    fputcsv($file, [
                        $entry->created_at->format('Y-m-d H:i'),
                        $entry->reason,
                        $typeStr,
                        $entry->reason,
                        $entry->amount,
                        $currencyCode,
                        $entry->business_amount,
                    ]);
                }
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Income, expenses, payroll and net profit for the last N months (oldest first).
     * Uses one grouped query per table instead of several queries per month.
     */
    private function buildMonthlyTrends(int $months): array
    {
        $start = now()->subMonths($months - 1)->startOfMonth();
        $end = now()->endOfMonth();
        $monthKey = $this->monthKeyExpression('created_at');

        $income = Transaction::whereBetween('created_at', [$start, $end])
            ->whereIn('type', ['received', 'refunded', 'sent'])
            ->selectRaw("{$monthKey} as month_key, type, SUM(business_amount) as total")
            ->groupBy('month_key', 'type')
            ->get()
            ->groupBy('month_key');

        $costs = CostTransaction::whereBetween('created_at', [$start, $end])
            ->selectRaw("{$monthKey} as month_key")
            ->selectRaw("SUM(CASE WHEN reason = 'salary' THEN business_amount ELSE 0 END) as salaries")
            ->selectRaw("SUM(CASE WHEN reason <> 'salary' THEN business_amount ELSE 0 END) as expenses")
            ->groupBy('month_key')
            ->get()
            ->keyBy('month_key');

        $trends = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $trends[] = $this->monthTrendRow($date, $income->get($date->format('Y-m'), collect()), $costs->get($date->format('Y-m')));
        }

        return $trends;
    }

    private function monthTrendRow(Carbon $date, $incomeRows, $costRow): array
    {
        $byType = $incomeRows->pluck('total', 'type');
        $income = max(0, abs((float) ($byType['received'] ?? 0)) - abs((float) ($byType['refunded'] ?? 0)) - abs((float) ($byType['sent'] ?? 0)));
        $expenses = abs((float) ($costRow->expenses ?? 0));
        $salaries = abs((float) ($costRow->salaries ?? 0));

        return [
            'month' => $date->format('M Y'),
            'income' => (float) $income,
            'expenses' => (float) $expenses,
            'payroll' => (float) $salaries,
            'net_profit' => (float) ($income - $expenses - $salaries),
        ];
    }

    /**
     * SQL expression that turns a datetime column into a "YYYY-MM" key (MySQL and SQLite).
     */
    private function monthKeyExpression(string $column): string
    {
        return DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";
    }

    /**
     * Open receivables in business currency, bucketed by expected due date
     * (created_at + 30 days). Sums per currency and bucket in SQL, then converts once per currency.
     */
    private function buildReceivablesForecast(): array
    {
        $now = now();
        $bucketSql = "CASE WHEN created_at IS NULL THEN 'next_30_days'"
            ." WHEN created_at >= ? AND created_at <= ? THEN 'next_30_days'"
            ." WHEN created_at > ? AND created_at <= ? THEN 'next_60_days'"
            ." WHEN created_at > ? AND created_at <= ? THEN 'next_90_days'"
            ." ELSE 'later' END";
        $bindings = [
            $now->copy()->subDays(30), $now,
            $now, $now->copy()->addDays(30),
            $now->copy()->addDays(30), $now->copy()->addDays(60),
        ];

        $rows = Invoice::whereIn('status', ['sent', 'partially_paid', 'unpaid', 'pending'])
            ->selectRaw("currency_id, {$bucketSql} as bucket, SUM(unpaid) as outstanding", array_map(fn ($d) => $d->format('Y-m-d H:i:s'), $bindings))
            ->groupBy('currency_id', 'bucket')
            ->toBase()
            ->get();

        $forecast = ['total_outstanding' => 0, 'next_30_days' => 0, 'next_60_days' => 0, 'next_90_days' => 0];
        $businessCurrencyId = AdminSettings::business_currency();

        foreach ($rows as $row) {
            $amount = $this->toBusinessCurrency((float) $row->outstanding, $row->currency_id, $businessCurrencyId);
            $forecast['total_outstanding'] += $amount;
            if (isset($forecast[$row->bucket])) {
                $forecast[$row->bucket] += $amount;
            }
        }

        return $forecast;
    }

    private function toBusinessCurrency(float $amount, $currencyId, $businessCurrencyId): float
    {
        if ((int) $currencyId === (int) $businessCurrencyId) {
            return $amount;
        }

        try {
            return (float) CurrenciesExchange::RateByDate(now(), $amount, $currencyId, $businessCurrencyId);
        } catch (\Throwable $e) {
            Log::warning('Receivables forecast: currency conversion failed, using unconverted amount', [
                'currency_id' => $currencyId,
                'business_currency_id' => $businessCurrencyId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            return $amount;
        }
    }
}
