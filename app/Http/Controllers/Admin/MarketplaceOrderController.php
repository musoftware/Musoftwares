<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Core\Models\ServiceOrder;
use Inertia\Inertia;

class MarketplaceOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = ServiceOrder::with(['buyer:id,name,email', 'seller:id,name,email', 'package.service'])
            ->latest()
            ->paginate(20);

        return Inertia::render('Admin/Marketplace/Orders/Index', [
            'orders' => $orders
        ]);
    }

    public function show($id)
    {
        $order = ServiceOrder::with(['buyer', 'seller', 'package.service'])->findOrFail($id);

        return Inertia::render('Admin/Marketplace/Orders/Show', [
            'order' => $order
        ]);
    }

    public function resolveDispute(Request $request, $id)
    {
        $request->validate([
            'action' => 'required|in:refund_buyer,release_to_seller'
        ]);

        $order = ServiceOrder::findOrFail($id);

        if ($order->status === 'completed' || $order->status === 'cancelled') {
            return back()->withErrors(['error' => 'Order is already closed.']);
        }

        if ($request->action === 'refund_buyer') {
            $order->status = 'cancelled';
            $order->save();
            // Integration with wallet refund should be placed here
        } elseif ($request->action === 'release_to_seller') {
            $order->status = 'completed';
            $order->completed_at = now();
            $order->save();
            // Integration with wallet release should be placed here
        }

        return back()->with('success', 'Order resolved successfully.');
    }
}
