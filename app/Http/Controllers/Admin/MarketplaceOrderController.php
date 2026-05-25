<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceOrder;
use App\Services\MarketplaceOrderService;
use App\Http\Requests\Admin\MarketplaceOrder\ResolveMarketplaceDisputeRequest;
use App\Http\Resources\MarketplaceOrderResource;
use Inertia\Inertia;

class MarketplaceOrderController extends Controller
{
    public function __construct(
        protected MarketplaceOrderService $marketplaceOrderService
    ) {}
    public function index(Request $request)
    {
        $orders = ServiceOrder::with(['buyer:id,name,email', 'seller:id,name,email', 'package.service'])
            ->latest()
            ->paginate(20)
            ->through(fn($o) => clone (new MarketplaceOrderResource($o))->resolve());

        return Inertia::render('Admin/Marketplace/Orders/Index', [
            'orders' => $orders
        ]);
    }

    public function show($id)
    {
        $order = ServiceOrder::with(['buyer', 'seller', 'package.service'])->findOrFail($id);

        return Inertia::render('Admin/Marketplace/Orders/Show', [
            'order' => clone (new MarketplaceOrderResource($order))->resolve()
        ]);
    }

    public function resolveDispute(ResolveMarketplaceDisputeRequest $request, $id)
    {
        $order = ServiceOrder::findOrFail($id);

        try {
            $this->marketplaceOrderService->resolveDispute($order, $request->validated('action'));
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }

        return back()->with('success', 'Order resolved successfully.');
    }
}
