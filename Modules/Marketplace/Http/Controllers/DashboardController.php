<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServiceCategory;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Fetch Active Purchases (As Buyer)
        $activePurchases = ServiceOrder::where('buyer_id', $user->id)
            ->whereIn('status', [ServiceOrderStatus::PENDING, ServiceOrderStatus::PROCESSING])
            ->with(['package.service', 'seller'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'title' => $order->package->service->title ?? 'Unknown Service',
                    'sellerName' => $order->seller->name ?? 'Unknown Seller',
                    'amount' => $order->amount ?? 0,
                    'status' => $order->status,
                    'deliveryDate' => $order->created_at->setTimezone('Africa/Cairo')->addDays($order->package->delivery_days ?? 3)->format('Y-m-d'),
                ];
            });

        // 2. Fetch Active Sales (As Seller)
        $activeSales = ServiceOrder::where('seller_id', $user->id)
            ->whereIn('status', [ServiceOrderStatus::PENDING, ServiceOrderStatus::PROCESSING])
            ->with(['package.service', 'buyer'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'title' => $order->package->service->title ?? 'Unknown Service',
                    'buyerName' => $order->buyer->name ?? 'Unknown Buyer',
                    'amount' => $order->amount ?? 0,
                    'status' => $order->status,
                    'deliveryDate' => $order->created_at->setTimezone('Africa/Cairo')->addDays($order->package->delivery_days ?? 3)->format('Y-m-d'),
                ];
            });

        // 3. Fetch Listed Gigs (As Seller)
        $listedGigs = Service::where('seller_id', $user->id)
            ->withMin('packages', 'price')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($service) {
                return [
                    'id' => $service->id,
                    'title' => $service->title,
                    'price' => $service->packages_min_price ?? ($service->is_free ? 0 : 5),
                    'reviews' => $service->review_count,
                    'rating' => (float) $service->avg_rating,
                ];
            });

        // 4. Compute Real Stats (Seller Perspective)
        $sellerOrdersStats = ServiceOrder::where('seller_id', $user->id)
            ->select(
                DB::raw("SUM(CASE WHEN status = '" . ServiceOrderStatus::PROCESSING->value . "' THEN amount ELSE 0 END) as locked_escrow"),
                DB::raw("SUM(CASE WHEN status = '" . ServiceOrderStatus::PROCESSING->value . "' THEN 1 ELSE 0 END) as active_orders"),
                DB::raw("SUM(CASE WHEN status = '" . ServiceOrderStatus::COMPLETED->value . "' THEN amount ELSE 0 END) as total_sales")
            )->first();

        $sellerStats = [
            'lockedEscrow' => $sellerOrdersStats->locked_escrow ?? 0,
            'activeOrders' => (int) ($sellerOrdersStats->active_orders ?? 0),
            'servicesListed' => Service::where('seller_id', $user->id)->count(),
            'totalSales' => $sellerOrdersStats->total_sales ?? 0,
        ];

        // 5. Compute Real Stats (Buyer/Client Perspective)
        $buyerOrdersStats = ServiceOrder::where('buyer_id', $user->id)
            ->select(
                DB::raw("SUM(CASE WHEN status = '" . ServiceOrderStatus::PROCESSING->value . "' THEN amount ELSE 0 END) as locked_escrow"),
                DB::raw("SUM(CASE WHEN status = '" . ServiceOrderStatus::PROCESSING->value . "' THEN 1 ELSE 0 END) as active_orders"),
                DB::raw("SUM(CASE WHEN status = '" . ServiceOrderStatus::COMPLETED->value . "' THEN amount ELSE 0 END) as total_spent")
            )->first();

        // Count unique services purchased (where status is completed)
        $distinctGigsCount = ServiceOrder::where('buyer_id', $user->id)
            ->where('status', ServiceOrderStatus::COMPLETED)
            ->join('marketplace_packages', 'marketplace_orders.package_id', '=', 'marketplace_packages.id')
            ->distinct('marketplace_packages.service_id')
            ->count('marketplace_packages.service_id');

        $buyerStats = [
            'lockedEscrow' => $buyerOrdersStats->locked_escrow ?? 0,
            'activeOrders' => (int) ($buyerOrdersStats->active_orders ?? 0),
            'servicesListed' => $distinctGigsCount,
            'totalSales' => $buyerOrdersStats->total_spent ?? 0,
        ];

        // 6. Fetch Service Categories
        $categories = ServiceCategory::all()->map(function($category) {
            return [
                'id' => $category->id,
                'name' => $category->name,
            ];
        });

        return Inertia::render('Marketplace/Dashboard', [
            'activePurchases' => $activePurchases,
            'activeSales' => $activeSales,
            'listedGigs' => $listedGigs,
            'sellerStats' => $sellerStats,
            'buyerStats' => $buyerStats,
            'categories' => $categories
        ]);
    }
}
