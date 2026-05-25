<?php

namespace App\Services;

use Modules\Marketplace\Models\ServiceOrder;

class MarketplaceOrderService
{
    public function resolveDispute(ServiceOrder $order, string $action): void
    {
        if ($order->status === 'completed' || $order->status === 'cancelled') {
            throw new \Exception('Order is already closed.');
        }

        if ($action === 'refund_buyer') {
            $order->status = 'cancelled';
            $order->save();
            // Integration with wallet refund should be placed here
        } elseif ($action === 'release_to_seller') {
            $order->status = 'completed';
            $order->completed_at = now();
            $order->save();
            // Integration with wallet release should be placed here
        }
    }
}
