<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\BalancesHelper;
use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\CostTransaction;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CostTransactionAuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BusinessController extends Controller
{
    public function __construct(
        protected CostTransactionAuditService $costAudit,
    ) {}
    public function income(Request $request)
    {
        $currentYear = (int) now()->year;
        $year = (int) $request->query('year', $currentYear);
        $month = (int) $request->query('month', now()->month);

        $txMin = Transaction::min('created_at');
        $txMax = Transaction::max('created_at');
        $earliestYear = $txMin ? (int) Carbon::parse($txMin)->year : $currentYear;
        $latestYear = $txMax ? (int) Carbon::parse($txMax)->year : $currentYear;
        $availableYears = range($latestYear, $earliestYear);
        $availableMonths = range(1, 12);

        $incomeQuery = Transaction::with(['user', 'project'])
            ->whereIn('type', ['received', 'refunded', 'sent'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc');

        $entries = $incomeQuery->paginate(50)->withQueryString();

        $currencies = Currency::as_array();

        $entries->getCollection()->transform(function ($entry) use ($currencies) {
            $currId = $entry->currency_id ?? $entry->currency;
            $currRow = isset($currencies[$currId]) ? $currencies[$currId] : null;
            $currencyCode = $currRow ? $currRow->currency : 'EGP';
            $currencySymbol = $currRow ? $currRow->symbol : '£';

            return [
                'id' => $entry->id,
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
                'created_at' => $entry->created_at,
                'type' => $entry->type,
            ];
        });

        $iq = Transaction::whereYear('created_at', $year)->whereMonth('created_at', $month);
        $received = (clone $iq)->where('type', 'received')->sum('business_amount') ?? 0;
        $refunded = (clone $iq)->where('type', 'refunded')->sum('business_amount') ?? 0;
        $sent = (clone $iq)->where('type', 'sent')->sum('business_amount') ?? 0;
        $netIncome = max(0, abs($received) - abs($refunded) - abs($sent));

        $prevMonth = Carbon::createFromDate($year, $month)->subMonth();
        $prevIq = Transaction::whereYear('created_at', $prevMonth->year)->whereMonth('created_at', $prevMonth->month);
        $prevReceived = (clone $prevIq)->where('type', 'received')->sum('business_amount') ?? 0;
        $prevRefunded = (clone $prevIq)->where('type', 'refunded')->sum('business_amount') ?? 0;
        $prevSent = (clone $prevIq)->where('type', 'sent')->sum('business_amount') ?? 0;
        $prevNetIncome = max(0, abs($prevReceived) - abs($prevRefunded) - abs($prevSent));

        $incomeChange = 0;
        if ($prevNetIncome > 0) {
            $incomeChange = (($netIncome - $prevNetIncome) / $prevNetIncome) * 100;
        } elseif ($netIncome > 0) {
            $incomeChange = 100;
        }

        $monthlyTrends = [];
        $trendStart = now()->subMonths(5)->startOfMonth();
        $trendEnd = now()->endOfMonth();

        $txs = Transaction::select('created_at', 'type', 'business_amount')
            ->whereBetween('created_at', [$trendStart, $trendEnd])
            ->whereIn('type', ['received', 'refunded', 'sent'])
            ->get();

        $txData = [];
        foreach ($txs as $tx) {
            $ym = $tx->created_at->format('Y-m');
            $txData[$ym][$tx->type] = ($txData[$ym][$tx->type] ?? 0.0) + (float) $tx->business_amount;
        }

        $costs = CostTransaction::select('created_at', 'business_amount')
            ->excludingSalaries()
            ->whereBetween('created_at', [$trendStart, $trendEnd])
            ->get();

        $costByMonth = [];
        foreach ($costs as $cost) {
            $ym = $cost->created_at->format('Y-m');
            $costByMonth[$ym] = ($costByMonth[$ym] ?? 0.0) + (float) $cost->business_amount;
        }

        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $ym = $m->format('Y-m');
            $mReceived = (float) ($txData[$ym]['received'] ?? 0);
            $mRefunded = (float) ($txData[$ym]['refunded'] ?? 0);
            $mSent = (float) ($txData[$ym]['sent'] ?? 0);
            $mCosts = (float) ($costByMonth[$ym] ?? 0);

            $monthlyTrends[] = [
                'name' => $m->format('M'),
                'income' => max(0, abs($mReceived) - abs($mRefunded) - abs($mSent)),
                'expenses' => abs($mCosts),
            ];
        }

        $monthlyClientData = Transaction::with('user')->whereIn('type', ['received', 'refunded', 'sent'])->whereYear('created_at', $year)->whereMonth('created_at', $month)->get()->groupBy('user_id');
        $monthlyClientBreakdown = [];
        foreach ($monthlyClientData as $userId => $txs) {
            $user = $txs->first()->user;
            $userName = $user ? $user->name : 'Unknown';
            $cReceived = $txs->where('type', 'received')->sum('business_amount');
            $cRefunded = $txs->where('type', 'refunded')->sum('business_amount');
            $cSent = $txs->where('type', 'sent')->sum('business_amount');
            $cNet = max(0, abs($cReceived) - abs($cRefunded) - abs($cSent));
            if ($cNet > 0) {
                $monthlyClientBreakdown[] = ['name' => $userName, 'value' => $cNet];
            }
        }

        $annualClientData = Transaction::with('user')->whereIn('type', ['received', 'refunded', 'sent'])->whereYear('created_at', $year)->get()->groupBy('user_id');
        $annualClientBreakdown = [];
        foreach ($annualClientData as $userId => $txs) {
            $user = $txs->first()->user;
            $userName = $user ? $user->name : 'Unknown';
            $cReceived = $txs->where('type', 'received')->sum('business_amount');
            $cRefunded = $txs->where('type', 'refunded')->sum('business_amount');
            $cSent = $txs->where('type', 'sent')->sum('business_amount');
            $cNet = max(0, abs($cReceived) - abs($cRefunded) - abs($cSent));
            if ($cNet > 0) {
                $annualClientBreakdown[] = ['name' => $userName, 'value' => $cNet];
            }
        }

        usort($monthlyClientBreakdown, fn ($a, $b) => $b['value'] <=> $a['value']);
        usort($annualClientBreakdown, fn ($a, $b) => $b['value'] <=> $a['value']);

        $monthlyCategoryData = Transaction::whereIn('type', ['received', 'refunded', 'sent'])->whereYear('created_at', $year)->whereMonth('created_at', $month)->get()->groupBy('reason');
        $monthlyCategoryBreakdown = [];
        foreach ($monthlyCategoryData as $reason => $txs) {
            $cReceived = $txs->where('type', 'received')->sum('business_amount');
            $cRefunded = $txs->where('type', 'refunded')->sum('business_amount');
            $cSent = $txs->where('type', 'sent')->sum('business_amount');
            $cNet = max(0, abs($cReceived) - abs($cRefunded) - abs($cSent));
            if ($cNet > 0) {
                $monthlyCategoryBreakdown[] = ['name' => ucfirst($reason ?: 'Other'), 'value' => $cNet];
            }
        }

        $annualCategoryData = Transaction::whereIn('type', ['received', 'refunded', 'sent'])->whereYear('created_at', $year)->get()->groupBy('reason');
        $annualCategoryBreakdown = [];
        foreach ($annualCategoryData as $reason => $txs) {
            $cReceived = $txs->where('type', 'received')->sum('business_amount');
            $cRefunded = $txs->where('type', 'refunded')->sum('business_amount');
            $cSent = $txs->where('type', 'sent')->sum('business_amount');
            $cNet = max(0, abs($cReceived) - abs($cRefunded) - abs($cSent));
            if ($cNet > 0) {
                $annualCategoryBreakdown[] = ['name' => ucfirst($reason ?: 'Other'), 'value' => $cNet];
            }
        }

        usort($monthlyCategoryBreakdown, fn ($a, $b) => $b['value'] <=> $a['value']);
        usort($annualCategoryBreakdown, fn ($a, $b) => $b['value'] <=> $a['value']);

        $bCurrency = CurrencyHelper::getBusinessCurrency();

        return Inertia::render('Admin/Business/Income', [
            'entries' => $entries,
            'filters' => ['year' => $year, 'month' => $month, 'available_years' => $availableYears, 'available_months' => $availableMonths],
            'stats' => [
                'total_received' => abs($received),
                'total_refunded' => abs($refunded),
                'total_sent' => abs($sent),
                'total_monthly_income' => $netIncome,
                'previous_month_income' => $prevNetIncome,
                'income_change_percent' => round($incomeChange, 1),
                'monthly_trends' => $monthlyTrends,
                'monthly_client_breakdown' => $monthlyClientBreakdown,
                'annual_client_breakdown' => $annualClientBreakdown,
                'monthly_category_breakdown' => $monthlyCategoryBreakdown,
                'annual_category_breakdown' => $annualCategoryBreakdown,
                'business_currency_code' => $bCurrency['currency'] ?? 'USD',
                'business_currency_symbol' => $bCurrency['symbol'] ?? '$',
            ],
        ]);
    }

    public function costs(Request $request)
    {
        $currentYear = (int) now()->year;
        $year = (int) $request->query('year', $currentYear);
        $month = (int) $request->query('month', now()->month);
        $search = trim((string) $request->query('search', ''));

        $minDate = CostTransaction::min('created_at');
        $maxDate = CostTransaction::max('created_at');
        $earliestYear = $minDate ? (int) Carbon::parse($minDate)->year : $currentYear;
        $latestYear = $maxDate ? (int) Carbon::parse($maxDate)->year : $currentYear;
        if ($earliestYear > $currentYear) {
            $earliestYear = $currentYear;
        }
        if ($latestYear < $currentYear) {
            $latestYear = $currentYear;
        }
        $availableYears = range($latestYear, $earliestYear);
        $availableMonths = range(1, 12);

        $costsQuery = CostTransaction::with(['user', 'project', 'recurringSources'])
            ->excludingSalaries()
            ->inYearMonth($year, $month)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('reason', 'like', "%{$search}%")
                      ->orWhere('amount', 'like', "%{$search}%")
                      ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                      ->orWhereHas('project', fn ($p) => $p->where('project_name', 'like', "%{$search}%"));
                });
            })
            ->orderBy('created_at', 'desc');

        $entries = $costsQuery->paginate(50)->withQueryString();

        $currencies = Currency::as_array();

        $entries->getCollection()->transform(function ($entry) use ($currencies) {
            $currId = $entry->currency_id ?? $entry->currency;
            $currRow = isset($currencies[$currId]) ? $currencies[$currId] : null;
            $currencyCode = $currRow ? $currRow->currency : 'EGP';
            $currencySymbol = $currRow ? $currRow->symbol : 'e£';

            $isRecurring = false;
            $categoryName = $entry->reason;
            $title = $entry->reason;

            $recurringSource = $entry->recurringSources->first();
            if ($recurringSource) {
                $isRecurring = true;
                $categoryName = $recurringSource->reason;
                $title = $recurringSource->title ?: $entry->reason;
            }

            return [
                'id' => $entry->id,
                'title' => ucfirst($title ?: 'Cost'),
                'amount' => $entry->amount,
                'business_amount' => $entry->business_amount,
                'currency' => $currencyCode,
                'currency_symbol' => $currencySymbol,
                'currency_id' => $currId,
                'category' => ['name' => ucfirst($categoryName ?: 'Cost')],
                'is_recurring' => $isRecurring,
                'next_due_date' => $entry->due_date,
                'status' => $entry->status ?? 'completed',
                'user' => $entry->user ? ['id' => $entry->user->id, 'name' => $entry->user->name, 'email' => $entry->user->email] : null,
                'project' => $entry->project ? ['id' => $entry->project->id, 'name' => $entry->project->name] : null,
                'created_at' => $entry->created_at,
                'type' => 'expense',
            ];
        });

        $totalMonthlyCosts = CostTransaction::excludingSalaries()
            ->inYearMonth($year, $month)
            ->sum('business_amount');

        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $mCosts = CostTransaction::excludingSalaries()
                ->inYearMonth($m->year, $m->month)
                ->sum('business_amount') ?? 0;

            $monthlyTrends[] = [
                'name' => $m->format('M'),
                'costs' => abs($mCosts),
            ];
        }

        $bCurrency = CurrencyHelper::getBusinessCurrency();

        return Inertia::render('Admin/Business/Costs', [
            'entries' => $entries,
            'filters' => [
                'year' => $year,
                'month' => $month,
                'search' => $search,
                'available_years' => $availableYears,
                'available_months' => $availableMonths,
            ],
            'stats' => [
                'total_monthly_costs' => abs($totalMonthlyCosts),
                'monthly_trends' => $monthlyTrends,
                'business_currency_code' => $bCurrency['currency'] ?? 'USD',
                'business_currency_symbol' => $bCurrency['symbol'] ?? '$',
            ],
        ]);
    }

    public function create_cost()
    {
        $users = User::select('id', 'name')->get();
        $projects = Project::whereNotIn('status', ['Completed', 'Cancelled'])->select('id', 'project_name as name', 'user_id')->get();
        $currencies = array_values(Currency::as_array());

        $businessCurrency = CurrencyHelper::getBusinessCurrency();

        return Inertia::render('Admin/Business/CostsCreate', [
            'users' => $users,
            'projects' => $projects,
            'currencies' => $currencies,
            'businessCurrency' => $businessCurrency,
        ]);
    }

    public function store_cost(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'reason' => 'required|string|max:255',
            'created_at' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $cost = new CostTransaction;
        $cost->amount = $request->amount;
        $cost->currency_id = $request->currency_id;
        $cost->reason = $request->reason;

        if ($request->filled('created_at')) {
            $cost->created_at = $request->created_at;
        }

        if ($request->filled('user_id')) {
            $cost->user_id = $request->user_id;
        }

        if ($request->filled('project_id')) {
            $cost->project_id = $request->project_id;
        }

        DB::transaction(function () use ($cost) {
            $cost->save();
            if ($cost->user_id) {
                $user = User::find($cost->user_id);
                if ($user) {
                    BalancesHelper::instance()->CalcCostBalance($user);
                }
            }
        });

        $this->costAudit->log(
            CostTransactionAuditService::ACTION_CREATED,
            $cost->id,
            [
                'amount' => $cost->amount,
                'currency_id' => $cost->currency_id,
                'reason' => $cost->reason,
                'user_id' => $cost->user_id,
                'project_id' => $cost->project_id,
            ]
        );

        return redirect()->route('admin.costs.index')->with('success', __('general.saved_successfully'));
    }

    public function edit_cost($id)
    {
        $cost = CostTransaction::findOrFail($id);

        $users = User::select('id', 'name')->get();
        $projects = Project::whereNotIn('status', ['Completed', 'Cancelled'])->select('id', 'project_name as name', 'user_id')->get();
        $currencies = array_values(Currency::as_array());

        $businessCurrency = CurrencyHelper::getBusinessCurrency();

        return Inertia::render('Admin/Business/CostsEdit', [
            'cost' => $cost,
            'users' => $users,
            'projects' => $projects,
            'currencies' => $currencies,
            'businessCurrency' => $businessCurrency,
        ]);
    }

    public function update_cost(Request $request, $id)
    {
        $cost = CostTransaction::findOrFail($id);
        $before = $cost->only(['amount', 'currency_id', 'reason', 'user_id', 'project_id']);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'reason' => 'required|string|max:255',
            'created_at' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        DB::transaction(function () use ($cost, $request) {
            $cost->amount = $request->amount;
            $cost->currency_id = $request->currency_id;
            $cost->reason = $request->reason;

            if ($request->filled('created_at')) {
                $cost->created_at = $request->created_at;
            }

            $cost->user_id = $request->filled('user_id') ? $request->user_id : null;
            $cost->project_id = $request->filled('project_id') ? $request->project_id : null;

            $cost->save();

            if ($cost->user_id) {
                $user = User::find($cost->user_id);
                if ($user) {
                    BalancesHelper::instance()->CalcCostBalance($user);
                }
            }
        });

        $after = $cost->fresh()->only(['amount', 'currency_id', 'reason', 'user_id', 'project_id']);
        $changed = array_keys(array_diff_assoc($after, $before));

        if ($changed !== []) {
            $this->costAudit->log(
                CostTransactionAuditService::ACTION_UPDATED,
                $cost->id,
                [
                    'changed' => $changed,
                    'before' => array_intersect_key($before, array_flip($changed)),
                    'after' => array_intersect_key($after, array_flip($changed)),
                ]
            );
        }

        return redirect()->route('admin.costs.index')->with('success', __('general.saved_successfully'));
    }

    public function delete_cost($id)
    {
        $cost = CostTransaction::findOrFail($id);
        $snapshot = $cost->only(['amount', 'currency_id', 'reason', 'user_id', 'project_id']);

        DB::transaction(function () use ($cost) {
            $userId = $cost->user_id;
            $cost->delete();

            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    BalancesHelper::instance()->CalcCostBalance($user);
                }
            }
        });

        $this->costAudit->log(
            CostTransactionAuditService::ACTION_DELETED,
            $id,
            $snapshot
        );

        return redirect()->route('admin.costs.index')->with('success', __('general.deleted_successfully'));
    }

    public function delete_income($id)
    {
        $transaction = Transaction::findOrFail($id);

        if ($transaction->isReversed() || $transaction->isReverseTransaction()) {
            return redirect()->back()->with('error', 'Cannot delete a reversed or reversal transaction. It must be kept for ledger integrity.');
        }

        $transaction->delete_with_balance();

        return redirect()->route('admin.income.index')->with('success', __('general.deleted_successfully'));
    }

    public function reverse_income(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        $transaction = Transaction::findOrFail($id);

        try {
            $transaction->createReverse($request->reason);

            return redirect()->route('admin.income.index')->with('success', 'Transaction reversed successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function reports(Request $request)
    {
        $bCurrency = CurrencyHelper::getBusinessCurrency();

        $lifetimeReceived = Transaction::where('type', 'received')->sum('business_amount') ?? 0;
        $lifetimeRefunded = Transaction::where('type', 'refunded')->sum('business_amount') ?? 0;
        $lifetimeSent = Transaction::where('type', 'sent')->sum('business_amount') ?? 0;
        $lifetimeIncome = max(0, abs($lifetimeReceived) - abs($lifetimeRefunded) - abs($lifetimeSent));

        $lifetimeExpenses = CostTransaction::sum('business_amount') ?? 0;
        $netProfit = $lifetimeIncome - abs($lifetimeExpenses);

        return Inertia::render('Admin/Business/Reports', [
            'stats' => [
                'total_users' => User::count(),
                'total_projects' => Project::count(),
                'total_invoices' => Invoice::count(),
                'total_transactions' => Transaction::count(),
                'lifetime_income' => $lifetimeIncome,
                'lifetime_expenses' => abs($lifetimeExpenses),
                'net_profit' => $netProfit,
                'business_currency_code' => $bCurrency['currency'] ?? 'USD',
                'business_currency_symbol' => $bCurrency['symbol'] ?? '$',
            ],
        ]);
    }

    public function balance(Request $request)
    {
        $currentYear = (int) now()->year;
        $year = (int) $request->query('year', $currentYear);

        $txnMin = Transaction::min('created_at');
        $txnMax = Transaction::max('created_at');
        $costMin = CostTransaction::min('created_at');
        $costMax = CostTransaction::max('created_at');

        $mins = array_filter([$txnMin, $costMin]);
        $maxes = array_filter([$txnMax, $costMax]);

        if (! empty($mins) && ! empty($maxes)) {
            $earliestYear = (int) Carbon::parse(min($mins))->year;
            $latestYear = (int) Carbon::parse(max($maxes))->year;
        } else {
            $earliestYear = $currentYear;
            $latestYear = $currentYear;
        }

        $availableYears = range($latestYear, $earliestYear);

        $monthlyTrends = [];
        for ($i = 1; $i <= 12; $i++) {
            $mReceived = Transaction::whereYear('created_at', $year)->whereMonth('created_at', $i)->where('type', 'received')->sum('business_amount') ?? 0;
            $mRefunded = Transaction::whereYear('created_at', $year)->whereMonth('created_at', $i)->where('type', 'refunded')->sum('business_amount') ?? 0;
            $mSent = Transaction::whereYear('created_at', $year)->whereMonth('created_at', $i)->where('type', 'sent')->sum('business_amount') ?? 0;

            $income = max(0, abs($mReceived) - abs($mRefunded) - abs($mSent));
            $costs = CostTransaction::whereYear('created_at', $year)->whereMonth('created_at', $i)->sum('business_amount') ?? 0;
            $profit = $income - abs($costs);

            $monthlyTrends[] = [
                'name' => Carbon::create()->month($i)->format('M'),
                'income' => $income,
                'costs' => abs($costs),
                'profit' => $profit,
            ];
        }

        $bCurrency = CurrencyHelper::getBusinessCurrency();

        $totalYearIncome = array_sum(array_column($monthlyTrends, 'income'));
        $totalYearCosts = array_sum(array_column($monthlyTrends, 'costs'));
        $totalYearProfit = array_sum(array_column($monthlyTrends, 'profit'));

        return Inertia::render('Admin/Business/BalanceReport', [
            'stats' => [
                'year' => $year,
                'available_years' => $availableYears,
                'monthly_trends' => $monthlyTrends,
                'total_income' => $totalYearIncome,
                'total_costs' => $totalYearCosts,
                'total_profit' => $totalYearProfit,
                'business_currency_code' => $bCurrency['currency'] ?? 'USD',
                'business_currency_symbol' => $bCurrency['symbol'] ?? '$',
            ],
        ]);
    }
}
