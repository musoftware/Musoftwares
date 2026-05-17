<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceOrder;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // 1. Fetch Active Purchases (As Buyer)
        $activePurchases = ServiceOrder::where('buyer_id', $user->id)
            ->whereIn('status', ['pending', 'in_progress', 'in_revision'])
            ->with('service:id,title,seller_id')
            ->latest()
            ->take(5)
            ->get()
            ->map(function($order) {
                return [
                    'id' => $order->id,
                    'title' => $order->service->title ?? 'Unknown Service',
                    'sellerName' => $order->service->seller->name ?? 'Unknown Seller',
                    'amount' => $order->total_price ?? 0,
                    'status' => $order->status,
                    'deliveryDate' => clone $order->created_at->addDays($order->delivery_days ?? 3)->format('Y-m-d'),
                ];
            });

        // 2. Fetch Listed Gigs (As Seller)
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

        // 3. Compute Real Stats
        $stats = [
            // Escrow locked hold could be sum of active orders where user is buyer or seller
            'lockedEscrow' => ServiceOrder::where('seller_id', $user->id)->where('status', 'in_progress')->sum('total_price'),
            'activeOrders' => ServiceOrder::where('seller_id', $user->id)->where('status', 'in_progress')->count(),
            'servicesListed' => Service::where('seller_id', $user->id)->count(),
            'totalSales' => ServiceOrder::where('seller_id', $user->id)->where('status', 'completed')->sum('total_price'),
        ];

        return Inertia::render('Marketplace/Dashboard', [
            'activePurchases' => $activePurchases,
            'listedGigs' => $listedGigs,
            'stats' => $stats
        ]);
    }
}
