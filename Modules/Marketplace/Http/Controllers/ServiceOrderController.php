<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Services\FinancialTransactionService;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Events\MarketplaceOrderPlaced;
use App\Events\MarketplaceOrderCompleted;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Services\EscrowService;

class ServiceOrderController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $tab = $request->query('tab', 'purchases');

        if ($tab === 'sales') {
            $orders = ServiceOrder::with(['buyer', 'package.service'])->where('seller_id', $user->id)->latest()->paginate(15);
        } else {
            $orders = ServiceOrder::with(['seller', 'package.service'])->where('buyer_id', $user->id)->latest()->paginate(15);
        }

        return Inertia::render('Marketplace/Orders/Index', [
            'orders' => $orders,
            'tab' => $tab
        ]);
    }

    public function show(ServiceOrder $order)
    {
        // Authorize user (buyer or seller)
        if (auth()->id() !== $order->buyer_id && auth()->id() !== $order->seller_id) {
            abort(403);
        }

        $order->load(['buyer', 'seller', 'package.service']);

        $conversation = Conversation::with(['messages.sender', 'participants'])
            ->where('conversable_type', ServiceOrder::class)
            ->where('conversable_id', $order->id)
            ->first();

        return Inertia::render('Marketplace/Orders/Show', [
            'order' => $order,
            'conversation' => $conversation
        ]);
    }

    public function store(Request $request, FinancialTransactionService $financialService, EscrowService $escrowService)
    {
        $validated = $request->validate([
            'package_id' => 'required|exists:marketplace_packages,id',
        ]);

        $package = ServicePackage::with('service')->findOrFail($validated['package_id']);
        $buyer = auth()->user();
        $seller_id = $package->service->seller_id;

        if ($buyer->id === $seller_id) {
            return redirect()->back()->withErrors(['error' => 'You cannot purchase your own service.']);
        }

        if ($buyer->user_balance < $package->price) {
            return redirect()->back()->withErrors(['error' => 'Insufficient balance.']);
        }

        // Commission logic
        $commissionRate = config('marketplace.commission_rate', 0.10);
        $commissionAmount = $package->price * $commissionRate;

        DB::beginTransaction();

        try {
            // Create Order
            $order = ServiceOrder::create([
                'buyer_id' => $buyer->id,
                'seller_id' => $seller_id,
                'package_id' => $package->id,
                'amount' => $package->price,
                'currency_id' => $package->currency_id,
                'commission_amount' => $commissionAmount,
                'status' => ServiceOrderStatus::PENDING
            ]);

            // Delegate escrow creation and lock to the EscrowService
            $escrowService->holdFunds($order);

            // Create Conversation
            $conversation = Conversation::create([
                'conversable_type' => ServiceOrder::class,
                'conversable_id' => $order->id,
                'type' => 'marketplace_order',
                'status' => 'open'
            ]);

            $conversation->participants()->createMany([
                ['user_id' => $buyer->id, 'role' => 'buyer'],
                ['user_id' => $seller_id, 'role' => 'seller'],
            ]);

            DB::commit();

            event(new MarketplaceOrderPlaced($order));

            return redirect()->route('marketplace.orders.show', $order->id)->with('success', __('general.order_placed_successfully'));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Order failed: ' . $e->getMessage()]);
        }
    }

    public function deliver(Request $request, ServiceOrder $order)
    {
        $this->authorize('deliver', $order);

        $validated = $request->validate([
            'message' => 'nullable|string',
            'links' => 'nullable|string'
        ]);

        $payload = [
            'message' => $validated['message'] ?? '',
            'links' => $validated['links'] ?? ''
        ];

        $order->update([
            'status' => ServiceOrderStatus::DELIVERED,
            'delivered_at' => now(),
            'auto_complete_at' => now()->addDays(3),
            'delivery_payload' => $payload
        ]);

        return redirect()->back()->with('success', __('general.order_marked_as_delivered'));
    }

    public function complete(ServiceOrder $order, EscrowService $escrowService)
    {
        $this->authorize('complete', $order);

        if ($order->status === ServiceOrderStatus::COMPLETED) {
            return redirect()->back()->with('error', __('general.order_already_completed'));
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => ServiceOrderStatus::COMPLETED,
                'completed_at' => now(),
            ]);

            $escrow = \Modules\Marketplace\Models\MarketplaceEscrow::where('order_id', $order->id)->first();
            if ($escrow) {
                $escrowService->releaseFunds($escrow);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to complete order: ' . $e->getMessage()]);
        }

        event(new MarketplaceOrderCompleted($order));

        return redirect()->back()->with('success', __('general.order_completed'));
    }

    public function dispute(ServiceOrder $order, EscrowService $escrowService)
    {
        $this->authorize('dispute', $order);

        $order->update([
            'status' => ServiceOrderStatus::DISPUTED,
        ]);
        
        $escrow = \Modules\Marketplace\Models\MarketplaceEscrow::where('order_id', $order->id)->first();
        if ($escrow) {
            $escrowService->dispute($escrow);
        }

        return redirect()->back()->with('success', __('general.order_is_now_in_dispute'));
    }
}

