<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\RecurringCost;
use App\Models\RecurringIncome;
use App\Models\RecurringSalary;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RecurringBusinessController extends Controller
{
    // ==========================================
    // RECURRING COSTS ACTIONS
    // ==========================================

    public function recurring_costs(Request $request)
    {
        $costs = RecurringCost::latest()->paginate(50);
        $costs->getCollection()->transform(function ($cost) {
            $cost->currency = \App\Helpers\CurrencyHelper::getFrontendCurrency($cost->currency_id);
            return $cost;
        });
        $currencies = Currency::all();
        
        $costReasons = CostTransaction::select('reason')->distinct()->pluck('reason');
        $recurringReasons = RecurringCost::select('reason')->distinct()->pluck('reason');
        $categories = $costReasons->concat($recurringReasons)->unique()->filter()->values();

        $bCurrency = \App\Helpers\CurrencyHelper::getBusinessCurrency();

        $stats = [
            'monthly_total' => RecurringCost::monthly_str(),
            'annual_total' => RecurringCost::annual_str(),
            'business_currency_code' => $bCurrency->currency,
            'business_currency_symbol' => $bCurrency->symbol,
        ];

        return Inertia::render('Admin/Business/RecurringCosts/Index', [
            'costs' => $costs,
            'currencies' => $currencies,
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    public function create_recurring_costs()
    {
        $currencies = Currency::all();
        
        $costReasons = CostTransaction::select('reason')->distinct()->pluck('reason');
        $recurringReasons = RecurringCost::select('reason')->distinct()->pluck('reason');
        $categories = $costReasons->concat($recurringReasons)->unique()->filter()->values();

        $bCurrency = \App\Helpers\CurrencyHelper::getBusinessCurrency();

        $stats = [
            'business_currency_code' => $bCurrency->currency,
            'business_currency_symbol' => $bCurrency->symbol,
        ];

        return Inertia::render('Admin/Business/RecurringCosts/Create', [
            'currencies' => $currencies,
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    public function store_recurring_costs(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required',
            'reason_choice' => 'required|string',
            'custom_reason' => 'required_if:reason_choice,custom',
            'start_date' => 'required|date',
            'recurring' => 'required|in:day,week,month,year',
            'recurring_times' => 'required|integer|min:1',
        ]);

        $rCost = new RecurringCost();
        $rCost->title = $request->input('title');
        $rCost->amount = $request->input('amount');
        $rCost->currency_id = $request->input('currency');
        $rCost->start_date = $request->input('start_date');
        $rCost->current_date = $request->input('start_date');
        $rCost->recurring = $request->input('recurring');
        $rCost->recurring_times = $request->input('recurring_times');
        
        $rCost->reason = $request->input('reason_choice') === 'custom' 
            ? $request->input('custom_reason') 
            : $request->input('reason_choice');

        if ($request->input('recurring') === 'week') {
            $request->validate(['recurring_times_week' => 'required']);
            $rCost->recurring_times_week = implode(',', $request->input('recurring_times_week', []));
        }
        if ($request->input('recurring') === 'month') {
            $request->validate(['recurring_times_month' => 'required']);
            $rCost->recurring_times_month = implode(',', $request->input('recurring_times_month', []));
        }
        if ($request->input('recurring') === 'year') {
            $request->validate(['recurring_times_year' => 'required']);
            $rCost->recurring_times_year = implode(',', $request->input('recurring_times_year', []));
        }

        $rCost->save();
        $rCost->apply();

        return redirect()->route('admin.recurring_costs.index')->with('success', __('general.recurring_cost_added_successfully'));
    }

    public function edit_recurring_costs($id)
    {
        $cost = RecurringCost::findOrFail($id);
        $currencies = Currency::all();
        
        $costReasons = CostTransaction::select('reason')->distinct()->pluck('reason');
        $recurringReasons = RecurringCost::select('reason')->distinct()->pluck('reason');
        $categories = $costReasons->concat($recurringReasons)->unique()->filter()->values();

        return Inertia::render('Admin/Business/RecurringCosts/Edit', [
            'cost' => [
                'id' => $cost->id,
                'title' => $cost->title,
                'amount' => $cost->amount,
                'currency' => $cost->currency_id,
                'start_date' => $cost->start_date,
                'recurring' => $cost->recurring,
                'recurring_times' => $cost->recurring_times,
                'recurring_times_week' => $cost->recurring_times_week ? explode(',', $cost->recurring_times_week) : [],
                'recurring_times_month' => $cost->recurring_times_month ? explode(',', $cost->recurring_times_month) : [],
                'recurring_times_year' => $cost->recurring_times_year ? explode(',', $cost->recurring_times_year) : [],
                'reason' => $cost->reason,
            ],
            'currencies' => $currencies,
            'categories' => $categories,
        ]);
    }

    public function update_recurring_costs(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required',
            'reason_choice' => 'required|string',
            'custom_reason' => 'required_if:reason_choice,custom',
            'start_date' => 'required|date',
            'recurring' => 'required|in:day,week,month,year',
            'recurring_times' => 'required|integer|min:1',
        ]);

        $rCost = RecurringCost::findOrFail($id);
        $rCost->title = $request->input('title');
        $rCost->amount = $request->input('amount');
        $rCost->currency_id = $request->input('currency');
        $rCost->start_date = $request->input('start_date');
        $rCost->current_date = $request->input('start_date');
        $rCost->recurring = $request->input('recurring');
        $rCost->recurring_times = $request->input('recurring_times');
        
        $rCost->reason = $request->input('reason_choice') === 'custom' 
            ? $request->input('custom_reason') 
            : $request->input('reason_choice');

        $rCost->recurring_times_week = null;
        $rCost->recurring_times_month = null;
        $rCost->recurring_times_year = null;

        if ($request->input('recurring') === 'week') {
            $request->validate(['recurring_times_week' => 'required']);
            $rCost->recurring_times_week = implode(',', $request->input('recurring_times_week', []));
        }
        if ($request->input('recurring') === 'month') {
            $request->validate(['recurring_times_month' => 'required']);
            $rCost->recurring_times_month = implode(',', $request->input('recurring_times_month', []));
        }
        if ($request->input('recurring') === 'year') {
            $request->validate(['recurring_times_year' => 'required']);
            $rCost->recurring_times_year = implode(',', $request->input('recurring_times_year', []));
        }

        $rCost->save();

        return redirect()->route('admin.recurring_costs.index')->with('success', __('general.recurring_cost_updated_successfully'));
    }

    public function recurring_costs_view($id)
    {
        $rCost = RecurringCost::findOrFail($id);
        $transactions = $rCost->transactions()->latest()->get()->map(function ($tx) {
            return [
                'id' => $tx->id,
                'created_at' => $tx->created_at,
                'amount' => $tx->amount,
                'currency' => \App\Helpers\CurrencyHelper::getFrontendCurrency($tx->currency_id ?? $tx->currency),
                'reason' => $tx->reason,
            ];
        });

        // Compute 15 upcoming schedule dates
        $upcomingSchedule = [];
        $count = 0;
        $baseDate = Carbon::parse($rCost->current_date);
        for ($i = 0; $i <= 365 && $count < 15; $i++) {
            $checkDate = $baseDate->copy()->addDays($i);
            if ($rCost->isToday($checkDate)) {
                $count++;
                $upcomingSchedule[] = [
                    'date' => $checkDate->toDateString(),
                    'amount_str' => $rCost->current_amount_str(),
                    'recorded' => $rCost->createdBefore($checkDate),
                ];
            }
        }

        $currencyModel = \App\Helpers\CurrencyHelper::getFrontendCurrency($rCost->currency_id);

        return Inertia::render('Admin/Business/RecurringCosts/View', [
            'cost' => [
                'id' => $rCost->id,
                'title' => $rCost->title,
                'amount' => $rCost->amount,
                'currency' => $currencyModel,
                'reason' => $rCost->reason,
                'start_date' => $rCost->start_date,
                'current_date' => $rCost->current_date,
                'recurring' => $rCost->recurring,
                'recurring_times' => $rCost->recurring_times,
                'details' => $rCost->details(),
            ],
            'transactions' => $transactions,
            'upcomingSchedule' => $upcomingSchedule,
            'total_stat' => [
                'entries_count' => $rCost->transactions()->count(),
                'total_cost' => \App\Helpers\FinanceHelper::instance()->format_money($rCost->transactions()->sum('amount'), $rCost->currency_id),
            ]
        ]);
    }

    public function recurring_costs_delete($id)
    {
        $rCost = RecurringCost::findOrFail($id);
        $rCost->delete();
        return redirect()->route('admin.recurring_costs.index')->with('success', __('general.recurring_cost_deleted'));
    }

    public function recurring_costs_delete_with_transaction($id)
    {
        $rCost = RecurringCost::findOrFail($id);
        $rCost->delete_with_transactions();
        return redirect()->route('admin.recurring_costs.index')->with('success', __('general.recurring_cost_and_generated_transactions_deleted'));
    }

    // ==========================================
    // RECURRING INCOME ACTIONS
    // ==========================================

    public function recurring_income(Request $request)
    {
        $incomes = RecurringIncome::latest()->paginate(50);
        $incomes->getCollection()->transform(function ($income) {
            $income->currency = \App\Helpers\CurrencyHelper::getFrontendCurrency($income->currency_id);
            return $income;
        });
        $currencies = Currency::all();
        
        $incomeReasons = \App\Models\Transaction::whereIn('type', ['received', 'refunded', 'sent'])->select('reason')->distinct()->pluck('reason');
        $recurringReasons = RecurringIncome::select('reason')->distinct()->pluck('reason');
        $categories = $incomeReasons->concat($recurringReasons)->unique()->filter()->values();

        $bCurrency = \App\Helpers\CurrencyHelper::getBusinessCurrency();

        $stats = [
            'monthly_total' => RecurringIncome::monthly_str(),
            'annual_total' => RecurringIncome::annual_str(),
            'business_currency_code' => $bCurrency->currency,
            'business_currency_symbol' => $bCurrency->symbol,
        ];

        return Inertia::render('Admin/Business/RecurringIncome/Index', [
            'incomes' => $incomes,
            'currencies' => $currencies,
            'categories' => $categories,
            'stats' => $stats,
        ]);
    }

    public function store_recurring_income(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required',
            'reason_choice' => 'required|string',
            'custom_reason' => 'required_if:reason_choice,custom',
            'start_date' => 'required|date',
            'recurring' => 'required|in:day,week,month,year',
            'recurring_times' => 'required|integer|min:1',
        ]);

        $rIncome = new RecurringIncome();
        $rIncome->title = $request->input('title');
        $rIncome->amount = $request->input('amount');
        $rIncome->currency_id = $request->input('currency');
        $rIncome->start_date = $request->input('start_date');
        $rIncome->current_date = $request->input('start_date');
        $rIncome->recurring = $request->input('recurring');
        $rIncome->recurring_times = $request->input('recurring_times');
        
        $rIncome->reason = $request->input('reason_choice') === 'custom' 
            ? $request->input('custom_reason') 
            : $request->input('reason_choice');

        if ($request->input('recurring') === 'week') {
            $request->validate(['recurring_times_week' => 'required']);
            $rIncome->recurring_times_week = implode(',', $request->input('recurring_times_week', []));
        }
        if ($request->input('recurring') === 'month') {
            $request->validate(['recurring_times_month' => 'required']);
            $rIncome->recurring_times_month = implode(',', $request->input('recurring_times_month', []));
        }
        if ($request->input('recurring') === 'year') {
            $request->validate(['recurring_times_year' => 'required']);
            $rIncome->recurring_times_year = implode(',', $request->input('recurring_times_year', []));
        }

        $rIncome->save();
        $rIncome->apply();

        return redirect()->route('admin.recurring_income.index')->with('success', __('general.recurring_income_added_successfully'));
    }

    public function edit_recurring_income($id)
    {
        $income = RecurringIncome::findOrFail($id);
        $currencies = Currency::all();
        
        $incomeReasons = \App\Models\Transaction::whereIn('type', ['received', 'refunded', 'sent'])->select('reason')->distinct()->pluck('reason');
        $recurringReasons = RecurringIncome::select('reason')->distinct()->pluck('reason');
        $categories = $incomeReasons->concat($recurringReasons)->unique()->filter()->values();

        return Inertia::render('Admin/Business/RecurringIncome/Edit', [
            'income' => [
                'id' => $income->id,
                'title' => $income->title,
                'amount' => $income->amount,
                'currency' => $income->currency_id,
                'start_date' => $income->start_date,
                'recurring' => $income->recurring,
                'recurring_times' => $income->recurring_times,
                'recurring_times_week' => $income->recurring_times_week ? explode(',', $income->recurring_times_week) : [],
                'recurring_times_month' => $income->recurring_times_month ? explode(',', $income->recurring_times_month) : [],
                'recurring_times_year' => $income->recurring_times_year ? explode(',', $income->recurring_times_year) : [],
                'reason' => $income->reason,
            ],
            'currencies' => $currencies,
            'categories' => $categories,
        ]);
    }

    public function update_recurring_income(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'amount' => 'required|numeric|gt:0',
            'currency' => 'required',
            'reason_choice' => 'required|string',
            'custom_reason' => 'required_if:reason_choice,custom',
            'start_date' => 'required|date',
            'recurring' => 'required|in:day,week,month,year',
            'recurring_times' => 'required|integer|min:1',
        ]);

        $rIncome = RecurringIncome::findOrFail($id);
        $rIncome->title = $request->input('title');
        $rIncome->amount = $request->input('amount');
        $rIncome->currency_id = $request->input('currency');
        $rIncome->start_date = $request->input('start_date');
        $rIncome->current_date = $request->input('start_date');
        $rIncome->recurring = $request->input('recurring');
        $rIncome->recurring_times = $request->input('recurring_times');
        
        $rIncome->reason = $request->input('reason_choice') === 'custom' 
            ? $request->input('custom_reason') 
            : $request->input('reason_choice');

        $rIncome->recurring_times_week = null;
        $rIncome->recurring_times_month = null;
        $rIncome->recurring_times_year = null;

        if ($request->input('recurring') === 'week') {
            $request->validate(['recurring_times_week' => 'required']);
            $rIncome->recurring_times_week = implode(',', $request->input('recurring_times_week', []));
        }
        if ($request->input('recurring') === 'month') {
            $request->validate(['recurring_times_month' => 'required']);
            $rIncome->recurring_times_month = implode(',', $request->input('recurring_times_month', []));
        }
        if ($request->input('recurring') === 'year') {
            $request->validate(['recurring_times_year' => 'required']);
            $rIncome->recurring_times_year = implode(',', $request->input('recurring_times_year', []));
        }

        $rIncome->save();

        return redirect()->route('admin.recurring_income.index')->with('success', __('general.recurring_income_updated_successfully'));
    }

    public function recurring_income_view($id)
    {
        $rIncome = RecurringIncome::findOrFail($id);
        $transactions = $rIncome->transactions()->latest()->get()->map(function ($tx) {
            return [
                'id' => $tx->id,
                'created_at' => $tx->created_at,
                'amount' => $tx->amount,
                'currency' => \App\Helpers\CurrencyHelper::getFrontendCurrency($tx->currency_id ?? $tx->currency),
                'reason' => $tx->reason,
            ];
        });

        // Compute 15 upcoming schedule dates
        $upcomingSchedule = [];
        $count = 0;
        $baseDate = Carbon::parse($rIncome->current_date);
        for ($i = 0; $i <= 365 && $count < 15; $i++) {
            $checkDate = $baseDate->copy()->addDays($i);
            if ($rIncome->isToday($checkDate)) {
                $count++;
                $upcomingSchedule[] = [
                    'date' => $checkDate->toDateString(),
                    'amount_str' => $rIncome->current_amount_str(),
                    'recorded' => $rIncome->createdBefore($checkDate),
                ];
            }
        }

        $currencyModel = \App\Helpers\CurrencyHelper::getFrontendCurrency($rIncome->currency_id);

        return Inertia::render('Admin/Business/RecurringIncome/View', [
            'income' => [
                'id' => $rIncome->id,
                'title' => $rIncome->title,
                'amount' => $rIncome->amount,
                'currency' => $currencyModel,
                'reason' => $rIncome->reason,
                'start_date' => $rIncome->start_date,
                'current_date' => $rIncome->current_date,
                'recurring' => $rIncome->recurring,
                'recurring_times' => $rIncome->recurring_times,
                'details' => $rIncome->details(),
            ],
            'transactions' => $transactions,
            'upcomingSchedule' => $upcomingSchedule,
            'total_stat' => [
                'entries_count' => $rIncome->transactions()->count(),
                'total_cost' => \App\Helpers\FinanceHelper::instance()->format_money($rIncome->transactions()->sum('amount'), $rIncome->currency_id),
            ]
        ]);
    }

    public function recurring_income_delete($id)
    {
        $rIncome = RecurringIncome::findOrFail($id);
        $rIncome->delete();
        return redirect()->route('admin.recurring_income.index')->with('success', __('general.recurring_income_deleted'));
    }

    public function recurring_income_delete_with_transaction($id)
    {
        $rIncome = RecurringIncome::findOrFail($id);
        $rIncome->delete_with_transactions();
        return redirect()->route('admin.recurring_income.index')->with('success', __('general.recurring_income_and_generated_transactions_deleted'));
    }

    // ==========================================
    // RECURRING SALARIES ACTIONS
    // ==========================================

    public function recurring_salaries(Request $request)
    {
        $salaries = RecurringSalary::with('user')->latest()->paginate(50);
        $salaries->getCollection()->transform(function ($salary) {
            $salary->currency = \App\Helpers\CurrencyHelper::getFrontendCurrency($salary->currency_id);
            return $salary;
        });
        $currencies = Currency::all();
        $users = User::select('id', 'name', 'email')->get();

        return Inertia::render('Admin/Business/RecurringSalaries/Index', [
            'salaries' => $salaries,
            'currencies' => $currencies,
            'users' => $users,
        ]);
    }

    public function store_recurring_salaries(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'recurring_times' => 'required|integer|min:1',
            'recurring' => 'required|in:day,week,month,year',
            'currency' => 'required|exists:currencies,id',
        ]);

        $salary = new RecurringSalary();
        $salary->user_id = (int) $request->input('user_id');
        $salary->title = $request->input('title');
        $salary->start_date = $request->input('start_date');
        $salary->current_date = $request->input('start_date');
        $salary->amount = (float) $request->input('amount');
        $salary->currency_id = (int) $request->input('currency');
        $salary->reason = $request->input('reason') ? trim($request->input('reason')) : null;
        $salary->recurring = $request->input('recurring');
        $salary->recurring_times = (int) $request->input('recurring_times');

        if ($request->input('recurring') === 'week') {
            $request->validate(['recurring_times_week' => 'required']);
            $salary->recurring_times_week = implode(',', $request->input('recurring_times_week', []));
        }
        if ($request->input('recurring') === 'month') {
            $request->validate(['recurring_times_month' => 'required']);
            $salary->recurring_times_month = implode(',', $request->input('recurring_times_month', []));
        }
        if ($request->input('recurring') === 'year') {
            $request->validate(['recurring_times_year' => 'required']);
            $salary->recurring_times_year = implode(',', $request->input('recurring_times_year', []));
        }

        $salary->save();
        $salary->apply();

        return redirect()->route('admin.recurring_salaries.index')->with('success', __('general.recurring_salary_added_successfully'));
    }

    public function edit_recurring_salaries($id)
    {
        $salary = RecurringSalary::findOrFail($id);
        $currencies = Currency::all();
        $users = User::select('id', 'name', 'email')->get();

        return Inertia::render('Admin/Business/RecurringSalaries/Edit', [
            'salary' => [
                'id' => $salary->id,
                'user_id' => $salary->user_id,
                'title' => $salary->title,
                'amount' => $salary->amount,
                'currency' => $salary->currency_id,
                'reason' => $salary->reason ?? '',
                'start_date' => $salary->start_date,
                'recurring' => $salary->recurring,
                'recurring_times' => $salary->recurring_times,
                'recurring_times_week' => $salary->recurring_times_week ? explode(',', $salary->recurring_times_week) : [],
                'recurring_times_month' => $salary->recurring_times_month ? array_map('intval', explode(',', $salary->recurring_times_month)) : [],
                'recurring_times_year' => $salary->recurring_times_year ? explode(',', $salary->recurring_times_year) : [],
            ],
            'currencies' => $currencies,
            'users' => $users,
        ]);
    }

    public function update_recurring_salaries(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'recurring_times' => 'required|integer|min:1',
            'recurring' => 'required|in:day,week,month,year',
            'currency' => 'required|exists:currencies,id',
        ]);

        $salary = RecurringSalary::findOrFail($id);
        $salary->user_id = (int) $request->input('user_id');
        $salary->title = $request->input('title');
        $salary->start_date = $request->input('start_date');
        $salary->current_date = $request->input('start_date');
        $salary->amount = (float) $request->input('amount');
        $salary->currency_id = (int) $request->input('currency');
        $salary->reason = $request->input('reason') ? trim($request->input('reason')) : null;
        $salary->recurring = $request->input('recurring');
        $salary->recurring_times = (int) $request->input('recurring_times');

        $salary->recurring_times_week = null;
        $salary->recurring_times_month = null;
        $salary->recurring_times_year = null;

        if ($request->input('recurring') === 'week') {
            $request->validate(['recurring_times_week' => 'required']);
            $salary->recurring_times_week = implode(',', $request->input('recurring_times_week', []));
        }
        if ($request->input('recurring') === 'month') {
            $request->validate(['recurring_times_month' => 'required']);
            $salary->recurring_times_month = implode(',', $request->input('recurring_times_month', []));
        }
        if ($request->input('recurring') === 'year') {
            $request->validate(['recurring_times_year' => 'required']);
            $salary->recurring_times_year = implode(',', $request->input('recurring_times_year', []));
        }

        $salary->save();

        return redirect()->route('admin.recurring_salaries.index')->with('success', __('general.recurring_salary_updated_successfully'));
    }

    public function recurring_salaries_view($id)
    {
        $salary = RecurringSalary::with('user')->findOrFail($id);
        $transactions = $salary->transactions()->latest()->get()->map(function ($tx) {
            return [
                'id' => $tx->id,
                'created_at' => $tx->created_at,
                'amount' => $tx->amount,
                'currency' => \App\Helpers\CurrencyHelper::getFrontendCurrency($tx->currency_id ?? $tx->currency),
                'reason' => $tx->reason,
            ];
        });

        // Compute 15 upcoming schedule dates
        $upcomingSchedule = [];
        $count = 0;
        $baseDate = Carbon::parse($salary->current_date);
        for ($i = 0; $i <= 365 && $count < 15; $i++) {
            $checkDate = $baseDate->copy()->addDays($i);
            if ($salary->isToday($checkDate)) {
                $count++;
                $upcomingSchedule[] = [
                    'date' => $checkDate->toDateString(),
                    'amount_str' => $salary->current_amount_str(),
                    'recorded' => $salary->createdBefore($checkDate),
                ];
            }
        }

        $currencyModel = \App\Helpers\CurrencyHelper::getFrontendCurrency($salary->currency_id);

        return Inertia::render('Admin/Business/RecurringSalaries/View', [
            'salary' => [
                'id' => $salary->id,
                'user' => $salary->user ? ['id' => $salary->user->id, 'name' => $salary->user->name, 'email' => $salary->user->email] : null,
                'title' => $salary->title,
                'amount' => $salary->amount,
                'currency' => $currencyModel,
                'reason' => $salary->reason ?? '',
                'start_date' => $salary->start_date,
                'current_date' => $salary->current_date,
                'recurring' => $salary->recurring,
                'recurring_times' => $salary->recurring_times,
                'details' => $salary->details(),
            ],
            'transactions' => $transactions,
            'upcomingSchedule' => $upcomingSchedule,
            'total_stat' => [
                'entries_count' => $salary->transactions()->count(),
                'total_cost' => \App\Helpers\FinanceHelper::instance()->format_money($salary->transactions()->sum('amount'), $salary->currency_id),
            ]
        ]);
    }

    public function recurring_salaries_delete($id)
    {
        $salary = RecurringSalary::findOrFail($id);
        $salary->delete();
        return redirect()->route('admin.recurring_salaries.index')->with('success', __('general.recurring_salary_deleted'));
    }
}
