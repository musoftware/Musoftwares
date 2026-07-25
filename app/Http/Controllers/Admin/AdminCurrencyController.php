<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Currency\StoreCurrencyRequest;
use App\Http\Requests\Admin\Currency\UpdateCurrencyRequest;
use App\Models\Currency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminCurrencyController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->get('search', '');

        $currencies = Currency::query()
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('currency', 'like', "%{$search}%")
                    ->orWhere('symbol', 'like', "%{$search}%");
            }))
            ->withCount(['exchangesFrom', 'exchangesTo'])
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Currencies/Index', [
            'currencies' => $currencies,
            'search' => $search,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Currencies/Create');
    }

    public function store(StoreCurrencyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['is_default'])) {
            Currency::query()->update(['is_default' => false]);
        }
        Currency::create($data);

        return redirect()->route('admin.currencies.index')
            ->with('success', __('admin.currency_created'));
    }

    public function edit(Currency $currency): Response
    {
        return Inertia::render('Admin/Currencies/Edit', [
            'currency' => $currency,
        ]);
    }

    public function update(UpdateCurrencyRequest $request, Currency $currency): RedirectResponse
    {
        $data = $request->validated();
        if (! empty($data['is_default'])) {
            Currency::where('id', '!=', $currency->id)->update(['is_default' => false]);
        }
        $currency->update($data);

        return redirect()->route('admin.currencies.index')
            ->with('success', __('admin.currency_updated'));
    }

    public function destroy(Currency $currency): RedirectResponse
    {
        $currency->delete();

        return redirect()->route('admin.currencies.index')
            ->with('success', __('admin.currency_deleted'));
    }
}
