<?php

namespace Modules\ERP\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Warehouse\Warehouse;
use Illuminate\Support\Str;

class WarehouseController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $warehouses = Warehouse::where('tenant_id', $tenantId)
            ->withCount(['zones', 'stockTransfersFrom', 'stockTransfersTo'])
            ->latest()
            ->get();

        return Inertia::render('ERP/Warehouse/Index', [
            'warehouses' => $warehouses
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);

        $tenantId = $request->user()->tenant_id;

        Warehouse::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'code' => $request->code,
            'city' => $request->city,
            'country' => $request->country,
            'is_active' => $request->is_active ?? true,
        ]);

        return redirect()->back()->with('success', 'Warehouse created successfully.');
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'is_active' => 'boolean'
        ]);

        $warehouse->update($request->only('name', 'code', 'city', 'country', 'is_active'));

        return redirect()->back()->with('success', 'Warehouse updated successfully.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return redirect()->back()->with('success', 'Warehouse deleted successfully.');
    }
}
