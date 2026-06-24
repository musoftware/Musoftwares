<?php

namespace Modules\ERP\Http\Controllers\Warehouse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\ERP\Models\Warehouse\StockTransfer;
use Modules\ERP\Models\Warehouse\Warehouse;
use Modules\ERP\Services\Warehouse\WarehouseService;

class StockTransferController extends Controller
{
    protected WarehouseService $warehouseService;

    public function __construct(WarehouseService $warehouseService)
    {
        $this->warehouseService = $warehouseService;
    }

    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $transfers = StockTransfer::where('tenant_id', $tenantId)
            ->with(['fromWarehouse', 'toWarehouse'])
            ->latest()
            ->get();

        $warehouses = Warehouse::where('tenant_id', $tenantId)->get();

        return Inertia::render('ERP/Warehouse/Transfers/Index', [
            'transfers' => $transfers,
            'warehouses' => $warehouses
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'from_warehouse_id' => 'required|exists:erp_warehouses,id',
            'to_warehouse_id' => 'required|exists:erp_warehouses,id|different:from_warehouse_id',
            'product_id' => 'required|exists:erp_products,id',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string'
        ]);

        $tenantId = $request->user()->tenant_id;

        try {
            $this->warehouseService->initiateTransfer(
                $tenantId,
                $request->from_warehouse_id,
                $request->to_warehouse_id,
                $request->product_id,
                $request->quantity,
                $request->notes
            );
            return redirect()->back()->with('success', 'Transfer initiated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
