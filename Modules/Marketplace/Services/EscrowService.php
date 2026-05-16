<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Models\Order;
use Modules\Core\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Exception;

class EscrowService
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function createHold(Order $order, float $amount, string $currency): MarketplaceEscrow
    {
        return DB::transaction(function () use ($order, $amount, $currency) {
            // Check if user has wallet, etc. Assuming buyer has wallet linked.
            // Simplified logic: locking funds from buyer's wallet

            $buyerWallet = $order->buyer->wallets()->firstOrCreate(['currency' => $currency]); // Assuming wallets exist

            $this->walletService->lockFunds(
                $buyerWallet,
                $amount,
                $currency,
                'marketplace_escrow',
                $order->id,
                "Escrow hold for order #{$order->id}"
            );

            $escrow = MarketplaceEscrow::create([
                'order_id' => $order->id,
                'amount' => $amount,
                'amount_currency' => $currency,
                'business_amount' => $amount, // Simplify exchange logic here for now
                'business_currency' => $currency,
                'exchange_rate' => 1.0,
                'exchange_rate_date' => now(),
                'status' => 'held'
            ]);

            return $escrow;
        });
    }

    public function release(MarketplaceEscrow $escrow, float $commissionAmount = 0): void
    {
        DB::transaction(function () use ($escrow, $commissionAmount) {
            if ($escrow->status !== 'held') {
                throw new Exception("Escrow is not held.");
            }

            $order = $escrow->order;
            $buyerWallet = $order->buyer->wallets()->first();
            $sellerWallet = $order->seller->wallets()->firstOrCreate(['currency' => $escrow->amount_currency]);

            // Transfer locked to spent from buyer
            $buyerTx = $this->walletService->transferLockedToSpent(
                $buyerWallet,
                $escrow->amount,
                $escrow->amount_currency,
                'marketplace_escrow_release',
                $order->id,
                "Release escrow for order #{$order->id}"
            );

            $sellerAmount = $escrow->amount - $commissionAmount;

            // Credit seller
            $sellerTx = $this->walletService->creditAvailable(
                $sellerWallet,
                $sellerAmount,
                $escrow->amount_currency,
                'marketplace_order_earning',
                $order->id,
                "Earnings for order #{$order->id}"
            );

            // Deduct commission to system? Not explicit here but typical.

            $escrow->update([
                'status' => 'released',
                'buyer_wallet_transaction_id' => $buyerTx->id,
                'seller_wallet_transaction_id' => $sellerTx->id,
                'released_at' => now(),
            ]);
        });
    }

    public function refund(MarketplaceEscrow $escrow): void
    {
        DB::transaction(function () use ($escrow) {
            if (!in_array($escrow->status, ['held', 'disputed'])) {
                throw new Exception("Cannot refund escrow in current state.");
            }

            $order = $escrow->order;
            $buyerWallet = $order->buyer->wallets()->first();

            $this->walletService->unlockFunds(
                $buyerWallet,
                $escrow->amount,
                $escrow->amount_currency,
                'marketplace_escrow_refund',
                $order->id,
                "Refund escrow for order #{$order->id}"
            );

            $escrow->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);
        });
    }

    public function dispute(MarketplaceEscrow $escrow): void
    {
        DB::transaction(function () use ($escrow) {
            if ($escrow->status !== 'held') {
                throw new Exception("Only held escrows can be disputed.");
            }

            $escrow->update([
                'status' => 'disputed'
            ]);
        });
    }
}
