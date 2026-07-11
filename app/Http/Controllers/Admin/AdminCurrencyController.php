<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class AdminCurrencyController extends Controller
{
    public function index(Request $request)
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

    public function create()
    {
        return Inertia::render('Admin/Currencies/Create');
    }

    public function store(Request $request)
    {
        $validated = $this->validatePayload($request);
        $validated = $this->ensureUniqueCurrencyCode($validated);

        Currency::create($validated);

        return redirect()->route('admin.currencies.index')
            ->with('success', __('admin.currency_created'));
    }

    public function edit(Currency $currency)
    {
        return Inertia::render('Admin/Currencies/Edit', [
            'currency' => $currency,
        ]);
    }

    public function update(Request $request, Currency $currency)
    {
        $validated = $this->validatePayload($request);
        $validated = $this->ensureUniqueCurrencyCode($validated, $currency->id);

        $currency->update($validated);

        return redirect()->route('admin.currencies.index')
            ->with('success', __('admin.currency_updated'));
    }

    public function destroy(Currency $currency)
    {
        $currency->delete();

        return redirect()->route('admin.currencies.index')
            ->with('success', __('admin.currency_deleted'));
    }

    protected function validatePayload(Request $request): array
    {
        return $request->validate([
            'currency' => ['required', 'string', 'max:10'],
            'symbol' => ['required', 'string', 'max:10'],
            'string_format' => ['required', 'string', 'max:20'],
        ]);
    }

    protected function ensureUniqueCurrencyCode(array $validated, ?int $ignoreId = null): array
    {
        $exists = Currency::withTrashed()
            ->where('currency', $validated['currency'])
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'currency' => __('validation.unique', ['attribute' => 'currency']),
            ]);
        }

        return $validated;
    }
}
