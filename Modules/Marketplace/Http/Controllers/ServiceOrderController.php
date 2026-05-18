<?php

namespace Modules\Marketplace\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Core\Services\FinancialTransactionService;
use Modules\Core\Models\Wallet;
use Modules\Core\Models\Conversation;
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

        $buyerWallet = Wallet::firstOrCreate(
            ['owner_type' => \App\Models\User::class, 'owner_id' => $buyer->id],
            ['context' => 'user', 'balance' => 10000.00, 'currency' => 'USD']
        );

        $sellerWallet = Wallet::firstOrCreate(
            ['owner_type' => \App\Models\User::class, 'owner_id' => $seller_id],
            ['context' => 'user', 'balance' => 0.00, 'currency' => 'USD']
        );

        if ($buyerWallet->balance < $package->price) {
            return redirect()->back()->withErrors(['error' => 'Insufficient wallet balance.']);
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

            // Full deduction from Buyer
            $newBuyerBalance = $buyerWallet->balance - $package->price;
            DB::table('wallet_transactions')->insert([
                'wallet_id' => $buyerWallet->id,
                'type' => 'debit',
                'amount' => $package->price,
                'balance_before' => $buyerWallet->balance,
                'balance_after' => $newBuyerBalance,
                'reference_type' => ServiceOrder::class,
                'reference_id' => (string)$order->id,
                'description' => "Payment for service order #{$order->id}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('wallets')->where('id', $buyerWallet->id)->update(['balance' => $newBuyerBalance, 'updated_at' => now()]);

            // Credit to Seller (Price - Commission)
            $newSellerBalance = $sellerWallet->balance + $sellerCredit;
            DB::table('wallet_transactions')->insert([
                'wallet_id' => $sellerWallet->id,
                'type' => 'credit',
                'amount' => $sellerCredit,
                'balance_before' => $sellerWallet->balance,
                'balance_after' => $newSellerBalance,
                'reference_type' => ServiceOrder::class,
                'reference_id' => (string)$order->id,
                'description' => "Earnings from service order #{$order->id}",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('wallets')->where('id', $sellerWallet->id)->update(['balance' => $newSellerBalance, 'updated_at' => now()]);

            // The platform keeps the commission implicitly, or we could credit a specific "Platform Wallet" if it existed.

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

    public function deliver(ServiceOrder $order)
    {
        if (auth()->id() !== $order->seller_id) {
            abort(403);
        }

        $order->update([
            'status' => 'delivered',
            'delivered_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Order marked as delivered.');
    }

    public function complete(ServiceOrder $order)
    {
        if (auth()->id() !== $order->buyer_id) {
            abort(403);
        }

        $order->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

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
