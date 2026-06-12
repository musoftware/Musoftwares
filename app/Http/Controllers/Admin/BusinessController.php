<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\CostTransaction;
use App\Models\Transaction;

class BusinessController extends Controller
{
    public function income(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $incomeQuery = Transaction::with(['user', 'project'])
            ->whereIn('type', ['received', 'refunded', 'sent'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc');

        $entries = $incomeQuery->paginate(50)->withQueryString();

        $currencies = \App\Models\Currency::as_array();
        
        $entries->getCollection()->transform(function ($entry) use ($currencies) {
            $currId = $entry->currency_id ?? $entry->currency;
            $currRow = isset($currencies[$currId]) ? $currencies[$currId] : null;
            $currencyCode = $currRow ? $currRow->currency : 'EGP';
            $currencySymbol = $currRow ? $currRow->symbol : 'e£';

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

        // Calc stats
        $iq = Transaction::whereYear('created_at', $year)->whereMonth('created_at', $month);
        $received = (clone $iq)->where('type', 'received')->sum('business_amount') ?? 0;
        $refunded = (clone $iq)->where('type', 'refunded')->sum('business_amount') ?? 0;
        $sent = (clone $iq)->where('type', 'sent')->sum('business_amount') ?? 0;
        $netIncome = max(0, abs($received) - abs($refunded) - abs($sent));

        // Lifetime stats removed per user request
        // For Chart
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $mReceived = Transaction::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->where('type', 'received')->sum('business_amount') ?? 0;
            $mRefunded = Transaction::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->where('type', 'refunded')->sum('business_amount') ?? 0;
            $mSent = Transaction::whereYear('created_at', $m->year)->whereMonth('created_at', $m->month)->where('type', 'sent')->sum('business_amount') ?? 0;
            
            $monthlyTrends[] = [
                'name' => $m->format('M'),
                'income' => max(0, abs($mReceived) - abs($mRefunded) - abs($mSent)),
            ];
        }

        // Client Breakdown (Monthly & Annually)
        $monthlyClientData = Transaction::with('user')
            ->whereIn('type', ['received', 'refunded', 'sent'])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->get()
            ->groupBy('user_id');

        $monthlyClientBreakdown = [];
        foreach ($monthlyClientData as $userId => $txs) {
            $user = $txs->first()->user;
            $userName = $user ? $user->name : 'Unknown';
            $cReceived = $txs->where('type', 'received')->sum('business_amount');
            $cRefunded = $txs->where('type', 'refunded')->sum('business_amount');
            $cSent = $txs->where('type', 'sent')->sum('business_amount');
            $cNet = max(0, abs($cReceived) - abs($cRefunded) - abs($cSent));
            if ($cNet > 0) {
                $monthlyClientBreakdown[] = [
                    'name' => $userName,
                    'value' => $cNet,
                ];
            }
        }

        $annualClientData = Transaction::with('user')
            ->whereIn('type', ['received', 'refunded', 'sent'])
            ->whereYear('created_at', $year)
            ->get()
            ->groupBy('user_id');

        $annualClientBreakdown = [];
        foreach ($annualClientData as $userId => $txs) {
            $user = $txs->first()->user;
            $userName = $user ? $user->name : 'Unknown';
            $cReceived = $txs->where('type', 'received')->sum('business_amount');
            $cRefunded = $txs->where('type', 'refunded')->sum('business_amount');
            $cSent = $txs->where('type', 'sent')->sum('business_amount');
            $cNet = max(0, abs($cReceived) - abs($cRefunded) - abs($cSent));
            if ($cNet > 0) {
                $annualClientBreakdown[] = [
                    'name' => $userName,
                    'value' => $cNet,
                ];
            }
        }

        usort($monthlyClientBreakdown, fn($a, $b) => $b['value'] <=> $a['value']);
        usort($annualClientBreakdown, fn($a, $b) => $b['value'] <=> $a['value']);

        $bCurrencyId = \App\Models\AdminSettings::business_currency();
        $bCurrency = \App\Models\Currency::find($bCurrencyId);

        return Inertia::render('Admin/Business/Income', [
            'entries' => $entries,
            'stats' => [
                'total_monthly_income' => $netIncome,
                'monthly_trends' => $monthlyTrends,
                'monthly_client_breakdown' => $monthlyClientBreakdown,
                'annual_client_breakdown' => $annualClientBreakdown,
                'business_currency_code' => $bCurrency ? $bCurrency->currency : 'EGP',
                'business_currency_symbol' => $bCurrency ? $bCurrency->symbol : 'e£',
            ]
        ]);
    }

    public function costs(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        $month = (int) $request->query('month', now()->month);

        $costsQuery = CostTransaction::with(['user', 'project'])
            ->where('reason', '!=', 'salary')
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('created_at', 'desc');

        $entries = $costsQuery->paginate(50)->withQueryString();

        $currencies = \App\Models\Currency::as_array();
        
        $entries->getCollection()->transform(function ($entry) use ($currencies) {
            $currId = $entry->currency_id ?? $entry->currency;
            $currRow = isset($currencies[$currId]) ? $currencies[$currId] : null;
            $currencyCode = $currRow ? $currRow->currency : 'EGP';
            $currencySymbol = $currRow ? $currRow->symbol : 'e£';

            $isRecurring = false;
            $categoryName = $entry->reason;
            $title = $entry->reason;

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
            } catch (\Throwable $e) {}

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

        $totalMonthlyCosts = CostTransaction::whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->where('reason', '!=', 'salary')
            ->sum('business_amount');



        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $mCosts = CostTransaction::whereYear('created_at', $m->year)
                ->whereMonth('created_at', $m->month)
                ->where('reason', '!=', 'salary')
                ->sum('business_amount') ?? 0;
            
            $monthlyTrends[] = [
                'name' => $m->format('M'),
                'costs' => abs($mCosts),
            ];
        }

        $bCurrencyId = \App\Models\AdminSettings::business_currency();
        $bCurrency = \App\Models\Currency::find($bCurrencyId);

        return Inertia::render('Admin/Business/Costs', [
            'entries' => $entries,
            'stats' => [
                'total_monthly_costs' => abs($totalMonthlyCosts),
                'monthly_trends' => $monthlyTrends,
                'business_currency_code' => $bCurrency ? $bCurrency->currency : 'EGP',
                'business_currency_symbol' => $bCurrency ? $bCurrency->symbol : 'e£',
            ]
        ]);
    }

    public function create_cost()
    {
        $users = \App\Models\User::select('id', 'name')->get();
        $projects = \App\Models\Project::whereNotIn('status', ['Completed', 'Cancelled'])->select('id', 'project_name as name', 'client_id')->get();
        $currencies = array_values(\App\Models\Currency::as_array());
        
        $businessCurrency = \App\Helpers\CurrencyHelper::getBusinessCurrency();

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

        $cost = new CostTransaction();
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

        \Illuminate\Support\Facades\DB::transaction(function () use ($cost) {
            $cost->save();
            if ($cost->user_id) {
                \App\Models\User::find($cost->user_id)->increment('total_cost', $cost->amount);
            }
        });

        return redirect()->route('admin.costs.index')->with('success', __('general.saved_successfully'));
    }

    public function reports(Request $request)
    {
        $bCurrencyId = \App\Models\AdminSettings::business_currency();
        $bCurrency = \App\Models\Currency::find($bCurrencyId);

        $lifetimeReceived = Transaction::where('type', 'received')->sum('business_amount') ?? 0;
        $lifetimeRefunded = Transaction::where('type', 'refunded')->sum('business_amount') ?? 0;
        $lifetimeSent = Transaction::where('type', 'sent')->sum('business_amount') ?? 0;
        $lifetimeIncome = max(0, abs($lifetimeReceived) - abs($lifetimeRefunded) - abs($lifetimeSent));

        $lifetimeExpenses = CostTransaction::sum('business_amount') ?? 0;
        $netProfit = $lifetimeIncome - abs($lifetimeExpenses);

        return Inertia::render('Admin/Business/Reports', [
            'stats' => [
                'total_users' => \App\Models\User::count(),
                'total_projects' => \App\Models\Project::count(),
                'total_invoices' => \App\Models\Invoice::count(),
                'total_transactions' => Transaction::count(),
                'lifetime_income' => $lifetimeIncome,
                'lifetime_expenses' => abs($lifetimeExpenses),
                'net_profit' => $netProfit,
                'business_currency_code' => $bCurrency ? $bCurrency->currency : 'EGP',
                'business_currency_symbol' => $bCurrency ? $bCurrency->symbol : 'e£',
            ]
        ]);
    }

    public function balance(Request $request)
    {
        $year = (int) $request->query('year', now()->year);
        
        $monthlyTrends = [];
        for ($i = 1; $i <= 12; $i++) {
            $mReceived = Transaction::whereYear('created_at', $year)->whereMonth('created_at', $i)->where('type', 'received')->sum('business_amount') ?? 0;
            $mRefunded = Transaction::whereYear('created_at', $year)->whereMonth('created_at', $i)->where('type', 'refunded')->sum('business_amount') ?? 0;
            $mSent = Transaction::whereYear('created_at', $year)->whereMonth('created_at', $i)->where('type', 'sent')->sum('business_amount') ?? 0;
            
            $income = max(0, abs($mReceived) - abs($mRefunded) - abs($mSent));
            $costs = CostTransaction::whereYear('created_at', $year)->whereMonth('created_at', $i)->sum('business_amount') ?? 0;
            $profit = $income - abs($costs);

            $monthlyTrends[] = [
                'name' => \Carbon\Carbon::create()->month($i)->format('M'),
                'income' => $income,
                'costs' => abs($costs),
                'profit' => $profit
            ];
        }

        $bCurrencyId = \App\Models\AdminSettings::business_currency();
        $bCurrency = \App\Models\Currency::find($bCurrencyId);

        $totalYearIncome = array_sum(array_column($monthlyTrends, 'income'));
        $totalYearCosts = array_sum(array_column($monthlyTrends, 'costs'));
        $totalYearProfit = array_sum(array_column($monthlyTrends, 'profit'));

        return Inertia::render('Admin/Business/BalanceReport', [
            'stats' => [
                'year' => $year,
                'monthly_trends' => $monthlyTrends,
                'total_income' => $totalYearIncome,
                'total_costs' => $totalYearCosts,
                'total_profit' => $totalYearProfit,
                'business_currency_code' => $bCurrency ? $bCurrency->currency : 'EGP',
                'business_currency_symbol' => $bCurrency ? $bCurrency->symbol : 'e£',
            ]
        ]);
    }
}
