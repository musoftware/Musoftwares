<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\RecurringEntry;
use Modules\ERP\Models\Tenant;
use App\Services\ExchangeRateService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecurringController extends Controller
{
    protected $exchangeRateService;

    public function __construct(ExchangeRateService $exchangeRateService)
    {
        $this->exchangeRateService = $exchangeRateService;
    }

    public function index()
    {
        $tenant = Tenant::where('user_id', Auth::id())->firstOrFail();

        // H3 fix: scope to current tenant only
        $entries = RecurringEntry::where('tenant_id', $tenant->id)->latest()->get();

        $income  = $entries->where('type', 'income');
        $expense = $entries->where('type', 'expense');

        // H4 fix: resolve business currency from tenant's currency
        $currency = \App\Models\Currency::find($tenant->base_currency_id);
        $businessCurrency = $currency ? $currency->currency : config('app.business_currency', 'USD');

        $stats = [
            'income' => [
                'total_monthly' => $this->calculateMonthlyTotal($income),
                'next_7_days'   => $income->whereBetween('next_run_at', [now(), now()->addDays(7)])->count(),
            ],
            'expense' => [
                'total_monthly' => $this->calculateMonthlyTotal($expense),
                'next_7_days'   => $expense->whereBetween('next_run_at', [now(), now()->addDays(7)])->count(),
            ],
            'business_currency' => $businessCurrency,
        ];

        return Inertia::render('ERP/Recurring/Index', [
            'income'  => $income->values(),
            'expense' => $expense->values(),
            'stats'   => $stats,
        ]);
    }

    private function calculateMonthlyTotal($entries)
    {
        $total = 0;
        foreach ($entries as $entry) {
            if ($entry->status !== 'active') continue;

            $amount = (float)$entry->business_amount;
            switch ($entry->frequency) {
                case 'daily':
                    $total += $amount * 30;
                    break;
                case 'weekly':
                    $total += $amount * 4.33;
                    break;
                case 'monthly':
                    $total += $amount;
                    break;
                case 'yearly':
                    $total += $amount / 12;
                    break;
            }
        }
        return round($total, 2);
    }

    public function create()
    {
        $tenant = Tenant::where('user_id', Auth::id())->firstOrFail();
        $currency = \App\Models\Currency::find($tenant->base_currency_id);
        $businessCurrency = $currency ? $currency->currency : config('app.business_currency', 'USD');
        return Inertia::render('ERP/Recurring/Create', [
            'business_currency' => $businessCurrency,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'amount_currency' => 'required|string|size:3',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'frequency_day' => 'nullable|integer',
            'frequency_month' => 'nullable|integer',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        // H4 fix: resolve business currency from tenant
        $tenant = Tenant::where('user_id', Auth::id())->firstOrFail();
        $currency = \App\Models\Currency::find($tenant->base_currency_id);
        $businessCurrency = $currency ? $currency->currency : config('app.business_currency', 'USD');
        $conversion = $this->exchangeRateService->convertAmount(
            (float)$validated['amount'],
            $validated['amount_currency'],
            $businessCurrency,
            now()
        );

        $currencyModel = \App\Models\Currency::where('currency', $validated['amount_currency'])->first();
        if (!$currencyModel) {
            throw \Illuminate\Validation\ValidationException::withMessages(['amount_currency' => __('errors.invalid_currency')]);
        }
        $validated['currency_id']        = $currencyModel->id;
        $validated['business_amount']    = $conversion[2];
        $validated['business_currency_id']= $tenant->base_currency_id;
        $validated['exchange_rate']      = $conversion[4];
        $validated['exchange_rate_date'] = $conversion[5];
        $validated['status']             = 'active';
        $validated['tenant_id']          = $tenant->id;
        $validated['created_by']         = Auth::id();

        $validated['next_run_at'] = $this->calculateFirstRun(
            $validated['frequency'],
            $validated['starts_at'],
            $validated['frequency_day'],
            $validated['frequency_month']
        );

        RecurringEntry::create($validated);

        return redirect()->route('erp.recurring.index')->with('success', __('general.recurring_entry_created'));
    }

    private function calculateFirstRun($frequency, $startsAt, $day = null, $month = null)
    {
        $start = Carbon::parse($startsAt);
        $target = $start->copy();

        if ($frequency === 'weekly') {
            if ($day !== null) {
                $target->next((int)$day);
                if ($target->lt($start)) {
                    $target->addWeek();
                }
            }
        } elseif ($frequency === 'monthly') {
            if ($day !== null) {
                $target->day((int)$day);
                if ($target->lt($start)) {
                    $target->addMonth();
                }
            }
        } elseif ($frequency === 'yearly') {
            if ($month !== null && $day !== null) {
                $target->month((int)$month)->day((int)$day);
                if ($target->lt($start)) {
                    $target->addYear();
                }
            }
        }

        return $target->toDateString();
    }

    private function authorizeTenantRecurringEntry(RecurringEntry $entry)
    {
        $tenant = Tenant::where('user_id', Auth::id())->firstOrFail();
        if ($entry->tenant_id !== $tenant->id) {
            abort(403, __('general.unauthorized_access_to_recurring_entry'));
        }
    }

    public function edit(RecurringEntry $recurring)
    {
        $this->authorizeTenantRecurringEntry($recurring);

        $tenant = Tenant::where('user_id', Auth::id())->firstOrFail();
        $currency = \App\Models\Currency::find($tenant->base_currency_id);
        $businessCurrency = $currency ? $currency->currency : config('app.business_currency', 'USD');
        return Inertia::render('ERP/Recurring/Edit', [
            'entry' => $recurring,
            'business_currency' => $businessCurrency,
        ]);
    }

    public function update(Request $request, RecurringEntry $recurring)
    {
        $this->authorizeTenantRecurringEntry($recurring);

        $validated = $request->validate([
            'type' => 'required|in:income,expense',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'amount' => 'required|numeric|min:0',
            'amount_currency' => 'required|string|size:3',
            'frequency' => 'required|in:daily,weekly,monthly,yearly',
            'frequency_day' => 'nullable|integer',
            'frequency_month' => 'nullable|integer',
            'starts_at' => 'required|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'status' => 'required|in:active,paused,cancelled',
        ]);

        $tenant = Tenant::where('user_id', Auth::id())->firstOrFail();
        $currency = \App\Models\Currency::find($tenant->base_currency_id);
        $businessCurrency = $currency ? $currency->currency : config('app.business_currency', 'USD');
        $conversion = $this->exchangeRateService->convertAmount(
            (float)$validated['amount'],
            $validated['amount_currency'],
            $businessCurrency,
            now()
        );

        $currencyModel = \App\Models\Currency::where('currency', $validated['amount_currency'])->first();
        if (!$currencyModel) {
            throw \Illuminate\Validation\ValidationException::withMessages(['amount_currency' => __('errors.invalid_currency')]);
        }
        $validated['currency_id']        = $currencyModel->id;
        $validated['business_amount'] = $conversion[2];
        $validated['business_currency_id'] = $tenant->base_currency_id;
        $validated['exchange_rate'] = $conversion[4];
        $validated['exchange_rate_date'] = $conversion[5];

        if ($recurring->frequency !== $validated['frequency'] ||
            $recurring->frequency_day != $validated['frequency_day'] ||
            $recurring->frequency_month != $validated['frequency_month'] ||
            (method_exists($recurring->starts_at, 'toDateString') ? $recurring->starts_at->toDateString() : Carbon::parse($recurring->starts_at)->toDateString()) !== $validated['starts_at']) {

            $validated['next_run_at'] = $this->calculateFirstRun(
                $validated['frequency'],
                $validated['starts_at'],
                $validated['frequency_day'],
                $validated['frequency_month']
            );
        }

        $recurring->update($validated);

        return redirect()->route('erp.recurring.index')->with('success', __('general.recurring_entry_updated'));
    }

    public function destroy(RecurringEntry $recurring)
    {
        $this->authorizeTenantRecurringEntry($recurring);

        $recurring->delete();
        return redirect()->route('erp.recurring.index')->with('success', __('general.recurring_entry_deleted'));
    }

    public function pause(RecurringEntry $recurring)
    {
        $this->authorizeTenantRecurringEntry($recurring);

        $recurring->update(['status' => 'paused']);
        return redirect()->back();
    }

    public function resume(RecurringEntry $recurring)
    {
        $this->authorizeTenantRecurringEntry($recurring);

        $recurring->update(['status' => 'active']);
        return redirect()->back();
    }

    public function logs(RecurringEntry $recurring)
    {
        $this->authorizeTenantRecurringEntry($recurring);

        $logs = $recurring->executionLogs()->latest()->paginate(15);
        return Inertia::render('ERP/Recurring/Logs', [
            'entry' => $recurring,
            'logs' => $logs,
        ]);
    }
}
