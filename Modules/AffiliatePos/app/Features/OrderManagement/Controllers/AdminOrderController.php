<?php

namespace Modules\AffiliatePos\app\Features\OrderManagement\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
        $orders = $query->paginate($request->get('page_result', 50))
            ->through(fn ($order) => (new OrderResource($order))->resolve());
            
        return Inertia::render('AffiliatePos/Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->all()
        ]);
    }

    public function show(Order $order)
    {
        $order->load('items.product', 'items.sku', 'comments', 'user', 'shippingCompany');
        return Inertia::render('AffiliatePos/Admin/Orders/Show', [
            'order' => (new OrderResource($order))->resolve()
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order)
    {
        $this->orderService->changeStatus($order, $request->status);
        return back()->with('success', 'Order status updated successfully');
    }

    public function updatePartialDelivery(Request $request, Order $order)
    {
        $request->validate([
            'order_item' => 'required|array',
            'order_item.*' => 'required|string|in:delivered,returned,cancelled,working,shipping'
        ]);

        $this->orderService->partialDelivery($order, $request->order_item);
        return back()->with('success', 'Partial delivery applied successfully');
    }

    public function bulkUpdateStatus(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'status' => 'required|string'
        ]);

        $this->orderService->bulkChangeStatus($request->ids, $request->status);
        return back()->with('success', 'Status updated successfully');
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
        return back()->with('success', 'Shipping company assigned successfully');
    }
}
