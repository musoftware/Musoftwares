<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\RecurringEntry;
use Modules\Core\Services\ExchangeRateService;
use Carbon\Carbon;
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
        $entries = RecurringEntry::latest()->get();

        $income = $entries->where('type', 'income');
        $expense = $entries->where('type', 'expense');

        $stats = [
            'income' => [
                'total_monthly' => $this->calculateMonthlyTotal($income),
                'next_7_days' => $income->whereBetween('next_run_at', [now(), now()->addDays(7)])->count(),
            ],
            'expense' => [
                'total_monthly' => $this->calculateMonthlyTotal($expense),
                'next_7_days' => $expense->whereBetween('next_run_at', [now(), now()->addDays(7)])->count(),
            ],
            'business_currency' => session('business_currency', 'USD'),
        ];

        return Inertia::render('ERP/Recurring/Index', [
            'income' => $income->values(),
            'expense' => $expense->values(),
            'stats' => $stats,
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
        return Inertia::render('ERP/Recurring/Create', [
            'business_currency' => session('business_currency', 'USD'),
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

        $businessCurrency = session('business_currency', 'USD');
        $conversion = $this->exchangeRateService->convertAmount(
            (float)$validated['amount'],
            $validated['amount_currency'],
            $businessCurrency,
            now()
        );

        $validated['business_amount'] = $conversion[2];
        $validated['business_currency'] = $conversion[3];
        $validated['exchange_rate'] = $conversion[4];
        $validated['exchange_rate_date'] = $conversion[5];
        $validated['status'] = 'active';
        $validated['created_by'] = auth()->id();

        $validated['next_run_at'] = $this->calculateFirstRun(
            $validated['frequency'],
            $validated['starts_at'],
            $validated['frequency_day'],
            $validated['frequency_month']
        );

        RecurringEntry::create($validated);

        return redirect()->route('erp.recurring.index')->with('success', 'Recurring entry created.');
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

    public function edit(RecurringEntry $recurring)
    {
        return Inertia::render('ERP/Recurring/Edit', [
            'entry' => $recurring,
            'business_currency' => session('business_currency', 'USD'),
        ]);
    }

    public function update(Request $request, RecurringEntry $recurring)
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
            'status' => 'required|in:active,paused,cancelled',
        ]);

        $businessCurrency = session('business_currency', 'USD');
        $conversion = $this->exchangeRateService->convertAmount(
            (float)$validated['amount'],
            $validated['amount_currency'],
            $businessCurrency,
            now()
        );

        $validated['business_amount'] = $conversion[2];
        $validated['business_currency'] = $conversion[3];
        $validated['exchange_rate'] = $conversion[4];
        $validated['exchange_rate_date'] = $conversion[5];

        if ($recurring->frequency !== $validated['frequency'] ||
            $recurring->frequency_day != $validated['frequency_day'] ||
            $recurring->frequency_month != $validated['frequency_month'] ||
            $recurring->starts_at->toDateString() !== $validated['starts_at']) {

            $validated['next_run_at'] = $this->calculateFirstRun(
                $validated['frequency'],
                $validated['starts_at'],
                $validated['frequency_day'],
                $validated['frequency_month']
            );
        }

        $recurring->update($validated);

        return redirect()->route('erp.recurring.index')->with('success', 'Recurring entry updated.');
    }

    public function destroy(RecurringEntry $recurring)
    {
        $recurring->delete();
        return redirect()->route('erp.recurring.index')->with('success', 'Recurring entry deleted.');
    }

    public function pause(RecurringEntry $recurring)
    {
        $recurring->update(['status' => 'paused']);
        return redirect()->back();
    }

    public function resume(RecurringEntry $recurring)
    {
        $recurring->update(['status' => 'active']);
        return redirect()->back();
    }

    public function logs(RecurringEntry $recurring)
    {
        $logs = $recurring->executionLogs()->latest()->paginate(15);
        return Inertia::render('ERP/Recurring/Logs', [
            'entry' => $recurring,
            'logs' => $logs,
        ]);
    }
}
