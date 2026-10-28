<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractPriceItem;
use App\Models\CurrenciesExchange;
use App\Models\Currency;
use App\Services\ProjectEstimatorDataService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * The one price list: the estimator shows the items that have a platform, contracts and the AI
 * pricing use all of them. An item's price is its fixed price, or its hours at the hourly rate.
 */
class ContractPriceItemController extends Controller
{
    public function index(Request $request)
    {
        $rate = ContractPriceItem::hourlyRateUsd();
        $items = ContractPriceItem::orderBy('sort_order')->orderBy('id')->get()
            ->map(fn (ContractPriceItem $item) => $item->toArray() + ['unit_price_usd' => $item->unitPriceUsd($rate)]);

        if ($request->wantsJson()) {
            return response()->json($items);
        }

        $estimator = (new ProjectEstimatorDataService)->getEstimatorData(1, false);

        return Inertia::render('Admin/Contracts/PriceList', [
            'items' => $items,
            'system_hourly_rate' => $rate,
            'platforms' => collect($estimator['platforms'])->map(fn ($p) => ['key' => $p['key'], 'title' => $p['title'], 'title_ar' => $p['title_ar']])->values(),
            'categories' => collect($estimator['categories'])->reject(fn ($c) => $c['id'] === 'all')->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['key'] = $this->uniqueKey($data['name_en'] ?: $data['name_ar']);
        $data['sort_order'] = (int) ContractPriceItem::max('sort_order') + 1;

        $item = ContractPriceItem::create($data);

        if ($request->wantsJson()) {
            return response()->json($item);
        }

        return redirect()->back()->with('success', __('general.item_created'));
    }

    public function update(Request $request, ContractPriceItem $contractPriceItem)
    {
        $contractPriceItem->update($this->validated($request));

        if ($request->wantsJson()) {
            return response()->json($contractPriceItem);
        }

        return redirect()->back()->with('success', __('general.item_updated'));
    }

    public function destroy(ContractPriceItem $contractPriceItem)
    {
        $contractPriceItem->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()->with('success', __('general.deleted'));
    }

    private function validated(Request $request): array
    {
        // the contract form saves a line as a new item: a single name and a price in the contract's currency
        if (! $request->filled('name_ar') && ! $request->filled('name_en') && $request->filled('name')) {
            $request->merge(['name_ar' => $request->input('name')]);
        }
        if (! $request->filled('price_usd') && $request->filled('default_price')) {
            $usd = Currency::where('currency', 'USD')->value('id');
            $from = $request->input('currency_id') ?: $usd;
            $request->merge(['price_usd' => round((float) CurrenciesExchange::RateToday((float) $request->input('default_price'), $from, $usd), 2)]);
        }
        if (! $request->filled('standalone_hours')) {
            $hours = max(1, (int) round((float) $request->input('price_usd', 0) / ContractPriceItem::hourlyRateUsd()));
            $request->merge(['standalone_hours' => $hours, 'marginal_hours' => max(1, (int) round($hours * 0.6))]);
        }
        $request->mergeIfMissing(['complexity' => 'medium']);

        $data = $request->validate([
            'name_ar'          => 'required_without:name_en|nullable|string|max:255',
            'name_en'          => 'required_without:name_ar|nullable|string|max:255',
            'description'      => 'nullable|string',
            'platform'         => ['nullable', Rule::in(ContractPriceItem::PLATFORMS)],
            'category'         => 'nullable|string|max:40',
            'price_usd'        => 'nullable|numeric|min:0',
            'standalone_hours' => 'required|integer|min:1',
            'marginal_hours'   => 'required|integer|min:1',
            'complexity'       => 'required|string|in:low,medium,high',
            'is_active'        => 'sometimes|boolean',
        ]);

        $ar = $data['name_ar'] ?? null;
        $en = $data['name_en'] ?? null;
        $data['name'] = $ar ?: $en;
        $data['name_ar'] = $ar ?: $en;
        $data['name_en'] = $en ?: $ar;
        // contracts read default_price in USD; keep it equal to the fixed price
        $data['default_price'] = $data['price_usd'] ?? 0;
        $data['currency_id'] = Currency::where('currency', 'USD')->value('id');

        return $data;
    }

    private function uniqueKey(string $name): string
    {
        $base = Str::slug($name, '_') ?: 'item';
        $key = $base;
        for ($i = 2; ContractPriceItem::where('key', $key)->exists(); $i++) {
            $key = "{$base}_{$i}";
        }

        return $key;
    }
}
