<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CurrencyExchange\StoreCurrencyExchangeRequest;
use App\Http\Requests\Admin\CurrencyExchange\UpdateCurrencyExchangeRequest;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCurrencyExchangeController extends Controller
{
    public function index(Request $request): Response
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

    public function create(): Response
    {
        $currencies = Currency::orderBy('currency')->get(['id', 'currency', 'symbol']);

        return Inertia::render('Admin/CurrencyExchanges/Create', [
            'currencies' => $currencies,
        ]);
    }

    public function store(StoreCurrencyExchangeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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

    public function edit(CurrenciesExchange $currencyExchange): Response
    {
        $currencies = Currency::orderBy('currency')->get(['id', 'currency', 'symbol']);

        $currencyExchange->load(['currencyFrom:id,currency,symbol', 'currencyTo:id,currency,symbol']);

        return Inertia::render('Admin/CurrencyExchanges/Edit', [
            'exchange' => $currencyExchange,
            'currencies' => $currencies,
        ]);
    }

    public function update(UpdateCurrencyExchangeRequest $request, CurrenciesExchange $currencyExchange): RedirectResponse
    {
        $currencyExchange->update($request->validated());

        CurrenciesExchange::flushCache();

        return redirect()->route('admin.currency-exchanges.index')
            ->with('success', __('admin.currency_exchange_updated'));
    }

    public function destroy(CurrenciesExchange $currencyExchange): RedirectResponse
    {
        $currencyExchange->delete();

        CurrenciesExchange::flushCache();

        return redirect()->route('admin.currency-exchanges.index')
            ->with('success', __('admin.currency_exchange_deleted'));
    }
}
