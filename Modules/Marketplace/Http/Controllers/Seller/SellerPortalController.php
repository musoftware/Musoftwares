<?php

namespace Modules\Marketplace\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Enums\EscrowStatus;

class SellerPortalController extends Controller
{
    public function dashboard(Request $request)
    {
        $sellerId = $request->user()->id;

        $totalSales = ServiceOrder::where('seller_id', $sellerId)
            ->whereIn('status', [ServiceOrderStatus::COMPLETED, ServiceOrderStatus::DELIVERED])
            ->sum('amount');

        $activeProducts = Service::where('seller_id', $sellerId)
            ->where('status', 'active')
            ->count();

        $pendingPayouts = MarketplaceEscrow::whereHas('order', function($q) use ($sellerId) {
            $q->where('seller_id', $sellerId);
        })->where('status', EscrowStatus::HELD)->sum('amount');

        $recentOrders = ServiceOrder::with(['buyer', 'package.service'])
            ->where('seller_id', $sellerId)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return Inertia::render('Marketplace/Seller/Dashboard', [
            'stats' => [
                'total_sales' => $totalSales,
                'active_products' => $activeProducts,
                'pending_payouts' => $pendingPayouts,
            ],
            'recent_orders' => $recentOrders,
        ]);
    }

    public function products(Request $request)
    {
        $products = Service::with(['category', 'packages'])
            ->where('seller_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Marketplace/Seller/Products', [
            'products' => $products
        ]);
    }

    public function payouts(Request $request)
    {
        $escrows = MarketplaceEscrow::with(['order.package.service'])
            ->whereHas('order', function($q) use ($request) {
                $q->where('seller_id', $request->user()->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Marketplace/Seller/Payouts', [
            'escrows' => $escrows
        ]);
    }
}
