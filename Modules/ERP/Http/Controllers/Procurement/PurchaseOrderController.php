<?php

namespace Modules\ERP\Http\Controllers\Procurement;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ERP\Services\Procurement\PurchaseOrderService;

class PurchaseOrderController extends Controller
{
    protected $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    public function index(Request $request)
    {
        $orders = $this->purchaseOrderService->getAll($request->all());
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|uuid',
            'purchase_request_id' => 'nullable|uuid',
            'po_number' => 'required|string|max:100',
            'status' => 'nullable|string',
            'currency_id' => 'required|integer',
            'exchange_rate' => 'nullable|numeric',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $order = $this->purchaseOrderService->create($data);
        return response()->json($order, 201);
    }

    public function show(string $id)
    {
        $order = $this->purchaseOrderService->getById($id);
        return response()->json($order);
    }

    public function update(Request $request, string $id)
    {
        $data = $request->validate([
            'status' => 'sometimes|required|string',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $order = $this->purchaseOrderService->getById($id);
        $order = $this->purchaseOrderService->update($order, $data);

        return response()->json($order);
    }

    public function destroy(string $id)
    {
        $order = $this->purchaseOrderService->getById($id);
        $this->purchaseOrderService->delete($order);

        return response()->json(['message' => 'Purchase Order deleted successfully']);
    }
}
