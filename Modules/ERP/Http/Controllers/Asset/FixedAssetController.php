<?php

namespace Modules\ERP\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Asset\FixedAsset;
use Modules\ERP\Models\Asset\AssetCategory;

class FixedAssetController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = tenant('id') ?? $request->user()->tenant_id;

        $assets = FixedAsset::where('tenant_id', $tenantId)
            ->with(['category', 'assignee'])
            ->latest()
            ->get();

        $categories = AssetCategory::where('tenant_id', $tenantId)->get();

        return Inertia::render('ERP/Asset/Index', [
            'assets' => $assets,
            'categories' => $categories
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'required|date',
            'purchase_cost' => 'required|numeric|min:0',
            'salvage_value' => 'nullable|numeric|min:0',
            'asset_category_id' => 'required|exists:erp_asset_categories,id',
            'location' => 'nullable|string',
            'status' => 'required|string'
        ]);

        $tenantId = tenant('id') ?? $request->user()->tenant_id;

        FixedAsset::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'code' => $request->code,
            'serial_number' => $request->serial_number,
            'purchase_date' => $request->purchase_date,
            'purchase_cost' => $request->purchase_cost,
            'current_value' => $request->purchase_cost, // Initial value
            'salvage_value' => $request->salvage_value ?? 0,
            'asset_category_id' => $request->asset_category_id,
            'location' => $request->location,
            'status' => $request->status ?? 'active',
        ]);

        return redirect()->back()->with('success', 'Fixed Asset created successfully.');
    }

    public function update(Request $request, FixedAsset $asset)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string|max:100',
            'location' => 'nullable|string',
            'status' => 'required|string'
        ]);

        $asset->update($request->only('name', 'code', 'serial_number', 'location', 'status'));

        return redirect()->back()->with('success', 'Fixed Asset updated successfully.');
    }

    public function destroy(FixedAsset $asset)
    {
        $asset->delete();
        return redirect()->back()->with('success', 'Fixed Asset deleted successfully.');
    }
}
