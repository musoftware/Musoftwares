<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\BalancesHelper;
use App\Helpers\CurrencyHelper;
use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\CostTransaction;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItemTimer;
use App\Models\Project;
use App\Models\RecurringCost;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CostTransactionAuditService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $search = trim((string) $request->query('search', ''));
        $projectId = $request->query('project_id');
        $userId = $request->query('user_id');
        $currencyId = $request->query('currency_id');
        $category = $request->query('category');
        $minAmount = $request->query('min_amount');
        $maxAmount = $request->query('max_amount');
        $preset = $request->query('preset');
        $withTrashed = filter_var($request->query('with_trashed', false), FILTER_VALIDATE_BOOLEAN);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'amount', 'business_amount', 'reason'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

        $txMin = Transaction::min('created_at');
        $txMax = Transaction::max('created_at');
        $earliestYear = $txMin ? (int) Carbon::parse($txMin)->year : $currentYear;
        $latestYear = $txMax ? (int) Carbon::parse($txMax)->year : $currentYear;
        if ($earliestYear > $currentYear) {
            $earliestYear = $currentYear;
        }
        if ($latestYear < $currentYear) {
            $latestYear = $currentYear;
        }
        $availableYears = range($latestYear, $earliestYear);
        $availableMonths = range(1, 12);

        $baseQuery = Transaction::whereIn('type', ['received', 'refunded', 'sent']);
        if ($preset === 'all') {
            $incomeQuery = (clone $baseQuery);
        } elseif ($preset === 'last_30') {
            $incomeQuery = (clone $baseQuery)->where('created_at', '>=', now()->subDays(30));
        } elseif ($preset === 'last_90') {
            $incomeQuery = (clone $baseQuery)->where('created_at', '>=', now()->subDays(90));
        } elseif ($preset === 'ytd') {
            $incomeQuery = (clone $baseQuery)->whereYear('created_at', $currentYear);
        } else {
            $incomeQuery = (clone $baseQuery)->whereYear('created_at', $year)->whereMonth('created_at', $month);
        }

        $incomeQuery = $incomeQuery->with(['user', 'project'])
            ->when($category, function ($q) use ($category) {
                $q->where('category', $category);
            })
            ->when($userId, function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
            ->when($projectId, function ($q) use ($projectId) {
                $q->where('project_id', $projectId);
            })
            ->when($currencyId, function ($q) use ($currencyId) {
                $q->where('currency_id', $currencyId);
            })
            ->when($minAmount !== null && $minAmount !== '', function ($q) use ($minAmount) {
                $q->where('amount', '>=', (float) $minAmount);
            })
            ->when($maxAmount !== null && $maxAmount !== '', function ($q) use ($maxAmount) {
                $q->where('amount', '<=', (float) $maxAmount);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('reason', 'like', "%{$search}%")
                        ->orWhere('amount', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('project', fn ($p) => $p->where('project_name', 'like', "%{$search}%"));
                });
            });

        if ($withTrashed) {
            $incomeQuery->withTrashed();
        }

        $incomeQuery->orderBy($sortBy, $sortDir);

        $entries = $incomeQuery->paginate(25)->withQueryString();

        $currencies = Currency::as_array();

        $entries->getCollection()->transform(function ($entry) use ($currencies) {
            $currId = $entry->currency_id ?? $entry->currency;
            $currRow = isset($currencies[$currId]) ? $currencies[$currId] : null;
            $currencyCode = $currRow ? $currRow->currency : 'EGP';
            $currencySymbol = $currRow ? $currRow->symbol : '£';

            $categoryName = $entry->category ?: $entry->reason;

            return [
                'id' => $entry->id,
                'title' => ucfirst($entry->reason ?: 'Income'),
                'reason' => $entry->reason,
                'amount' => $entry->amount,
                'business_amount' => $entry->business_amount,
                'currency' => $currencyCode,
                'currency_symbol' => $currencySymbol,
                'currency_id' => $currId,
                'category' => ['name' => ucfirst($categoryName ?: 'Income')],
                'category_slug' => Str::slug((string) ($categoryName ?? '')),
                'category_raw' => $categoryName,
                'is_recurring' => false,
                'next_due_date' => $entry->due_date,
                'status' => $entry->status ?? 'completed',
                'user' => $entry->user ? ['id' => $entry->user->id, 'name' => $entry->user->name, 'email' => $entry->user->email] : null,
                'project' => $entry->project ? ['id' => $entry->project->id, 'name' => $entry->project->project_name] : null,
                'created_at' => $entry->created_at,
                'updated_at' => $entry->updated_at,
                'deleted_at' => $entry->deleted_at,
                'type' => $entry->type,
            ];
        });

        // Statistics relative to selected filter / month
        $received = (clone $incomeQuery)->where('type', 'received')->sum('business_amount') ?? 0;
        $refunded = (clone $incomeQuery)->where('type', 'refunded')->sum('business_amount') ?? 0;
        $sent = (clone $incomeQuery)->where('type', 'sent')->sum('business_amount') ?? 0;
        $netIncome = max(0, abs($received) - abs($refunded) - abs($sent));

        $prevMonth = Carbon::createFromDate($year, $month)->subMonth();
        $prevIq = Transaction::whereIn('type', ['received', 'refunded', 'sent'])->whereYear('created_at', $prevMonth->year)->whereMonth('created_at', $prevMonth->month);
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

        // Trends
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

        // Client and Category breakdowns (relative to selected time filter)
        $monthlyClientData = (clone $incomeQuery)->get()->groupBy('user_id');
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
        usort($monthlyClientBreakdown, fn ($a, $b) => $b['value'] <=> $a['value']);

        $monthlyCategoryData = (clone $incomeQuery)->get()->groupBy(fn ($item) => $item->category ?: $item->reason);
        $monthlyCategoryBreakdown = [];
        foreach ($monthlyCategoryData as $catKey => $txs) {
            $cReceived = $txs->where('type', 'received')->sum('business_amount');
            $cRefunded = $txs->where('type', 'refunded')->sum('business_amount');
            $cSent = $txs->where('type', 'sent')->sum('business_amount');
            $cNet = max(0, abs($cReceived) - abs($cRefunded) - abs($cSent));
            if ($cNet > 0) {
                $monthlyCategoryBreakdown[] = ['name' => ucfirst($catKey ?: 'Other'), 'value' => $cNet];
            }
        }
        usort($monthlyCategoryBreakdown, fn ($a, $b) => $b['value'] <=> $a['value']);

        $bCurrency = CurrencyHelper::getBusinessCurrency();
        $projectsList = Project::orderBy('project_name')->select('id', 'project_name', 'project_name as name')->limit(200)->get();
        $usersList = User::orderBy('name')->select('id', 'name')->get();
        $currenciesList = collect($currencies)->map(fn ($c) => ['id' => $c->id, 'code' => $c->currency, 'symbol' => $c->symbol])->values();
        $categoriesList = Transaction::whereIn('type', ['received', 'refunded', 'sent'])
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->limit(100)
            ->pluck('category')
            ->map(fn ($c) => ['value' => $c, 'label' => ucfirst($c)])
            ->values();

        return Inertia::render('Admin/Business/Income', [
            'entries' => $entries,
            'filters' => [
                'year' => $year,
                'month' => $month,
                'search' => $search,
                'preset' => $preset,
                'project_id' => $projectId,
                'user_id' => $userId,
                'currency_id' => $currencyId,
                'category' => $category,
                'min_amount' => $minAmount,
                'max_amount' => $maxAmount,
                'with_trashed' => $withTrashed,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'available_years' => $availableYears,
                'available_months' => $availableMonths,
            ],
            'options' => [
                'projects' => $projectsList,
                'users' => $usersList,
                'currencies' => $currenciesList,
                'categories' => $categoriesList,
            ],
            'stats' => [
                'total_received' => abs($received),
                'total_refunded' => abs($refunded),
                'total_sent' => abs($sent),
                'total_monthly_income' => $netIncome,
                'previous_month_income' => $prevNetIncome,
                'income_change_percent' => round($incomeChange, 1),
                'monthly_trends' => $monthlyTrends,
                'monthly_client_breakdown' => $monthlyClientBreakdown,
                'monthly_category_breakdown' => $monthlyCategoryBreakdown,
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
        $projectId = $request->query('project_id');
        $userId = $request->query('user_id');
        $currencyId = $request->query('currency_id');
        $category = $request->query('category');
        $minAmount = $request->query('min_amount');
        $maxAmount = $request->query('max_amount');
        $recurringOnly = filter_var($request->query('recurring_only', false), FILTER_VALIDATE_BOOLEAN);
        $preset = $request->query('preset');
        $withTrashed = filter_var($request->query('with_trashed', false), FILTER_VALIDATE_BOOLEAN);
        $sortBy = $request->query('sort_by', 'created_at');
        $sortDir = $request->query('sort_dir', 'desc');
        $allowedSorts = ['created_at', 'amount', 'business_amount', 'reason'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }
        $sortDir = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';

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

        $baseQuery = CostTransaction::excludingSalaries();
        if ($preset === 'all') {
            $costsQuery = (clone $baseQuery);
        } elseif ($preset === 'last_30') {
            $costsQuery = (clone $baseQuery)->where('created_at', '>=', now()->subDays(30));
        } elseif ($preset === 'last_90') {
            $costsQuery = (clone $baseQuery)->where('created_at', '>=', now()->subDays(90));
        } elseif ($preset === 'ytd') {
            $costsQuery = (clone $baseQuery)->whereYear('created_at', $currentYear);
        } else {
            $costsQuery = (clone $baseQuery)->inYearMonth($year, $month);
        }

        $costsQuery = $costsQuery->with(['user', 'project', 'recurringSources'])
            ->byCategory($category)
            ->forUser($userId)
            ->forProject($projectId)
            ->forCurrency($currencyId)
            ->amountBetween($minAmount, $maxAmount)
            ->recurringOnly($recurringOnly)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('reason', 'like', "%{$search}%")
                        ->orWhere('amount', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                        ->orWhereHas('project', fn ($p) => $p->where('project_name', 'like', "%{$search}%"));
                });
            });

        if ($withTrashed) {
            $costsQuery->withTrashed();
        }

        $costsQuery->orderBy($sortBy, $sortDir);

        $entries = $costsQuery->paginate(25)->withQueryString();

        $currencies = Currency::as_array();

        $entries->getCollection()->transform(function ($entry) use ($currencies) {
            $currId = $entry->currency_id ?? $entry->currency;
            $currRow = isset($currencies[$currId]) ? $currencies[$currId] : null;
            $currencyCode = $currRow ? $currRow->currency : 'EGP';
            $currencySymbol = $currRow ? $currRow->symbol : 'e£';

            $isRecurring = false;
            $categoryName = $entry->category ?: $entry->reason;
            $title = $entry->reason;
            $recurringCostId = null;
            $recurringCostTitle = null;

            $recurringSource = $entry->recurringSources->first();
            if ($recurringSource) {
                $isRecurring = true;
                $recurringCostId = $recurringSource->id;
                $recurringCostTitle = $recurringSource->title;
                $categoryName = $recurringSource->reason ?: $categoryName;
                $title = $recurringSource->title ?: $entry->reason;
            }

            return [
                'id' => $entry->id,
                'title' => ucfirst($title ?: 'Cost'),
                'reason' => $entry->reason,
                'amount' => $entry->amount,
                'business_amount' => $entry->business_amount,
                'currency' => $currencyCode,
                'currency_symbol' => $currencySymbol,
                'currency_id' => $currId,
                'category' => ['name' => ucfirst($categoryName ?: 'Cost')],
                'category_slug' => Str::slug((string) ($categoryName ?? '')),
                'category_raw' => $categoryName,
                'is_recurring' => $isRecurring,
                'recurring_cost_id' => $recurringCostId,
                'recurring_cost_title' => $recurringCostTitle,
                'next_due_date' => $entry->due_date,
                'status' => $entry->status ?? 'completed',
                'user' => $entry->user ? ['id' => $entry->user->id, 'name' => $entry->user->name, 'email' => $entry->user->email] : null,
                'project' => $entry->project ? ['id' => $entry->project->id, 'name' => $entry->project->project_name] : null,
                'created_at' => $entry->created_at,
                'updated_at' => $entry->updated_at,
                'deleted_at' => $entry->deleted_at,
                'type' => 'expense',
            ];
        });

        // Selected-period totals (filtered) for stat cards
        $filteredTotal = (clone $costsQuery)->sum('business_amount');
        $filteredCount = (clone $costsQuery)->count();
        $filteredAvg = $filteredCount > 0 ? abs($filteredTotal) / $filteredCount : 0;
        $filteredLargest = (clone $costsQuery)->orderBy('business_amount', 'desc')->first();

        // Comparison vs previous month
        $prevMonth = Carbon::createFromDate($year, $month)->subMonth();
        $prevTotal = CostTransaction::excludingSalaries()
            ->inYearMonth($prevMonth->year, $prevMonth->month)
            ->sum('business_amount') ?? 0;
        $changePercent = 0;
        if (abs($prevTotal) > 0) {
            $changePercent = ((abs($filteredTotal) - abs($prevTotal)) / abs($prevTotal)) * 100;
        } elseif (abs($filteredTotal) > 0) {
            $changePercent = 100;
        }

        // Year-to-date
        $ytdTotal = CostTransaction::excludingSalaries()
            ->whereYear('created_at', $year)
            ->sum('business_amount') ?? 0;

        // Monthly chart trends (last 12 months)
        $monthlyTrends = [];
        for ($i = 11; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $mCosts = CostTransaction::excludingSalaries()
                ->inYearMonth($m->year, $m->month)
                ->sum('business_amount') ?? 0;

            $monthlyTrends[] = [
                'name' => $m->format('M'),
                'costs' => abs($mCosts),
            ];
        }

        // Breakdowns
        $categoryBreakdown = $this->buildBreakdown($costsQuery, 'category');
        $projectBreakdown = $this->buildBreakdown($costsQuery, 'project', 'project_id');
        $clientBreakdown = $this->buildBreakdown($costsQuery, 'user', 'user_id');

        $bCurrency = CurrencyHelper::getBusinessCurrency();
        $projectsList = Project::orderBy('project_name')->select('id', 'project_name', 'project_name as name')->limit(200)->get();
        $usersList = User::orderBy('name')->select('id', 'name')->get();
        $currenciesList = collect($currencies)->map(fn ($c) => ['id' => $c->id, 'code' => $c->currency, 'symbol' => $c->symbol])->values();
        $categoriesList = CostTransaction::excludingSalaries()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->limit(100)
            ->pluck('category')
            ->map(fn ($c) => ['value' => $c, 'label' => ucfirst($c)])
            ->values();

        return Inertia::render('Admin/Business/Costs', [
            'entries' => $entries,
            'filters' => [
                'year' => $year,
                'month' => $month,
                'search' => $search,
                'preset' => $preset,
                'project_id' => $projectId,
                'user_id' => $userId,
                'currency_id' => $currencyId,
                'category' => $category,
                'min_amount' => $minAmount,
                'max_amount' => $maxAmount,
                'recurring_only' => $recurringOnly,
                'with_trashed' => $withTrashed,
                'sort_by' => $sortBy,
                'sort_dir' => $sortDir,
                'available_years' => $availableYears,
                'available_months' => $availableMonths,
            ],
            'options' => [
                'projects' => $projectsList,
                'users' => $usersList,
                'currencies' => $currenciesList,
                'categories' => $categoriesList,
                'payment_methods' => self::COST_PAYMENT_METHODS,
            ],
            'stats' => [
                'total_monthly_costs' => abs($filteredTotal),
                'previous_month_costs' => abs($prevTotal),
                'change_percent' => round($changePercent, 1),
                'ytd_costs' => abs($ytdTotal),
                'average_cost' => $filteredAvg,
                'entry_count' => $filteredCount,
                'largest_cost' => $filteredLargest ? [
                    'id' => $filteredLargest->id,
                    'reason' => $filteredLargest->reason,
                    'business_amount' => abs((float) $filteredLargest->business_amount),
                ] : null,
                'monthly_trends' => $monthlyTrends,
                'category_breakdown' => $categoryBreakdown,
                'project_breakdown' => $projectBreakdown,
                'client_breakdown' => $clientBreakdown,
                'business_currency_code' => $bCurrency['currency'] ?? 'USD',
                'business_currency_symbol' => $bCurrency['symbol'] ?? '$',
            ],
        ]);
    }

    private const COST_PAYMENT_METHODS = [
        ['value' => 'cash', 'label' => 'Cash'],
        ['value' => 'bank_transfer', 'label' => 'Bank Transfer'],
        ['value' => 'credit_card', 'label' => 'Credit Card'],
        ['value' => 'wallet', 'label' => 'Wallet'],
        ['value' => 'cheque', 'label' => 'Cheque'],
        ['value' => 'other', 'label' => 'Other'],
    ];

    private function buildBreakdown($query, string $column, ?string $foreign = null): array
    {
        $clone = clone $query;
        $rows = $clone->selectRaw(
            $foreign
                ? "$foreign as group_key"
                : "COALESCE(NULLIF($column, ''), 'uncategorized') as group_key",
            []
        )->selectRaw('SUM(ABS(business_amount)) as total', [])
            ->selectRaw('COUNT(*) as cnt', [])
            ->groupBy('group_key')
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        return $rows->map(function ($row) use ($column, $foreign) {
            $label = $row->group_key;
            if ($foreign === 'project_id') {
                $label = optional(Project::find($row->group_key))->project_name ?? 'Unknown';
            } elseif ($foreign === 'user_id') {
                $label = optional(User::find($row->group_key))->name ?? 'Unknown';
            } elseif ($column === 'category') {
                $label = $label === 'uncategorized' ? 'Uncategorized' : ucfirst((string) $label);
            }

            return [
                'name' => (string) $label,
                'value' => (float) $row->total,
                'count' => (int) $row->cnt,
            ];
        })->values()->all();
    }

    public function show_cost($id)
    {
        $cost = CostTransaction::with(['user', 'project', 'recurringSources'])
            ->withTrashed()
            ->findOrFail($id);

        $currencies = Currency::as_array();
        $currId = $cost->currency_id ?? $cost->currency;
        $currRow = isset($currencies[$currId]) ? $currencies[$currId] : null;

        $bCurrency = CurrencyHelper::getBusinessCurrency();

        $related = CostTransaction::with(['user', 'project'])
            ->excludingSalaries()
            ->where('id', '!=', $cost->id)
            ->where(function ($q) use ($cost) {
                if ($cost->user_id) {
                    $q->where('user_id', $cost->user_id);
                }
                if ($cost->project_id) {
                    $q->orWhere('project_id', $cost->project_id);
                }
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($r) use ($currencies) {
                $cid = $r->currency_id ?? $r->currency;
                $crow = $currencies[$cid] ?? null;

                return [
                    'id' => $r->id,
                    'reason' => $r->reason,
                    'amount' => $r->amount,
                    'business_amount' => $r->business_amount,
                    'currency_code' => optional($crow)->currency ?? 'USD',
                    'created_at' => $r->created_at,
                    'project' => $r->project?->only(['id', 'name']),
                ];
            })
            ->values();

        return Inertia::render('Admin/Business/CostsShow', [
            'cost' => [
                'id' => $cost->id,
                'reason' => $cost->reason,
                'title' => $cost->reason,
                'amount' => $cost->amount,
                'business_amount' => $cost->business_amount,
                'currency_id' => $currId,
                'currency_code' => optional($currRow)->currency ?? 'USD',
                'currency_symbol' => optional($currRow)->symbol ?? '',
                'category' => $cost->category,
                'category_label' => $cost->category ? ucfirst($cost->category) : null,
                'created_at' => $cost->created_at,
                'updated_at' => $cost->updated_at,
                'deleted_at' => $cost->deleted_at,
                'user' => $cost->user ? ['id' => $cost->user->id, 'name' => $cost->user->name, 'email' => $cost->user->email] : null,
                'project' => $cost->project ? ['id' => $cost->project->id, 'name' => $cost->project->project_name] : null,
                'recurring_sources' => $cost->recurringSources->map(fn ($r) => [
                    'id' => $r->id,
                    'title' => $r->title,
                ])->values(),
            ],
            'related' => $related,
            'business_currency_code' => $bCurrency['currency'] ?? 'USD',
            'business_currency_symbol' => $bCurrency['symbol'] ?? '$',
        ]);
    }

    public function restore_cost($id)
    {
        $cost = CostTransaction::withTrashed()->findOrFail($id);

        if (! $cost->trashed()) {
            return redirect()->route('admin.costs.index')->with('info', __('general.no_changes_to_restore'));
        }

        DB::transaction(function () use ($cost) {
            $cost->restore();
            if ($cost->user_id) {
                $user = User::find($cost->user_id);
                if ($user) {
                    BalancesHelper::instance()->CalcCostBalance($user);
                }
            }
        });

        $this->costAudit->log(
            CostTransactionAuditService::ACTION_UPDATED,
            $cost->id,
            ['restored' => true]
        );

        return redirect()->route('admin.costs.show', $cost->id)->with('success', __('general.restored_successfully'));
    }

    public function duplicate_cost($id)
    {
        $source = CostTransaction::withTrashed()->findOrFail($id);

        $clone = $source->replicate();
        $clone->created_at = now();
        $clone->updated_at = now();
        $clone->deleted_at = null;
        $clone->created_by = auth()->id();

        DB::transaction(function () use ($clone, $source) {
            $clone->save();

            if ($clone->user_id) {
                $user = User::find($clone->user_id);
                if ($user) {
                    BalancesHelper::instance()->CalcCostBalance($user);
                }
            }

            foreach ($source->recurringSources as $rc) {
                $clone->recurringSources()->attach($rc->id, []);
            }
        });

        $this->costAudit->log(
            CostTransactionAuditService::ACTION_CREATED,
            $clone->id,
            ['duplicated_from' => $source->id]
        );

        return redirect()->route('admin.costs.edit', $clone->id)->with('success', __('general.duplicated_successfully'));
    }

    public function bulk_delete_costs(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:cost_transactions,id',
        ]);

        $userIds = [];
        $deleted = 0;

        DB::transaction(function () use ($data, &$userIds, &$deleted) {
            $costs = CostTransaction::whereIn('id', $data['ids'])->get();
            foreach ($costs as $cost) {
                if ($cost->user_id) {
                    $userIds[$cost->user_id] = true;
                }
                $cost->delete();
                $this->costAudit->log(
                    CostTransactionAuditService::ACTION_DELETED,
                    $cost->id,
                    ['bulk' => true, 'amount' => $cost->amount, 'reason' => $cost->reason]
                );
                $deleted++;
            }
        });

        foreach (array_keys($userIds) as $uid) {
            $user = User::find($uid);
            if ($user) {
                BalancesHelper::instance()->CalcCostBalance($user);
            }
        }

        return redirect()->back()->with('success', trans_choice('general.bulk_deleted', $deleted, ['count' => $deleted]));
    }

    public function export_costs(Request $request)
    {
        $query = CostTransaction::excludingSalaries()->with(['user', 'project']);

        foreach (['year', 'month', 'project_id', 'user_id', 'currency_id', 'category'] as $f) {
            if ($request->filled($f)) {
                $v = $request->query($f);
                if ($f === 'project_id') {
                    $query->where('project_id', $v);
                } elseif ($f === 'user_id') {
                    $query->where('user_id', $v);
                } elseif ($f === 'currency_id') {
                    $query->where('currency_id', $v);
                } elseif ($f === 'category') {
                    $query->where('category', $v);
                } elseif ($f === 'year') {
                    $query->whereYear('created_at', (int) $v);
                } elseif ($f === 'month') {
                    $query->whereMonth('created_at', (int) $v);
                }
            }
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->query('search'));
            $query->where(function ($q) use ($term) {
                $q->where('reason', 'like', "%{$term}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$term}%"))
                    ->orWhereHas('project', fn ($p) => $p->where('project_name', 'like', "%{$term}%"));
            });
        }

        $rows = $query->orderBy('created_at', 'desc')->limit(10000)->get();
        $currencies = Currency::as_array();
        $bCurrency = CurrencyHelper::getBusinessCurrency();

        $filename = 'costs-'.now()->format('Ymd-His').'.csv';

        $response = new StreamedResponse(function () use ($rows, $currencies) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'id', 'date', 'reason', 'category', 'project', 'client', 'currency',
                'amount', 'business_amount', 'recurring',
            ]);
            foreach ($rows as $r) {
                $cid = $r->currency_id ?? $r->currency;
                $crow = $currencies[$cid] ?? null;
                fputcsv($out, [
                    $r->id,
                    optional($r->created_at)->toDateTimeString(),
                    $r->reason,
                    $r->category,
                    optional($r->project)->project_name,
                    optional($r->user)->name,
                    optional($crow)->currency,
                    (float) $r->amount,
                    (float) $r->business_amount,
                    $r->recurringSources->count() > 0 ? 'yes' : 'no',
                ]);
            }
            fclose($out);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$filename}\"");

        return $response;
    }

    public function create_cost()
    {
        $users = User::orderBy('name')->select('id', 'name')->get();
        $projects = Project::whereNotIn('status', ['Completed', 'Cancelled'])->orderBy('project_name')->select('id', 'project_name', 'project_name as name', 'user_id')->limit(200)->get();
        $currencies = array_values(Currency::as_array());
        $businessCurrency = CurrencyHelper::getBusinessCurrency();
        $existingCategories = CostTransaction::excludingSalaries()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->limit(100)
            ->pluck('category')
            ->map(fn ($c) => ['value' => $c, 'label' => ucfirst($c)])
            ->values();

        return Inertia::render('Admin/Business/CostsCreate', [
            'users' => $users,
            'projects' => $projects,
            'currencies' => $currencies,
            'businessCurrency' => $businessCurrency,
            'paymentMethods' => self::COST_PAYMENT_METHODS,
            'categories' => $existingCategories,
        ]);
    }

    public function store_cost(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'reason' => 'required|string|max:500',
            'created_at' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'category' => 'nullable|string|max:80',
            'category_text' => 'nullable|string|max:80',
            'make_recurring' => 'nullable|boolean',
            'recurring' => 'nullable|string|in:day,week,month,year',
            'recurring_times' => 'nullable|integer|min:1',
            'recurring_title' => 'nullable|string|max:255',
        ]);

        $category = $request->input('category') === '__new__'
            ? ($request->input('category_text') ?: null)
            : ($request->input('category') ?: null);

        $cost = new CostTransaction;
        $cost->amount = $request->amount;
        $cost->currency_id = $request->currency_id;
        $cost->reason = $request->reason;
        $cost->category = $category;

        if ($request->filled('created_at')) {
            $cost->created_at = $request->created_at;
        }

        if ($request->filled('user_id')) {
            $cost->user_id = $request->user_id;
        }

        if ($request->filled('project_id')) {
            $cost->project_id = $request->project_id;
        }

        $recurringId = null;

        DB::transaction(function () use ($cost, $request, &$recurringId) {
            $cost->save();
            if ($cost->user_id) {
                $user = User::find($cost->user_id);
                if ($user) {
                    BalancesHelper::instance()->CalcCostBalance($user);
                }
            }

            if ($request->boolean('make_recurring') && $request->filled('recurring')) {
                $rc = new RecurringCost;
                $rc->title = $request->input('recurring_title') ?: $cost->reason;
                $rc->amount = $cost->amount;
                $rc->currency_id = $cost->currency_id;
                $rc->start_date = ($cost->created_at ?? now())->toDateString();
                $rc->current_date = ($cost->created_at ?? now())->toDateString();
                $rc->recurring = $request->input('recurring');
                $rc->recurring_times = (int) ($request->input('recurring_times') ?? 1);
                if ($rc->recurring === 'month') {
                    $rc->recurring_times_month = (int) ($cost->created_at?->day ?? now()->day);
                }
                $rc->reason = $cost->category ?: $cost->reason;
                $rc->is_active = true;
                $rc->save();
                $rc->transactions()->attach($cost->id, ['unique_id' => $rc->id.'-'.($cost->created_at ?? now())->toDateString()]);
                $recurringId = $rc->id;
            }
        });

        $this->costAudit->log(
            CostTransactionAuditService::ACTION_CREATED,
            $cost->id,
            [
                'amount' => $cost->amount,
                'currency_id' => $cost->currency_id,
                'reason' => $cost->reason,
                'category' => $cost->category,
                'user_id' => $cost->user_id,
                'project_id' => $cost->project_id,
                'recurring_id' => $recurringId,
            ]
        );

        $redirect = $request->boolean('make_recurring')
            ? redirect()->route('admin.recurring_costs.edit', $recurringId)
            : redirect()->route('admin.costs.index');

        return $redirect->with('success', __('general.saved_successfully'));
    }

    public function edit_cost($id)
    {
        $cost = CostTransaction::withTrashed()->findOrFail($id);

        $users = User::orderBy('name')->select('id', 'name')->get();
        $projects = Project::whereNotIn('status', ['Completed', 'Cancelled'])->orderBy('project_name')->select('id', 'project_name', 'project_name as name', 'user_id')->limit(200)->get();
        $currencies = array_values(Currency::as_array());
        $businessCurrency = CurrencyHelper::getBusinessCurrency();
        $existingCategories = CostTransaction::excludingSalaries()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->limit(100)
            ->pluck('category')
            ->map(fn ($c) => ['value' => $c, 'label' => ucfirst($c)])
            ->values();

        return Inertia::render('Admin/Business/CostsEdit', [
            'cost' => $cost,
            'users' => $users,
            'projects' => $projects,
            'currencies' => $currencies,
            'businessCurrency' => $businessCurrency,
            'paymentMethods' => self::COST_PAYMENT_METHODS,
            'categories' => $existingCategories,
            'attachment_url' => $cost->attachment_path ? Storage::disk('public')->url($cost->attachment_path) : null,
        ]);
    }

    public function update_cost(Request $request, $id)
    {
        $cost = CostTransaction::withTrashed()->findOrFail($id);
        $before = $cost->only([
            'amount', 'currency_id', 'reason', 'user_id', 'project_id',
            'category', 'created_at',
        ]);

        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'currency_id' => 'required|exists:currencies,id',
            'reason' => 'required|string|max:500',
            'created_at' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'category' => 'nullable|string|max:80',
            'category_text' => 'nullable|string|max:80',
        ]);

        $category = $request->input('category') === '__new__'
            ? ($request->input('category_text') ?: null)
            : ($request->input('category') ?: null);

        $previousUserId = $cost->user_id;

        DB::transaction(function () use ($cost, $request, $category, $previousUserId) {
            $cost->amount = $request->amount;
            $cost->currency_id = $request->currency_id;
            $cost->reason = $request->reason;
            $cost->category = $category;

            if ($request->filled('created_at')) {
                $cost->created_at = $request->created_at;
            }

            $cost->user_id = $request->filled('user_id') ? $request->user_id : null;
            $cost->project_id = $request->filled('project_id') ? $request->project_id : null;

            $cost->save();

            $usersToRecalc = array_unique(array_filter([$cost->user_id, $previousUserId]));
            foreach ($usersToRecalc as $uid) {
                $user = User::find($uid);
                if ($user) {
                    BalancesHelper::instance()->CalcCostBalance($user);
                }
            }
        });

        $after = $cost->fresh()->only(array_keys($before));
        $changed = array_keys(array_diff_assoc($after, $before));
        $changed = array_values(array_unique($changed));

        if ($changed !== []) {
            $this->costAudit->log(
                CostTransactionAuditService::ACTION_UPDATED,
                $cost->id,
                [
                    'changed' => $changed,
                    'before' => array_intersect_key($before, array_flip($changed)),
                    'after' => array_intersect_key($after, array_flip($changed)),
                    'previous_user_id' => $previousUserId !== $after['user_id'] ? $previousUserId : null,
                ]
            );
        }

        return redirect()->route('admin.costs.show', $cost->id)->with('success', __('general.saved_successfully'));
    }

    public function delete_cost($id)
    {
        $cost = CostTransaction::withTrashed()->findOrFail($id);
        $wasTrashed = $cost->trashed();
        $snapshot = $cost->only([
            'amount', 'currency_id', 'reason', 'user_id', 'project_id',
            'category',
        ]);
        $costId = $cost->id;
        $userId = $cost->user_id;
        $force = request()->boolean('force');

        DB::transaction(function () use ($cost, $force, $userId) {
            if ($force) {
                $cost->forceDelete();
            } else {
                $cost->delete();
            }

            if ($userId) {
                $user = User::find($userId);
                if ($user) {
                    BalancesHelper::instance()->CalcCostBalance($user);
                }
            }
        });

        $this->costAudit->log(
            $wasTrashed ? 'costs.force_deleted' : CostTransactionAuditService::ACTION_DELETED,
            $costId,
            array_merge($snapshot, ['force' => $force && $wasTrashed])
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
        $clientId  = $request->query('client_id');
        $from      = $request->query('from');
        $to        = $request->query('to');
        $projectId = $request->query('project_id');
        $category  = $request->query('category');
        $startUtc  = null;
        $endUtc    = null;

        // Apply Cairo timezone boundaries flexibly
        if (!empty($from)) {
            $startUtc = Carbon::parse($from, 'Africa/Cairo')->startOfDay()->setTimezone('UTC');
        }
        if (!empty($to)) {
            $endUtc = Carbon::parse($to, 'Africa/Cairo')->endOfDay()->setTimezone('UTC');
        }

        // Client project IDs for relational scoping
        $clientProjectIds = !empty($clientId)
            ? Project::where('user_id', $clientId)->pluck('id')->toArray()
            : [];

        // Dropdown options
        $clients = User::select('id', 'name', 'email')->orderBy('name')->get();
        $projects = Project::select('id', 'project_name as name', 'user_id')->get();
        $txnCategories = Transaction::whereNotNull('category')->distinct()->pluck('category')->toArray();
        $costCategories = CostTransaction::whereNotNull('category')->distinct()->pluck('category')->toArray();
        $availableCategories = array_values(array_unique(array_filter(array_merge($txnCategories, $costCategories))));

        // General Stats Queries
        $usersQuery = User::query();
        $projectsQuery = Project::query();
        $invoicesQuery = Invoice::query();
        $txnsQuery = Transaction::query();

        if ($startUtc) {
            $usersQuery->where('created_at', '>=', $startUtc);
            $projectsQuery->where('created_at', '>=', $startUtc);
            $invoicesQuery->where('created_at', '>=', $startUtc);
            $txnsQuery->where('created_at', '>=', $startUtc);
        }
        if ($endUtc) {
            $usersQuery->where('created_at', '<=', $endUtc);
            $projectsQuery->where('created_at', '<=', $endUtc);
            $invoicesQuery->where('created_at', '<=', $endUtc);
            $txnsQuery->where('created_at', '<=', $endUtc);
        }

        if ($clientId) {
            $usersQuery->where('id', $clientId);
            $projectsQuery->where('user_id', $clientId);
            $invoicesQuery->where('user_id', $clientId);
            $txnsQuery->where('user_id', $clientId);
        }

        if ($projectId) {
            $invoicesQuery->where('project_id', $projectId);
            $txnsQuery->where('project_id', $projectId);
        }

        if ($category) {
            $txnsQuery->where('category', $category);
        }

        // ── Financial Calculations Queries (Combined into 1 Single Query) ──────
        $financialSummary = Transaction::query()
            ->whereIn('type', ['received', 'refunded', 'sent'])
            ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
            ->when($clientId, fn($q) => $q->where('user_id', $clientId))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->selectRaw("
                SUM(CASE WHEN type = 'received' THEN business_amount ELSE 0 END) as total_received,
                SUM(CASE WHEN type = 'refunded' THEN business_amount ELSE 0 END) as total_refunded,
                SUM(CASE WHEN type = 'sent' THEN business_amount ELSE 0 END) as total_sent
            ")
            ->first();

        $expensesQuery = CostTransaction::query()
            ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
            ->when($clientId, function ($q) use ($clientId, $clientProjectIds) {
                $q->where(function ($sq) use ($clientId, $clientProjectIds) {
                    $sq->where('user_id', $clientId);
                    if (!empty($clientProjectIds)) {
                        $sq->orWhereIn('project_id', $clientProjectIds);
                    }
                });
            })
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($category, fn($q) => $q->where('category', $category));

        $lifetimeReceived = $financialSummary->total_received ?? 0;
        $lifetimeRefunded = $financialSummary->total_refunded ?? 0;
        $lifetimeSent     = $financialSummary->total_sent ?? 0;
        $lifetimeIncome   = max(0, abs($lifetimeReceived) - abs($lifetimeRefunded) - abs($lifetimeSent));

        $lifetimeExpenses = $expensesQuery->sum('business_amount') ?? 0;
        $netProfit        = $lifetimeIncome - abs($lifetimeExpenses);

        // ── Category Breakdowns ────────────────────────────────────────────────
        $incomeByCategory = Transaction::where('type', 'received')
            ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
            ->when($clientId, fn($q) => $q->where('user_id', $clientId))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->select('category', DB::raw('SUM(business_amount) as value'))
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->category ?: __('general.uncategorized') ?: 'Uncategorized',
                    'value' => round(abs($item->value), 2),
                ];
            })
            ->filter(fn($x) => $x['value'] > 0)
            ->values()
            ->toArray();

        $expensesByCategory = CostTransaction::query()
            ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
            ->when($clientId, function ($q) use ($clientId, $clientProjectIds) {
                $q->where(function ($sq) use ($clientId, $clientProjectIds) {
                    $sq->where('user_id', $clientId);
                    if (!empty($clientProjectIds)) {
                        $sq->orWhereIn('project_id', $clientProjectIds);
                    }
                });
            })
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->select('category', DB::raw('SUM(business_amount) as value'))
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->category ?: __('general.uncategorized') ?: 'Uncategorized',
                    'value' => round(abs($item->value), 2),
                ];
            })
            ->filter(fn($x) => $x['value'] > 0)
            ->values()
            ->toArray();

        // ── Monthly Trends (Optimized into 2 Grouped Aggregations) ────────────
        $startTrend = $from ? Carbon::parse($from, 'Africa/Cairo')->startOfMonth() : now('Africa/Cairo')->subMonths(11)->startOfMonth();
        $endTrend   = $to ? Carbon::parse($to, 'Africa/Cairo')->endOfMonth() : now('Africa/Cairo')->endOfMonth();

        $trendStartUtc = $startTrend->copy()->startOfMonth()->setTimezone('UTC');
        $trendEndUtc   = $endTrend->copy()->endOfMonth()->setTimezone('UTC');

        $isSqlite     = DB::connection()->getDriverName() === 'sqlite';
        $dateExpr     = $isSqlite ? "strftime('%Y-%m', created_at)" : "DATE_FORMAT(created_at, '%Y-%m')";
        $timerSecExpr = $isSqlite ? 'SUM(CAST((julianday(date_end) - julianday(date_start)) * 86400 AS INTEGER)) as total_sec' : 'SUM(TIMESTAMPDIFF(SECOND, date_start, date_end)) as total_sec';

        // Single query for all transaction monthly aggregates
        $monthlyTxnsGrouped = Transaction::query()
            ->whereIn('type', ['received', 'refunded', 'sent'])
            ->whereBetween('created_at', [$trendStartUtc, $trendEndUtc])
            ->when($clientId, fn($q) => $q->where('user_id', $clientId))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->selectRaw("{$dateExpr} as ym, type, SUM(business_amount) as total_amount")
            ->groupBy('ym', 'type')
            ->get()
            ->groupBy('ym');

        // Single query for all cost monthly aggregates
        $monthlyCostsGrouped = CostTransaction::query()
            ->whereBetween('created_at', [$trendStartUtc, $trendEndUtc])
            ->when($clientId, function ($q) use ($clientId, $clientProjectIds) {
                $q->where(function ($sq) use ($clientId, $clientProjectIds) {
                    $sq->where('user_id', $clientId);
                    if (!empty($clientProjectIds)) {
                        $sq->orWhereIn('project_id', $clientProjectIds);
                    }
                });
            })
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when($category, fn($q) => $q->where('category', $category))
            ->selectRaw("{$dateExpr} as ym, SUM(business_amount) as total_amount")
            ->groupBy('ym')
            ->pluck('total_amount', 'ym');

        $monthlyTrends = [];
        $temp          = $startTrend->copy();
        $loopCount     = 0;

        while ($temp->lte($endTrend) && $loopCount < 36) {
            $ymKey = $temp->format('Y-m');

            $txnsForMonth = $monthlyTxnsGrouped->get($ymKey, collect());
            $incSum = $txnsForMonth->where('type', 'received')->sum('total_amount') ?? 0;
            $refSum = $txnsForMonth->where('type', 'refunded')->sum('total_amount') ?? 0;
            $sntSum = $txnsForMonth->where('type', 'sent')->sum('total_amount') ?? 0;

            $mIncome   = max(0, abs($incSum) - abs($refSum) - abs($sntSum));
            $mExpenses = abs($monthlyCostsGrouped->get($ymKey, 0));

            $mProfit = $mIncome - $mExpenses;
            $mMargin = $mIncome > 0 ? round($mProfit / $mIncome * 100, 1) : 0;

            $monthlyTrends[] = [
                'name'          => $temp->format('M Y'),
                'income'        => round($mIncome, 2),
                'costs'         => round($mExpenses, 2),
                'profit'        => round($mProfit, 2),
                'profit_margin' => $mMargin,
            ];

            $temp->addMonth();
            $loopCount++;
        }

        $bCurrency = CurrencyHelper::getBusinessCurrency();

        // Compute monthly averages from the trends
        $trendCount = count($monthlyTrends);
        $avgIncome  = $trendCount > 0 ? round(array_sum(array_column($monthlyTrends, 'income')) / $trendCount, 2) : 0;
        $avgCosts   = $trendCount > 0 ? round(array_sum(array_column($monthlyTrends, 'costs'))  / $trendCount, 2) : 0;
        $avgProfit  = $trendCount > 0 ? round(array_sum(array_column($monthlyTrends, 'profit')) / $trendCount, 2) : 0;
        $avgMargin  = $trendCount > 0 ? round(array_sum(array_column($monthlyTrends, 'profit_margin')) / $trendCount, 1) : 0;

        // ── 1. Worked Hours & EHR (Optimized DB Aggregate Sum) ───────────────
        $totalWorkedSeconds = (int) InvoiceItemTimer::query()
            ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when(!$projectId && $clientId, fn($q) => $q->whereIn('project_id', $clientProjectIds))
            ->whereNotNull('date_end')
            ->whereNotNull('date_start')
            ->selectRaw($timerSecExpr)
            ->value('total_sec');

        $totalWorkedHours    = round($totalWorkedSeconds / 3600, 2);
        $marketHourlyRate    = (float) AdminSettings::GetValue('market_hourly_rate', 0);
        $effectiveHourlyRate = $totalWorkedHours > 0 ? round($lifetimeIncome / $totalWorkedHours, 2) : 0;
        $costPerWorkedHour   = $totalWorkedHours > 0 ? round(abs($lifetimeExpenses) / $totalWorkedHours, 2) : 0;
        $rateVariance        = round($effectiveHourlyRate - $marketHourlyRate, 2);

        // ── 2. Invoices & DSO & AR Aging (Optimized & Fixed Invoice Properties) ──
        $invoicesList = Invoice::query()
            ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
            ->when($clientId, fn($q) => $q->where('user_id', $clientId))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->select(['id', 'user_id', 'status', 'final_total', 'paid', 'unpaid', 'created_at', 'paid_at'])
            ->get();

        $totalInvoicedAmount = 0;
        $totalPaidAmount     = 0;
        $totalUnpaidAmount   = 0;

        $arAging = [
            '0_30'    => 0,
            '31_60'   => 0,
            '61_90'   => 0,
            '90_plus' => 0,
        ];

        $now = now('Africa/Cairo');

        foreach ($invoicesList as $inv) {
            $invTotal = abs(method_exists($inv, 'total') ? $inv->total() : ($inv->amount ?? 0));
            $totalInvoicedAmount += $invTotal;

            $paid = abs($inv->paid ?? 0);
            $totalPaidAmount += $paid;

            $unpaid = max(0, abs($inv->unpaid ?? ($invTotal - $paid)));
            if ($inv->status !== 'paid' && $inv->status !== 'cancelled' && $unpaid > 0) {
                $totalUnpaidAmount += $unpaid;

                $dueDate = $inv->due_date ? Carbon::parse($inv->due_date) : Carbon::parse($inv->created_at);
                $daysOverdue = max(0, (int) $dueDate->diffInDays($now, false));

                if ($daysOverdue <= 30) {
                    $arAging['0_30'] += round($unpaid, 2);
                } elseif ($daysOverdue <= 60) {
                    $arAging['31_60'] += round($unpaid, 2);
                } elseif ($daysOverdue <= 90) {
                    $arAging['61_90'] += round($unpaid, 2);
                } else {
                    $arAging['90_plus'] += round($unpaid, 2);
                }
            }
        }

        $collectionRatePercent = $totalInvoicedAmount > 0 ? round(($totalPaidAmount / $totalInvoicedAmount) * 100, 1) : 0;

        $daysInPeriod = 30;
        if ($startUtc && $endUtc) {
            $daysInPeriod = max(1, (int) Carbon::parse($from)->diffInDays(Carbon::parse($to)) + 1);
        } elseif ($startUtc) {
            $daysInPeriod = max(1, (int) Carbon::parse($from)->diffInDays(now('Africa/Cairo')) + 1);
        } elseif ($endUtc) {
            $daysInPeriod = max(1, (int) Carbon::parse($to)->diffInDays(now('Africa/Cairo')) + 1);
        }

        $paidInvoices = $invoicesList->filter(fn($inv) => $inv->status === 'paid' && !empty($inv->paid_at));
        $totalPaidDays = 0;
        $paidCount = $paidInvoices->count();
        foreach ($paidInvoices as $inv) {
            $createdAt = Carbon::parse($inv->created_at);
            $paidAt = Carbon::parse($inv->paid_at);
            $seconds = max(0, $createdAt->diffInSeconds($paidAt));
            $totalPaidDays += $seconds / 86400.0;
        }
        $dsoDays = $paidCount > 0 ? round($totalPaidDays / $paidCount, 1) : 0;

        // ── 3. Academic Margins & Break-Even ──────────────────────────────────
        $operatingMarginPercent  = $lifetimeIncome > 0 ? round(($netProfit / $lifetimeIncome) * 100, 1) : 0;
        $monthlyBreakEvenRevenue = $avgCosts;

        // ── 4. Current Year Months (Cairo Timezone) ───────────────────────────
        $nowCairo = now('Africa/Cairo');
        $currentYear = (int) $nowCairo->year;
        $currentMonth = (int) $nowCairo->month;

        $currentYearMonths = [];
        for ($m = 1; $m <= 12; $m++) {
            $dt = Carbon::createFromDate($currentYear, $m, 1, 'Africa/Cairo');
            $currentYearMonths[] = [
                'month'      => $m,
                'year'       => $currentYear,
                'name'       => $dt->format('M'),
                'full_name'  => $dt->format('F'),
                'from'       => $dt->copy()->startOfMonth()->toDateString(),
                'to'         => $dt->copy()->endOfMonth()->toDateString(),
                'is_active'  => $m <= $currentMonth,
            ];
        }

        // ── 5. Clients Worked With in Period ──────────────────────────────────
        $invoiceClientIds = Invoice::query()
            ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
            ->when($clientId, fn($q) => $q->where('user_id', $clientId))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        $projectClientIds = Project::query()
            ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
            ->when($clientId, fn($q) => $q->where('user_id', $clientId))
            ->when($projectId, fn($q) => $q->where('id', $projectId))
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        $timerProjectIds = InvoiceItemTimer::query()
            ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
            ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->when(!$projectId && $clientId, fn($q) => $q->whereIn('project_id', $clientProjectIds))
            ->whereNotNull('project_id')
            ->distinct()
            ->pluck('project_id')
            ->toArray();

        $timerClientIds = Project::whereIn('id', $timerProjectIds)
            ->whereNotNull('user_id')
            ->distinct()
            ->pluck('user_id')
            ->toArray();

        $allClientIds = array_values(array_unique(array_filter(array_merge($invoiceClientIds, $projectClientIds, $timerClientIds))));

        if ($clientId) {
            $allClientIds = array_values(array_intersect($allClientIds, [(int) $clientId]));
            if (empty($allClientIds) && User::where('id', $clientId)->exists()) {
                $allClientIds = [(int) $clientId];
            }
        }

        $clientsWorkedWith = [];

        if (!empty($allClientIds)) {
            $clientUsers = User::whereIn('id', $allClientIds)->get();

            foreach ($clientUsers as $client) {
                $cProjectIds = Project::where('user_id', $client->id)
                    ->when($projectId, fn($q) => $q->where('id', $projectId))
                    ->pluck('id')
                    ->toArray();

                $workedSeconds = 0;
                if (!empty($cProjectIds)) {
                    $workedSeconds = (int) InvoiceItemTimer::query()
                        ->whereIn('project_id', $cProjectIds)
                        ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
                        ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
                        ->whereNotNull('date_end')
                        ->whereNotNull('date_start')
                        ->selectRaw($timerSecExpr)
                        ->value('total_sec');
                }

                $clientInvoices = Invoice::query()
                    ->where('user_id', $client->id)
                    ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
                    ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
                    ->when($projectId, fn($q) => $q->where('project_id', $projectId))
                    ->get();

                $invoicedAmount = 0;
                $paidAmount = 0;
                $lastActivity = null;
                $businessCurrencyId = (int) (AdminSettings::GetValue('business_currency') ?: 2);

                foreach ($clientInvoices as $inv) {
                    $rawTotal  = abs(method_exists($inv, 'total') ? $inv->total() : ($inv->amount ?? 0));
                    $rawPaid   = abs($inv->paid ?? 0);
                    $invCurrId = $inv->currency_id ?: $businessCurrencyId;

                    $busTotal = (float) CurrenciesExchange::RateToday($rawTotal, $invCurrId, $businessCurrencyId);
                    $busPaid  = (float) CurrenciesExchange::RateToday($rawPaid, $invCurrId, $businessCurrencyId);

                    $invoicedAmount += $busTotal;
                    $paidAmount     += $busPaid;

                    if (!$lastActivity || (isset($inv->created_at) && $inv->created_at > $lastActivity)) {
                        $lastActivity = $inv->created_at;
                    }
                }

                $activeProjectsCount = Project::where('user_id', $client->id)
                    ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
                    ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
                    ->when($projectId, fn($q) => $q->where('id', $projectId))
                    ->count();

                if (!empty($cProjectIds)) {
                    $latestTimer = InvoiceItemTimer::whereIn('project_id', $cProjectIds)
                        ->when($startUtc, fn($q) => $q->where('created_at', '>=', $startUtc))
                        ->when($endUtc, fn($q) => $q->where('created_at', '<=', $endUtc))
                        ->latest('created_at')
                        ->first();
                    if ($latestTimer && (!$lastActivity || $latestTimer->created_at > $lastActivity)) {
                        $lastActivity = $latestTimer->created_at;
                    }
                }

                $clientsWorkedWith[] = [
                    'id'                    => $client->id,
                    'name'                  => $client->name,
                    'email'                 => $client->email,
                    'avatar'                => method_exists($client, 'getProfilePhotoUrlAttribute') ? $client->profile_photo_url : null,
                    'worked_hours'          => round($workedSeconds / 3600, 2),
                    'worked_seconds'        => $workedSeconds,
                    'total_invoiced'        => round($invoicedAmount, 2),
                    'total_paid'            => round($paidAmount, 2),
                    'active_projects_count' => $activeProjectsCount,
                    'last_activity'         => $lastActivity ? Carbon::parse($lastActivity)->timezone('Africa/Cairo')->format('Y-m-d H:i') : null,
                ];
            }

            usort($clientsWorkedWith, function ($a, $b) {
                if ($b['worked_seconds'] !== $a['worked_seconds']) {
                    return $b['worked_seconds'] <=> $a['worked_seconds'];
                }
                return $b['total_invoiced'] <=> $a['total_invoiced'];
            });
        }

        return Inertia::render('Admin/Business/Reports', [
            'stats' => [
                'total_users'               => $usersQuery->count(),
                'total_projects'            => $projectsQuery->count(),
                'total_invoices'            => $invoicesQuery->count(),
                'total_transactions'        => $txnsQuery->count(),
                'lifetime_income'           => $lifetimeIncome,
                'lifetime_expenses'         => abs($lifetimeExpenses),
                'net_profit'                => $netProfit,
                'business_currency_code'   => $bCurrency['currency'] ?? 'USD',
                'business_currency_symbol' => $bCurrency['symbol'] ?? '$',
                // Monthly averages
                'avg_monthly_income'        => $avgIncome,
                'avg_monthly_costs'         => $avgCosts,
                'avg_monthly_profit'        => $avgProfit,
                'avg_profit_margin'         => $avgMargin,
                // Academic & Financial Metrics
                'total_worked_hours'        => $totalWorkedHours,
                'effective_hourly_rate'     => $effectiveHourlyRate,
                'cost_per_worked_hour'      => $costPerWorkedHour,
                'market_hourly_rate'        => $marketHourlyRate,
                'rate_variance'             => $rateVariance,
                'total_invoiced_amount'     => round($totalInvoicedAmount, 2),
                'total_paid_invoices'       => round($totalPaidAmount, 2),
                'total_unpaid_invoices'     => round($totalUnpaidAmount, 2),
                'collection_rate_percent'   => $collectionRatePercent,
                'dso_days'                  => $dsoDays,
                'ar_aging'                  => $arAging,
                'operating_margin_percent'  => $operatingMarginPercent,
                'monthly_break_even_revenue'=> $monthlyBreakEvenRevenue,
            ],
            'charts' => [
                'monthly_trends'      => $monthlyTrends,
                'income_by_category'  => $incomeByCategory,
                'expenses_by_category'=> $expensesByCategory,
            ],
            'clients'             => $clients,
            'projects'            => $projects,
            'categories'          => $availableCategories,
            'current_year_months' => $currentYearMonths,
            'clients_worked_with' => $clientsWorkedWith,
            'filters'             => [
                'from'       => $from,
                'to'         => $to,
                'client_id'  => $clientId,
                'project_id' => $projectId,
                'category'   => $category,
            ],
        ]);
    }

    public function balance(Request $request)
    {
        $currentYear = (int) now('Africa/Cairo')->year;
        $year = (int) $request->query('year', $currentYear);

        $txnMin = Transaction::min('created_at');
        $txnMax = Transaction::max('created_at');
        $costMin = CostTransaction::min('created_at');
        $costMax = CostTransaction::max('created_at');

        $mins = array_filter([$txnMin, $costMin]);
        $maxes = array_filter([$txnMax, $costMax]);

        if (! empty($mins) && ! empty($maxes)) {
            $earliestYear = (int) Carbon::parse(min($mins))->setTimezone('Africa/Cairo')->year;
            $latestYear = (int) Carbon::parse(max($maxes))->setTimezone('Africa/Cairo')->year;
        } else {
            $earliestYear = $currentYear;
            $latestYear = $currentYear;
        }

        $availableYears = range($latestYear, $earliestYear);

        $monthlyTrends = [];
        for ($i = 1; $i <= 12; $i++) {
            $startOfMonth = Carbon::create($year, $i, 1, 0, 0, 0, 'Africa/Cairo');
            $endOfMonth = $startOfMonth->copy()->endOfMonth();

            $mReceived = Transaction::whereBetween('created_at', [$startOfMonth->copy()->setTimezone('UTC'), $endOfMonth->copy()->setTimezone('UTC')])
                ->where('type', 'received')
                ->sum('business_amount') ?? 0;
            $mRefunded = Transaction::whereBetween('created_at', [$startOfMonth->copy()->setTimezone('UTC'), $endOfMonth->copy()->setTimezone('UTC')])
                ->where('type', 'refunded')
                ->sum('business_amount') ?? 0;
            $mSent = Transaction::whereBetween('created_at', [$startOfMonth->copy()->setTimezone('UTC'), $endOfMonth->copy()->setTimezone('UTC')])
                ->where('type', 'sent')
                ->sum('business_amount') ?? 0;

            $income = max(0, abs($mReceived) - abs($mRefunded) - abs($mSent));
            $costs = CostTransaction::whereBetween('created_at', [$startOfMonth->copy()->setTimezone('UTC'), $endOfMonth->copy()->setTimezone('UTC')])
                ->sum('business_amount') ?? 0;
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
