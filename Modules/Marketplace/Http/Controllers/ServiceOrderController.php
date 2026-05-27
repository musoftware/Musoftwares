<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServicePackage;
use App\Services\FinancialTransactionService;
use App\Models\Conversation;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use App\Events\MarketplaceOrderPlaced;
use App\Events\MarketplaceOrderCompleted;

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

    public function store(Request $request, FinancialTransactionService $financialService)
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

        // Commission logic (e.g., 10%)
        $commissionRate = 0.10;
        $commissionAmount = $package->price * $commissionRate;
        $sellerCredit = $package->price - $commissionAmount;

        DB::beginTransaction();

        try {
            // Create Order
            $order = ServiceOrder::create([
                'buyer_id' => $buyer->id,
                'seller_id' => $seller_id,
                'package_id' => $package->id,
                'amount' => $package->price,
                'currency_code' => $package->currency_code,
                'commission_amount' => $commissionAmount,
                'status' => 'pending'
            ]);

            // Escrow Lock: Deduct from Buyer balance using type 'used'
            $transactionId = $buyer->add_balance(-$package->price, "Escrow lock for service order #{$order->id}", 'used', $package->currency_code);

            // Create Escrow record
            \Modules\Marketplace\Models\MarketplaceEscrow::create([
                'order_id' => $order->id,
                'buyer_wallet_transaction_id' => $transactionId,
                'amount' => $package->price,
                'amount_currency' => $package->currency_code,
                'status' => 'held'
            ]);

            // Seller does NOT get credited yet. Funds are in Escrow.

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

            return redirect()->route('marketplace.orders.show', $order->id)->with('success', 'Order placed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Order failed: ' . $e->getMessage()]);
        }
    }

    public function deliver(Request $request, ServiceOrder $order)
    {
        if (auth()->id() !== $order->seller_id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'nullable|string',
            'links' => 'nullable|string'
        ]);

        $payload = [
            'message' => $validated['message'] ?? '',
            'links' => $validated['links'] ?? ''
        ];

        $order->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'auto_complete_at' => now()->addDays(3),
            'delivery_payload' => $payload
        ]);

        return redirect()->back()->with('success', 'Order marked as delivered.');
    }

    public function complete(ServiceOrder $order)
    {
        if (auth()->id() !== $order->buyer_id) {
            abort(403);
        }

        if ($order->status === 'completed') {
            return redirect()->back()->with('error', 'Order already completed.');
        }

        DB::beginTransaction();
        try {
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            // Release Escrow: Credit Seller balance
            $seller = \App\Models\User::findOrFail($order->seller_id);
            $sellerCredit = $order->amount - $order->commission_amount;

            $transactionId = $seller->add_balance($sellerCredit, "Earnings from service order #{$order->id} (Escrow Released)", 'received', $order->currency_code);

            // Update Escrow Record
            $escrow = \Modules\Marketplace\Models\MarketplaceEscrow::where('order_id', $order->id)->first();
            if ($escrow) {
                $escrow->update([
                    'status' => 'released',
                    'seller_wallet_transaction_id' => $transactionId,
                    'released_at' => now(),
                ]);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Failed to complete order: ' . $e->getMessage()]);
        }

        event(new MarketplaceOrderCompleted($order));

        return redirect()->back()->with('success', 'Order completed.');
    }

    public function dispute(ServiceOrder $order)
    {
        // Allow either buyer or seller to dispute
        if (auth()->id() !== $order->buyer_id && auth()->id() !== $order->seller_id) {
            abort(403);
        }

        $order->update([
            'status' => 'disputed',
        ]);

        return redirect()->back()->with('success', 'Order is now in dispute.');
    }
}

