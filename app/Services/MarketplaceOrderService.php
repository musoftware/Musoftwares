<?php

namespace App\Services;

use App\Models\User;
use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Models\ServiceOrder;

class MarketplaceOrderService extends BaseService
{
    public function resolveDispute(ServiceOrder $order, string $action): void
    {
        if ($order->status === 'completed' || $order->status === 'cancelled') {
            throw new \Exception('Order is already closed.');
        }

        $escrow = MarketplaceEscrow::where('order_id', $order->id)->where('status', 'held')->first();

        if ($action === 'refund_buyer') {
            $order->status = 'cancelled';
            $order->save();

            if ($escrow) {
                $buyer = User::find($order->buyer_id);
                if ($buyer) {
                    if (! $escrow->currency_id) {
                        throw new \Exception(__('errors.escrow_currency_not_found'));
                    }
                    $transactionId = $buyer->add_balance($escrow->amount, "Refund for cancelled service order #{$order->id} (Dispute)", 'refunded', $escrow->currency_id);

                    $escrow->update([
                        'status' => 'refunded',
                        'buyer_wallet_transaction_id' => $transactionId, // overwriting or just keeping record
                        'refunded_at' => now(),
                    ]);
                }
            }
        } elseif ($action === 'release_to_seller') {
            $order->status = 'completed';
            $order->completed_at = now();
            $order->save();

            if ($escrow) {
                $seller = User::find($order->seller_id);
                if ($seller) {
                    $sellerCredit = $order->amount - $order->commission_amount;
                    $transactionId = $seller->add_balance($sellerCredit, "Earnings from service order #{$order->id} (Dispute Resolved)", 'received', $order->currency_id);

                    $escrow->update([
                        'status' => 'released',
                        'seller_wallet_transaction_id' => $transactionId,
                        'released_at' => now(),
                    ]);
                }
            }
        }
    }
}
