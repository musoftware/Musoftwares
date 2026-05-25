<?php

namespace Modules\AffiliatePos\app\Features\VendorPortal\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Modules\AffiliatePos\Models\Order;
use Modules\AffiliatePos\app\Features\OrderManagement\Resources\OrderResource;

class VendorOrderController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();
        
        $query = Order::whereHas('items.product', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with(['items' => function($q) use ($userId) {
            $q->whereHas('product', function($q2) use ($userId) {
                $q2->where('user_id', $userId);
            })->with('product', 'sku');
        }])->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(50)->through(fn($order) => (new OrderResource($order))->resolve());
        
        return Inertia::render('AffiliatePos/Vendor/Orders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status'])
        ]);
    }

    public function show(Order $order)
    {
        $userId = Auth::id();
        
        // Ensure vendor has at least one item in this order
        $hasVendorItem = $order->items()->whereHas('product', function($q) use ($userId) {
            $q->where('user_id', $userId);
        })->exists();

        if (!$hasVendorItem) {
            abort(403, 'Unauthorized access to order.');
        }

        $order->load(['items' => function($q) use ($userId) {
            // Vendors only see their own items in the order
            $q->whereHas('product', function($q2) use ($userId) {
                $q2->where('user_id', $userId);
            })->with('product', 'sku');
        }]);

        return Inertia::render('AffiliatePos/Vendor/Orders/Show', [
            'order' => (new OrderResource($order))->resolve()
        ]);
    }
}
