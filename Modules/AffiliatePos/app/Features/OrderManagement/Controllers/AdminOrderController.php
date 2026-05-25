<?php

namespace Modules\AffiliatePos\app\Features\OrderManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AffiliatePos\Models\Order;
use Modules\AffiliatePos\Models\ShippingCompany;
use Modules\AffiliatePos\app\Features\OrderManagement\Requests\UpdateOrderStatusRequest;
use Modules\AffiliatePos\app\Features\OrderManagement\Resources\OrderResource;
use Modules\AffiliatePos\app\Features\OrderManagement\Services\OrderProcessingService;
use Modules\AffiliatePos\app\Features\OrderManagement\Services\OrderQueryService;

class AdminOrderController extends Controller
{
    private $orderService;
    private $queryService;

    public function __construct(OrderProcessingService $orderService, OrderQueryService $queryService)
    {
        $this->orderService = $orderService;
        $this->queryService = $queryService;
    }

    public function index(Request $request)
    {
        $query = $this->queryService->buildQuery($request);
        $orders = $query->paginate($request->get('page_result', 50));
        return OrderResource::collection($orders);
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'items.sku', 'comments', 'user', 'shippingCompany');
        return new OrderResource($order);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $this->orderService->changeStatus($order, $request->status);
        return new OrderResource($order->load('items.product', 'items.sku'));
    }

    public function updatePartialDelivery(Request $request, Order $order)
    {
        $request->validate([
            'order_item' => 'required|array',
            'order_item.*' => 'required|string|in:delivered,returned,cancelled,working,shipping'
        ]);

        $this->orderService->partialDelivery($order, $request->order_item);
        return new OrderResource($order->load('items.product', 'items.sku'));
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'status' => 'required|string'
        ]);

        $this->orderService->bulkChangeStatus($request->ids, $request->status);
        return response()->json(['message' => 'Status updated successfully']);
    }

    public function bulkAssignShipping(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'shipping_company_id' => 'nullable|exists:affiliate_pos_shipping_companies,id'
        ]);

        $companyName = null;
        if ($request->shipping_company_id) {
            $companyName = ShippingCompany::find($request->shipping_company_id)->name;
        }

        $this->orderService->bulkAssignShippingCompany($request->ids, $request->shipping_company_id, $companyName);
        return response()->json(['message' => 'Shipping company assigned successfully']);
    }
}
