<?php

namespace App\Services;

use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Services\EscrowService;
use Modules\Marketplace\Enums\EscrowStatus;
use Illuminate\Support\Facades\DB;

class MarketplaceOrderService extends BaseService
{
    public function __construct(
        protected EscrowService $escrowService
    ) {}

    public function resolveDispute(ServiceOrder $order, string $action, ?string $reason = null): void
    {
        if (in_array($order->status, ['completed', 'cancelled'])) {
            throw new \Exception('Order is already closed.');
        }

        $escrow = MarketplaceEscrow::where('order_id', $order->id)
            ->whereIn('status', [EscrowStatus::HELD->value, EscrowStatus::DISPUTED->value, 'held', 'disputed'])
            ->first();

        DB::transaction(function () use ($order, $escrow, $action, $reason) {
            $timestamp = now('Africa/Cairo')->toDateTimeString();
            $actionTitle = $action === 'refund_buyer' ? 'Refunded to Buyer' : 'Released to Seller';
            $resolutionNote = "[{$timestamp}] Admin Dispute Resolution: {$actionTitle}";

            if ($reason) {
                $resolutionNote .= " - Reason: {$reason}";
            }

            $order->notes = trim(($order->notes ? $order->notes . "\n" : '') . $resolutionNote);

            if ($action === 'refund_buyer') {
                $order->status = 'cancelled';
                $order->save();

                if ($escrow) {
                    $this->escrowService->refundFunds($escrow);
                }
            } elseif ($action === 'release_to_seller') {
                $order->status = 'completed';
                $order->completed_at = now('Africa/Cairo');
                $order->save();

                if ($escrow) {
                    $this->escrowService->releaseFunds($escrow);
                }
            }
        });
    }
}
