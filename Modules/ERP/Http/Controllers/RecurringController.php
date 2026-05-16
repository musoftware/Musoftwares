<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Core\Models\Currency;
use Modules\Core\Models\ExchangeRate;
use Modules\Core\Services\ExchangeRateService;
use Modules\ERP\Models\RecurringEntry;
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
        $income = RecurringEntry::where('type', 'income')->latest()->get();
        $expenses = RecurringEntry::where('type', 'expense')->latest()->get();

        $stats = [
            'income' => [
                'totalMonthly' => $this->calculateMonthlyTotal($income),
                'next7Days' => $income->whereBetween('next_run_at', [now(), now()->addDays(7)])->count(),
            ],
            'expense' => [
                'totalMonthly' => $this->calculateMonthlyTotal($expenses),
                'next7Days' => $expenses->whereBetween('next_run_at', [now(), now()->addDays(7)])->count(),
            ],
        ];

        return Inertia::render('ERP/Recurring/Index', [
            'income' => $income,
            'expenses' => $expenses,
            'stats' => $stats,
        ]);
    }

    private function calculateMonthlyTotal($entries)
    {
        $total = 0;
        foreach ($entries as $entry) {
            $amount = $entry->business_amount;
            switch ($entry->frequency) {
                case 'daily':
                    $total += $amount * 30;
                    break;
                case 'weekly':
                    $total += $amount * 4;
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
            'currencies' => Currency::all(),
            'exchangeRates' => ExchangeRate::where('to_currency', 'USD')->get(),
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

        $businessCurrency = 'USD'; // Assuming USD is business currency
        $conversion = $this->exchangeRateService->convertAmount(
            $validated['amount'],
            $validated['amount_currency'],
            $businessCurrency
        );

        // $conversion returns [amount, from, businessAmount, to, rate, date]
        $data = array_merge($validated, [
            'business_amount' => $conversion[2],
            'business_currency' => $conversion[3],
            'exchange_rate' => $conversion[4],
            'exchange_rate_date' => $conversion[5],
            'next_run_at' => $validated['starts_at'],
            'next_date' => $validated['starts_at'],
            'status' => 'active',
            'is_active' => true,
            'created_by' => auth()->id(),
        ]);

        RecurringEntry::create($data);

        return redirect()->route('erp.recurring.index')->with('success', 'Recurring entry created successfully.');
    }

    public function edit(RecurringEntry $recurring)
    {
        return Inertia::render('ERP/Recurring/Edit', [
            'recurring' => $recurring,
            'currencies' => Currency::all(),
            'exchangeRates' => ExchangeRate::where('to_currency', 'USD')->get(),
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
        ]);

        $businessCurrency = 'USD';
        $conversion = $this->exchangeRateService->convertAmount(
            $validated['amount'],
            $validated['amount_currency'],
            $businessCurrency
        );

        $data = array_merge($validated, [
            'business_amount' => $conversion[2],
            'business_currency' => $conversion[3],
            'exchange_rate' => $conversion[4],
            'exchange_rate_date' => $conversion[5],
        ]);

        $recurring->update($data);

        return redirect()->route('erp.recurring.index')->with('success', 'Recurring entry updated successfully.');
    }

    public function destroy(RecurringEntry $recurring)
    {
        $recurring->delete();

        return redirect()->route('erp.recurring.index')->with('success', 'Recurring entry deleted successfully.');
    }

    public function pause(RecurringEntry $recurring)
    {
        $recurring->update(['status' => 'paused', 'is_active' => false]);
        return redirect()->back()->with('success', 'Recurring entry paused.');
    }

    public function resume(RecurringEntry $recurring)
    {
        $recurring->update(['status' => 'active', 'is_active' => true]);
        return redirect()->back()->with('success', 'Recurring entry resumed.');
    }

    public function logs(RecurringEntry $recurring)
    {
        $logs = $recurring->executionLogs()
            ->latest()
            ->paginate(15);

        return Inertia::render('ERP/Recurring/Logs', [
            'recurring' => $recurring,
            'logs' => $logs,
        ]);
    }
}
