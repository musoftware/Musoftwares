<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServiceCategory;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Fetch Active Purchases (As Buyer)
        $activePurchases = ServiceOrder::where('buyer_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
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
                    'deliveryDate' => $order->created_at->addDays($order->package->delivery_days ?? 3)->format('Y-m-d'),
                ];
            });

        // 2. Fetch Active Sales (As Seller)
        $activeSales = ServiceOrder::where('seller_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
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
                    'deliveryDate' => $order->created_at->addDays($order->package->delivery_days ?? 3)->format('Y-m-d'),
                ];
            });

        // 3. Fetch Listed Gigs (As Seller)
        $listedGigs = Service::where('seller_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(function($service) {
                return [
                    'id' => $service->id,
                    'title' => $service->title,
                    'price' => $service->packages()->min('price') ?? 0,
                    'reviews' => 0, // Placeholder until reviews module is built
                    'rating' => 0.0,
                ];
            });

        // 4. Compute Real Stats
        $stats = [
            // Escrow locked hold could be sum of active orders where user is buyer or seller
            'lockedEscrow' => ServiceOrder::where('seller_id', $user->id)->where('status', 'processing')->sum('amount'),
            'activeOrders' => ServiceOrder::where('seller_id', $user->id)->where('status', 'processing')->count(),
            'servicesListed' => Service::where('seller_id', $user->id)->count(),
            'totalSales' => ServiceOrder::where('seller_id', $user->id)->where('status', 'completed')->sum('amount'),
        ];

        // 5. Fetch Service Categories for Publish Modal
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
            'stats' => $stats,
            'categories' => $categories
        ]);
    }
}
