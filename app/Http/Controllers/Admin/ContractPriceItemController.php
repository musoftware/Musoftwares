<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractPriceItem;
use Illuminate\Http\Request;

class ContractPriceItemController extends Controller
{
    public function index(Request $request)
    {
        $items = ContractPriceItem::all();
        if ($request->wantsJson()) {
            return response()->json($items);
        }

        $currencies = \App\Models\Currency::all();
        return \Inertia\Inertia::render('Admin/Contracts/PriceList', [
            'items' => $items,
            'currencies' => $currencies
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_price' => 'required|numeric|min:0',
            'currency_id' => 'required|integer',
        ]);

        $item = ContractPriceItem::create($validated);

        if ($request->wantsJson()) {
            return response()->json($item);
        }

        return redirect()->back()->with('success', __('general.item_created'));
    }

    public function update(Request $request, ContractPriceItem $contractPriceItem)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'default_price' => 'required|numeric|min:0',
            'currency_id' => 'required|integer',
        ]);

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

        return redirect()->back()->with('success', __('general.item_deleted'));
    }
}
