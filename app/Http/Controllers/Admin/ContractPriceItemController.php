<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminSettings;
use App\Models\ContractPriceItem;
use App\Models\Currency;
use App\Services\AI\ScopePricingEngine;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ContractPriceItemController extends Controller
{
    public function index(Request $request)
    {
        $items = ContractPriceItem::orderBy('sort_order', 'asc')->get();
        if ($request->wantsJson()) {
            return response()->json($items);
        }

        $currencies = Currency::all();
        $marketHourlyRate = (float) AdminSettings::GetValue('market_hourly_rate', ScopePricingEngine::BASE_HOURLY_RATE_USD);
        if ($marketHourlyRate <= 0) {
            $marketHourlyRate = ScopePricingEngine::BASE_HOURLY_RATE_USD;
        }

        return Inertia::render('Admin/Contracts/PriceList', [
            'items'              => $items,
            'currencies'         => $currencies,
            'system_hourly_rate' => $marketHourlyRate,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'standalone_hours' => 'required|integer|min:1',
            'marginal_hours'   => 'required|integer|min:1',
            'complexity'       => 'required|string|in:low,medium,high',
            'currency_id'      => 'nullable|integer',
        ]);

        $validated['key'] = \Illuminate\Support\Str::slug($validated['name'], '_');
        $validated['name_ar'] = $validated['name'];
        $validated['name_en'] = $validated['name'];

        $item = ContractPriceItem::create($validated);

        if ($request->wantsJson()) {
            return response()->json($item);
        }

        return redirect()->back()->with('success', __('general.item_created'));
    }

    public function update(Request $request, ContractPriceItem $contractPriceItem)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'description'      => 'nullable|string',
            'standalone_hours' => 'required|integer|min:1',
            'marginal_hours'   => 'required|integer|min:1',
            'complexity'       => 'required|string|in:low,medium,high',
            'currency_id'      => 'nullable|integer',
        ]);

        $validated['name_ar'] = $validated['name'];
        $validated['name_en'] = $validated['name'];

        $contractPriceItem->update($validated);

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
}
