<?php

namespace Modules\ERP\Http\Controllers\Asset;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Models\Asset\AssetCategory;

class AssetCategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'depreciation_method' => 'nullable|string',
            'useful_life_years' => 'nullable|integer|min:1',
        ]);

        $tenantId = tenant('id') ?? $request->user()->tenant_id;

        AssetCategory::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'code' => $request->code,
            'depreciation_method' => $request->depreciation_method,
            'useful_life_years' => $request->useful_life_years,
        ]);

        return redirect()->back()->with('success', 'Asset Category created successfully.');
    }

    public function destroy(AssetCategory $category)
    {
        $category->delete();
        return redirect()->back()->with('success', 'Asset Category deleted successfully.');
    }
}
