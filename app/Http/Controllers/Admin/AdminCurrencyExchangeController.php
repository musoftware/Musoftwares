<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminCurrencyExchangeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search', '');
        $currencyId = $request->get('currency_id');

        $exchanges = CurrenciesExchange::query()
            ->with(['currencyFrom:id,currency,symbol', 'currencyTo:id,currency,symbol'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('currencyFrom', fn ($qq) => $qq->where('currency', 'like', "%{$search}%"))
                    ->orWhereHas('currencyTo', fn ($qq) => $qq->where('currency', 'like', "%{$search}%"));
            })
            ->when($currencyId, function ($q) use ($currencyId) {
                $q->where(function ($q) use ($currencyId) {
                    $q->where('currency1', $currencyId)->orWhere('currency2', $currencyId);
                });
            })
            ->orderByDesc('date_string')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $currencies = Currency::orderBy('currency')->get(['id', 'currency', 'symbol']);

        return Inertia::render('Admin/CurrencyExchanges/Index', [
            'exchanges' => $exchanges,
            'currencies' => $currencies,
            'filters' => [
                'search' => $search,
                'currency_id' => $currencyId,
            ],
        ]);
    }

    public function create()
    {
        $currencies = Currency::orderBy('currency')->get(['id', 'currency', 'symbol']);

        return Inertia::render('Admin/CurrencyExchanges/Create', [
            'currencies' => $currencies,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);

        CurrenciesExchange::updateOrCreate(
            [
                'date_string' => $validated['date_string'],
                'currency1' => $validated['currency1'],
                'currency2' => $validated['currency2'],
            ],
            ['rate' => $validated['rate']]
        );

        CurrenciesExchange::flushCache();

        return redirect()->route('admin.currency-exchanges.index')
            ->with('success', __('admin.currency_exchange_created'));
    }

    public function edit(CurrenciesExchange $currencyExchange)
    {
        $currencies = Currency::orderBy('currency')->get(['id', 'currency', 'symbol']);

        $currencyExchange->load(['currencyFrom:id,currency,symbol', 'currencyTo:id,currency,symbol']);

        return Inertia::render('Admin/CurrencyExchanges/Edit', [
            'exchange' => $currencyExchange,
            'currencies' => $currencies,
        ]);
    }

    public function update(Request $request, CurrenciesExchange $currencyExchange)
    {
        $validated = $this->validatePayload($request, $currencyExchange->id);

        $currencyExchange->update($validated);

        CurrenciesExchange::flushCache();

        return redirect()->route('admin.currency-exchanges.index')
            ->with('success', __('admin.currency_exchange_updated'));
    }

    public function destroy(CurrenciesExchange $currencyExchange)
    {
        $currencyExchange->delete();

        CurrenciesExchange::flushCache();

        return redirect()->route('admin.currency-exchanges.index')
            ->with('success', __('admin.currency_exchange_deleted'));
    }

    protected function validatePayload(Request $request, ?int $ignoreId = null): array
    {
        $baseRules = [
            'date_string' => ['required', 'date_format:Y-m-d'],
            'currency1' => ['required', 'integer', 'different:currency2', Rule::exists('currencies', 'id')],
            'currency2' => ['required', 'integer', Rule::exists('currencies', 'id')],
            'rate' => ['required', 'numeric', 'gt:0'],
        ];

        $validated = $request->validate($baseRules);

        // Enforce the table-level unique constraint (date, currency1, currency2)
        $existsQuery = CurrenciesExchange::withTrashed()
            ->where('date_string', $validated['date_string'])
            ->where('currency1', $validated['currency1'])
            ->where('currency2', $validated['currency2']);

        if ($ignoreId !== null) {
            $existsQuery->where('id', '!=', $ignoreId);
        }

        if ($existsQuery->exists()) {
            throw ValidationException::withMessages([
                'date_string' => __('admin.exchange_already_exists_for_pair_on_date'),
            ]);
        }

        return $validated;
    }
}
