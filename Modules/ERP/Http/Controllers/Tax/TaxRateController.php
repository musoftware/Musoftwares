<?php

namespace Modules\ERP\Http\Controllers\Tax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Tax\TaxRate;

class TaxRateController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = tenant('id') ?? $request->user()->tenant_id;

        $rates = TaxRate::where('tenant_id', $tenantId)->latest()->get();

        return Inertia::render('ERP/Tax/Rates/Index', [
            'rates' => $rates
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'type' => 'required|in:percentage,fixed',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $tenantId = tenant('id') ?? $request->user()->tenant_id;

        TaxRate::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'rate' => $request->rate,
            'type' => $request->type,
            'description' => $request->description,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->back()->with('success', 'Tax Rate created successfully.');
    }

    public function update(Request $request, TaxRate $rate)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'required|numeric|min:0',
            'type' => 'required|in:percentage,fixed',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]);

        $rate->update($request->only('name', 'rate', 'type', 'description', 'is_active'));

        return redirect()->back()->with('success', 'Tax Rate updated successfully.');
    }

    public function destroy(TaxRate $rate)
    {
        $rate->delete();
        return redirect()->back()->with('success', 'Tax Rate deleted successfully.');
    }
}
