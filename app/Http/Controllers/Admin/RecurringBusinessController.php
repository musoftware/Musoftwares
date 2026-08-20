<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\CurrencyHelper;
use App\Helpers\FinanceHelper;
use App\Http\Controllers\Controller;
use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\RecurringCost;
use App\Models\RecurringIncome;
use App\Models\RecurringSalary;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class RecurringBusinessController extends Controller
{
    // ==========================================
    // RECURRING COSTS ACTIONS
    // ==========================================

    public function recurring_costs(Request $request)
    {
        $costs = RecurringCost::withCount('transactions')->latest()->paginate(50);
        $costs->getCollection()->transform(function ($cost) {
            $cost->currency = CurrencyHelper::getFrontendCurrency($cost->currency_id);
            $cost->next_date = $cost->getNextExecutionDate()?->toDateString();

            return $cost;
        });
        $currencies = Currency::all();

        $costReasons = CostTransaction::select('reason')->distinct()->pluck('reason');
        $recurringReasons = RecurringCost::select('reason')->distinct()->pluck('reason');
        $categories = $costReasons->concat($recurringReasons)->unique()->filter()->values();

        $bCurrency = CurrencyHelper::getBusinessCurrency();

        $stats = [
            'monthly_total' => RecurringCost::monthly_str(),
            'annual_total' => RecurringCost::annual_str(),
            'business_currency_code' => $bCurrency['currency'] ?? 'USD',
            'business_currency_symbol' => $bCurrency['symbol'] ?? '$',
            'chart_data' => RecurringCost::chartData(),
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

        $bCurrency = CurrencyHelper::getBusinessCurrency();

        $stats = [
            'business_currency_code' => $bCurrency['currency'] ?? 'USD',
            'business_currency_symbol' => $bCurrency['symbol'] ?? '$',
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

        $rCost = new RecurringCost;
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
                'currency' => CurrencyHelper::getFrontendCurrency($tx->currency_id ?? $tx->currency),
                'reason' => $tx->reason,
            ];
        });

        // Compute 15 upcoming schedule dates
        $upcomingSchedule = [];
        $count = 0;
        $baseDate = Carbon::today(config('app.timezone', 'Africa/Cairo'));
        for ($i = 0; $i <= 1826 && $count < 15; $i++) {
            $checkDate = $baseDate->copy()->addDays($i);
            if ($rCost->isToday($checkDate)) {
                $count++;
                $uniqueId = $rCost->id.'-'.$checkDate->toDateString();
                $isRecorded = $rCost->createdBefore($checkDate);

                // Fetch the actual transaction amount if this date was already executed
                $actualAmountStr = null;
                if ($isRecorded) {
                    $pivot = DB::table('recurring_cost_transactions')
                        ->where('unique_id', $uniqueId)
                        ->first();
                    if ($pivot && $pivot->cost_transaction_id) {
                        $actualTx = CostTransaction::find($pivot->cost_transaction_id);
                        if ($actualTx) {
                            $actualAmountStr = FinanceHelper::instance()->format_money($actualTx->amount, $actualTx->currency_id ?? $rCost->currency_id);
                        }
                    }
                }

                $upcomingSchedule[] = [
                    'date' => $checkDate->toDateString(),
                    'amount_str' => $actualAmountStr ?? $rCost->current_amount_str(),
                    'is_actual' => $actualAmountStr !== null,
                    'recorded' => $isRecorded,
                ];
            }
        }

        $currencyModel = CurrencyHelper::getFrontendCurrency($rCost->currency_id);

        return Inertia::render('Admin/Business/RecurringCosts/View', [
            'cost' => [
                'id' => $rCost->id,
                'title' => $rCost->title,
                'amount' => $rCost->amount,
                'currency' => $currencyModel,
                'reason' => $rCost->reason,
                'start_date' => $rCost->start_date,
                'current_date' => $rCost->current_date,
                'next_date' => $rCost->getNextExecutionDate()?->toDateString(),
                'recurring' => $rCost->recurring,
                'recurring_times' => $rCost->recurring_times,
                'details' => $rCost->details(),
            ],
            'transactions' => $transactions,
            'upcomingSchedule' => $upcomingSchedule,
            'total_stat' => [
                'entries_count' => $rCost->transactions()->count(),
                'total_cost' => FinanceHelper::instance()->format_money($rCost->transactions()->sum('amount'), $rCost->currency_id),
            ],
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

    public function toggle_recurring_costs($id)
    {
        $rCost = RecurringCost::findOrFail($id);
        $rCost->is_active = ! $rCost->is_active;
        $rCost->save();

        return redirect()->back()->with('success', __('general.status_updated_successfully'));
    }

    public function recurring_costs_generate_missing($id)
    {
        $rCost = RecurringCost::findOrFail($id);
        $count = $rCost->generateMissingRuns();

        if ($count > 0) {
            return redirect()->back()->with('success', __('general.generated_missing_transactions_count', ['count' => $count]));
        }

        return redirect()->back()->with('info', __('general.all_transactions_already_up_to_date'));
    }

    // ==========================================
    // RECURRING INCOME ACTIONS
    // ==========================================

    public function recurring_income(Request $request)
    {
        $incomes = RecurringIncome::withCount('transactions')->latest()->paginate(50);
        $incomes->getCollection()->transform(function ($income) {
            $income->currency = CurrencyHelper::getFrontendCurrency($income->currency_id);
            $income->next_date = $income->getNextExecutionDate()?->toDateString();

            return $income;
        });
        $currencies = Currency::all();

        $incomeReasons = Transaction::whereIn('type', ['received', 'refunded', 'sent'])->select('reason')->distinct()->pluck('reason');
        $recurringReasons = RecurringIncome::select('reason')->distinct()->pluck('reason');
        $categories = $incomeReasons->concat($recurringReasons)->unique()->filter()->values();

        $bCurrency = CurrencyHelper::getBusinessCurrency();

        $stats = [
            'monthly_total' => RecurringIncome::monthly_str(),
            'annual_total' => RecurringIncome::annual_str(),
            'business_currency_code' => $bCurrency['currency'] ?? 'USD',
            'business_currency_symbol' => $bCurrency['symbol'] ?? '$',
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

        $rIncome = new RecurringIncome;
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

        $incomeReasons = Transaction::whereIn('type', ['received', 'refunded', 'sent'])->select('reason')->distinct()->pluck('reason');
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
                'currency' => CurrencyHelper::getFrontendCurrency($tx->currency_id ?? $tx->currency),
                'reason' => $tx->reason,
            ];
        });

        // Compute 15 upcoming schedule dates
        $upcomingSchedule = [];
        $count = 0;
        $baseDate = Carbon::today(config('app.timezone', 'Africa/Cairo'));
        for ($i = 0; $i <= 1826 && $count < 15; $i++) {
            $checkDate = $baseDate->copy()->addDays($i);
            if ($rIncome->isToday($checkDate)) {
                $count++;
                $uniqueId = $rIncome->id.'-'.$checkDate->toDateString();
                $isRecorded = $rIncome->createdBefore($checkDate);

                // Fetch the actual transaction amount if this date was already executed
                $actualAmountStr = null;
                if ($isRecorded) {
                    $pivot = DB::table('recurring_income_transactions')
                        ->where('unique_id', $uniqueId)
                        ->first();
                    if ($pivot && $pivot->transaction_id) {
                        $actualTx = Transaction::find($pivot->transaction_id);
                        if ($actualTx) {
                            $actualAmountStr = FinanceHelper::instance()->format_money($actualTx->amount, $actualTx->currency_id ?? $rIncome->currency_id);
                        }
                    }
                }

                $upcomingSchedule[] = [
                    'date' => $checkDate->toDateString(),
                    'amount_str' => $actualAmountStr ?? $rIncome->current_amount_str(),
                    'is_actual' => $actualAmountStr !== null,
                    'recorded' => $isRecorded,
                ];
            }
        }

        $currencyModel = CurrencyHelper::getFrontendCurrency($rIncome->currency_id);

        return Inertia::render('Admin/Business/RecurringIncome/View', [
            'income' => [
                'id' => $rIncome->id,
                'title' => $rIncome->title,
                'amount' => $rIncome->amount,
                'currency' => $currencyModel,
                'reason' => $rIncome->reason,
                'start_date' => $rIncome->start_date,
                'current_date' => $rIncome->current_date,
                'next_date' => $rIncome->getNextExecutionDate()?->toDateString(),
                'recurring' => $rIncome->recurring,
                'recurring_times' => $rIncome->recurring_times,
                'details' => $rIncome->details(),
            ],
            'transactions' => $transactions,
            'upcomingSchedule' => $upcomingSchedule,
            'total_stat' => [
                'entries_count' => $rIncome->transactions()->count(),
                'total_cost' => FinanceHelper::instance()->format_money($rIncome->transactions()->sum('amount'), $rIncome->currency_id),
            ],
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

    public function toggle_recurring_income($id)
    {
        $rIncome = RecurringIncome::findOrFail($id);
        $rIncome->is_active = ! $rIncome->is_active;
        $rIncome->save();

        return redirect()->back()->with('success', __('general.status_updated_successfully'));
    }

    public function recurring_income_generate_missing($id)
    {
        $rIncome = RecurringIncome::findOrFail($id);
        $count = $rIncome->generateMissingRuns();

        if ($count > 0) {
            return redirect()->back()->with('success', __('general.generated_missing_transactions_count', ['count' => $count]));
        }

        return redirect()->back()->with('info', __('general.all_transactions_already_up_to_date'));
    }

    // ==========================================
    // RECURRING SALARIES ACTIONS
    // ==========================================

    public function recurring_salaries(Request $request)
    {
        $salaries = RecurringSalary::with('user')->withCount('transactions')->latest()->paginate(50);
        $salaries->getCollection()->transform(function ($salary) {
            $salary->currency = CurrencyHelper::getFrontendCurrency($salary->currency_id);
            $salary->next_date = $salary->getNextExecutionDate()?->toDateString();

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

        $salary = new RecurringSalary;
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
                'currency' => CurrencyHelper::getFrontendCurrency($tx->currency_id ?? $tx->currency),
                'reason' => $tx->reason,
            ];
        });

        // Compute 15 upcoming schedule dates
        $upcomingSchedule = [];
        $count = 0;
        $baseDate = Carbon::today(config('app.timezone', 'Africa/Cairo'));
        for ($i = 0; $i <= 1826 && $count < 15; $i++) {
            $checkDate = $baseDate->copy()->addDays($i);
            if ($salary->isToday($checkDate)) {
                $count++;
                $uniqueId = $salary->id.'-'.$checkDate->toDateString();
                $isRecorded = $salary->createdBefore($checkDate);

                // Fetch the actual transaction amount if this date was already executed
                $actualAmountStr = null;
                if ($isRecorded) {
                    $pivot = DB::table('recurring_salary_transactions')
                        ->where('unique_id', $uniqueId)
                        ->first();
                    if ($pivot) {
                        // Salaries link to transactions table (via transaction_id)
                        $txId = $pivot->transaction_id ?? null;
                        if ($txId) {
                            $actualTx = Transaction::find($txId);
                            if ($actualTx) {
                                $actualAmountStr = FinanceHelper::instance()->format_money($actualTx->amount, $actualTx->currency_id ?? $salary->currency_id);
                            }
                        }
                    }
                }

                $upcomingSchedule[] = [
                    'date' => $checkDate->toDateString(),
                    'amount_str' => $actualAmountStr ?? $salary->current_amount_str(),
                    'is_actual' => $actualAmountStr !== null,
                    'recorded' => $isRecorded,
                ];
            }
        }

        $currencyModel = CurrencyHelper::getFrontendCurrency($salary->currency_id);

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
                'next_date' => $salary->getNextExecutionDate()?->toDateString(),
                'recurring' => $salary->recurring,
                'recurring_times' => $salary->recurring_times,
                'details' => $salary->details(),
            ],
            'transactions' => $transactions,
            'upcomingSchedule' => $upcomingSchedule,
            'total_stat' => [
                'entries_count' => $salary->transactions()->count(),
                'total_cost' => FinanceHelper::instance()->format_money($salary->transactions()->sum('amount'), $salary->currency_id),
            ],
        ]);
    }

    public function recurring_salaries_delete($id)
    {
        $salary = RecurringSalary::findOrFail($id);
        $salary->delete();

        return redirect()->route('admin.recurring_salaries.index')->with('success', __('general.recurring_salary_deleted'));
    }

    public function toggle_recurring_salaries($id)
    {
        $salary = RecurringSalary::findOrFail($id);
        $salary->is_active = ! $salary->is_active;
        $salary->save();

        return redirect()->back()->with('success', __('general.status_updated_successfully'));
    }

    public function recurring_salaries_generate_missing($id)
    {
        $salary = RecurringSalary::findOrFail($id);
        $count = $salary->generateMissingRuns();

        if ($count > 0) {
            return redirect()->back()->with('success', __('general.generated_missing_transactions_count', ['count' => $count]));
        }

        return redirect()->back()->with('info', __('general.all_transactions_already_up_to_date'));
    }
}
