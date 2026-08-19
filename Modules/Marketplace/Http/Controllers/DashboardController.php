<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\OrderStatusHistory;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // ─────────────────────────────────────────────────────────────────
        // 1. BUYER PERSPECTIVE DATA
        // ─────────────────────────────────────────────────────────────────

        // Needs Action (Buyer): Delivered orders waiting review, or pending requirements
        $needsActionPurchases = ServiceOrder::where('buyer_id', $user->id)
            ->whereIn('status', [
                ServiceOrderStatus::DELIVERED,
                ServiceOrderStatus::PENDING_REQUIREMENTS,
            ])
            ->with(['package.service', 'seller'])
            ->latest()
            ->get()
            ->map(function($order) {
                $statusVal = $order->status instanceof ServiceOrderStatus ? $order->status->value : (string)$order->status;
                $actionText = $statusVal === 'delivered' 
                    ? 'Review & Approve Deliverable' 
                    : 'Submit Order Requirements';

                return [
                    'id' => $order->id,
                    'title' => $order->package->service->title ?? 'Custom Service Order',
                    'sellerName' => $order->seller->name ?? 'Service Seller',
                    'sellerAvatar' => $order->seller->avatar ?? null,
                    'amount' => (float) $order->amount,
                    'status' => $statusVal,
                    'actionNeededText' => $actionText,
                    'deliveryDate' => $order->delivered_at 
                        ? $order->delivered_at->setTimezone('Africa/Cairo')->format('Y-m-d H:i')
                        : ($order->created_at ? $order->created_at->setTimezone('Africa/Cairo')->addDays($order->package->delivery_days ?? 3)->format('Y-m-d') : null),
                ];
            });

        // Active Orders (Buyer): In progress, processing, revision
        $activePurchases = ServiceOrder::where('buyer_id', $user->id)
            ->whereIn('status', [
                ServiceOrderStatus::PENDING,
                ServiceOrderStatus::PROCESSING,
                ServiceOrderStatus::IN_PROGRESS,
                ServiceOrderStatus::REVISION,
            ])
            ->with(['package.service', 'seller'])
            ->latest()
            ->get()
            ->map(function($order) {
                $statusVal = $order->status instanceof ServiceOrderStatus ? $order->status->value : (string)$order->status;
                return [
                    'id' => $order->id,
                    'title' => $order->package->service->title ?? 'Custom Service Order',
                    'sellerName' => $order->seller->name ?? 'Service Seller',
                    'sellerAvatar' => $order->seller->avatar ?? null,
                    'amount' => (float) $order->amount,
                    'status' => $statusVal,
                    'deliveryDate' => $order->created_at 
                        ? $order->created_at->setTimezone('Africa/Cairo')->addDays($order->package->delivery_days ?? 3)->format('Y-m-d')
                        : null,
                ];
            });

        // Buyer Recent Activity Stream
        $buyerActivity = OrderStatusHistory::whereHas('order', function($q) use ($user) {
                $q->where('buyer_id', $user->id);
            })
            ->with(['order.package.service', 'changedBy'])
            ->latest()
            ->take(6)
            ->get()
            ->map(function($history) {
                return [
                    'id' => $history->id,
                    'orderId' => $history->order_id,
                    'serviceTitle' => $history->order->package->service->title ?? 'Service Order',
                    'oldStatus' => $history->old_status,
                    'newStatus' => $history->new_status,
                    'changedByName' => $history->changedBy->name ?? 'System',
                    'note' => $history->note,
                    'timestamp' => $history->created_at ? $history->created_at->setTimezone('Africa/Cairo')->diffForHumans() : '',
                ];
            });

        // Buyer Stats
        $buyerOrdersStats = ServiceOrder::where('buyer_id', $user->id)
            ->select(
                DB::raw("SUM(CASE WHEN status IN ('" . ServiceOrderStatus::PROCESSING->value . "', '" . ServiceOrderStatus::IN_PROGRESS->value . "', '" . ServiceOrderStatus::DELIVERED->value . "') THEN amount ELSE 0 END) as locked_escrow"),
                DB::raw("SUM(CASE WHEN status IN ('" . ServiceOrderStatus::PENDING->value . "', '" . ServiceOrderStatus::PROCESSING->value . "', '" . ServiceOrderStatus::IN_PROGRESS->value . "', '" . ServiceOrderStatus::REVISION->value . "') THEN 1 ELSE 0 END) as active_orders"),
                DB::raw("SUM(CASE WHEN status IN ('" . ServiceOrderStatus::COMPLETED->value . "', '" . ServiceOrderStatus::AUTO_COMPLETED->value . "') THEN amount ELSE 0 END) as total_spent"),
                DB::raw("SUM(CASE WHEN status IN ('" . ServiceOrderStatus::COMPLETED->value . "', '" . ServiceOrderStatus::AUTO_COMPLETED->value . "') THEN 1 ELSE 0 END) as completed_count")
            )->first();

        $buyerStats = [
            'lockedEscrow' => (float) ($buyerOrdersStats->locked_escrow ?? 0),
            'activeOrders' => (int) ($buyerOrdersStats->active_orders ?? 0),
            'totalSpent' => (float) ($buyerOrdersStats->total_spent ?? 0),
            'completedCount' => (int) ($buyerOrdersStats->completed_count ?? 0),
        ];

        // ─────────────────────────────────────────────────────────────────
        // 2. SELLER PERSPECTIVE DATA
        // ─────────────────────────────────────────────────────────────────

        // Orders Need Attention (Seller): Revision requested or due for delivery
        $needsActionSales = ServiceOrder::where('seller_id', $user->id)
            ->whereIn('status', [
                ServiceOrderStatus::REVISION,
                ServiceOrderStatus::PROCESSING,
                ServiceOrderStatus::IN_PROGRESS,
            ])
            ->with(['package.service', 'buyer'])
            ->latest()
            ->get()
            ->map(function($order) {
                $statusVal = $order->status instanceof ServiceOrderStatus ? $order->status->value : (string)$order->status;
                $actionText = $statusVal === 'revision' 
                    ? 'Client Requested Revision' 
                    : 'Work Submission Due';

                return [
                    'id' => $order->id,
                    'title' => $order->package->service->title ?? 'Service Order',
                    'buyerName' => $order->buyer->name ?? 'Client',
                    'buyerAvatar' => $order->buyer->avatar ?? null,
                    'amount' => (float) $order->amount,
                    'status' => $statusVal,
                    'actionNeededText' => $actionText,
                    'deliveryDate' => $order->created_at 
                        ? $order->created_at->setTimezone('Africa/Cairo')->addDays($order->package->delivery_days ?? 3)->format('Y-m-d')
                        : null,
                ];
            });

        // Active Orders (Seller): Active sales queue
        $activeSales = ServiceOrder::where('seller_id', $user->id)
            ->whereIn('status', [
                ServiceOrderStatus::PENDING,
                ServiceOrderStatus::PROCESSING,
                ServiceOrderStatus::IN_PROGRESS,
                ServiceOrderStatus::REVISION,
                ServiceOrderStatus::DELIVERED,
            ])
            ->with(['package.service', 'buyer'])
            ->latest()
            ->get()
            ->map(function($order) {
                $statusVal = $order->status instanceof ServiceOrderStatus ? $order->status->value : (string)$order->status;
                return [
                    'id' => $order->id,
                    'title' => $order->package->service->title ?? 'Service Order',
                    'buyerName' => $order->buyer->name ?? 'Client',
                    'buyerAvatar' => $order->buyer->avatar ?? null,
                    'amount' => (float) $order->amount,
                    'status' => $statusVal,
                    'deliveryDate' => $order->created_at 
                        ? $order->created_at->setTimezone('Africa/Cairo')->addDays($order->package->delivery_days ?? 3)->format('Y-m-d')
                        : null,
                ];
            });

        // Listed Services / Gigs (Seller)
        $listedGigs = Service::where('seller_id', $user->id)
            ->withMin('packages', 'price')
            ->latest()
            ->get()
            ->map(function($service) {
                return [
                    'id' => $service->id,
                    'title' => $service->title,
                    'status' => $service->status,
                    'price' => (float) ($service->packages_min_price ?? ($service->is_free ? 0 : 5)),
                    'reviews' => (int) $service->review_count,
                    'rating' => (float) $service->avg_rating,
                ];
            });

        // Seller Stats
        $sellerOrdersStats = ServiceOrder::where('seller_id', $user->id)
            ->select(
                DB::raw("SUM(CASE WHEN status IN ('" . ServiceOrderStatus::PROCESSING->value . "', '" . ServiceOrderStatus::IN_PROGRESS->value . "', '" . ServiceOrderStatus::DELIVERED->value . "') THEN amount ELSE 0 END) as locked_escrow"),
                DB::raw("SUM(CASE WHEN status IN ('" . ServiceOrderStatus::PENDING->value . "', '" . ServiceOrderStatus::PROCESSING->value . "', '" . ServiceOrderStatus::IN_PROGRESS->value . "', '" . ServiceOrderStatus::REVISION->value . "') THEN 1 ELSE 0 END) as active_orders"),
                DB::raw("SUM(CASE WHEN status IN ('" . ServiceOrderStatus::COMPLETED->value . "', '" . ServiceOrderStatus::AUTO_COMPLETED->value . "') THEN amount ELSE 0 END) as total_sales"),
                DB::raw("SUM(CASE WHEN status IN ('" . ServiceOrderStatus::COMPLETED->value . "', '" . ServiceOrderStatus::AUTO_COMPLETED->value . "') THEN 1 ELSE 0 END) as completed_orders"),
                DB::raw("SUM(CASE WHEN status = '" . ServiceOrderStatus::CANCELLED->value . "' THEN 1 ELSE 0 END) as cancelled_orders")
            )->first();

        $totalOrdersCount = ($sellerOrdersStats->completed_orders ?? 0) + ($sellerOrdersStats->cancelled_orders ?? 0);
        $completionRate = $totalOrdersCount > 0 
            ? round((($sellerOrdersStats->completed_orders ?? 0) / $totalOrdersCount) * 100) 
            : 100;

        $sellerStats = [
            'lockedEscrow' => (float) ($sellerOrdersStats->locked_escrow ?? 0),
            'activeOrders' => (int) ($sellerOrdersStats->active_orders ?? 0),
            'servicesListed' => Service::where('seller_id', $user->id)->count(),
            'totalSales' => (float) ($sellerOrdersStats->total_sales ?? 0),
            'completedOrders' => (int) ($sellerOrdersStats->completed_orders ?? 0),
            'completionRate' => $completionRate,
        ];

        return view('marketplace::dashboard.index', compact(
            'needsActionPurchases',
            'activePurchases',
            'buyerActivity',
            'buyerStats',
            'needsActionSales',
            'activeSales',
            'listedGigs',
            'sellerStats'
        ));
    }
}
