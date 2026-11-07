<?php

namespace Modules\Marketplace\Services;

use App\Models\User;
use App\Notifications\OrderCancellationRequestedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Models\MarketplaceEscrow;
use Modules\Marketplace\Models\OrderStatusHistory;
use Modules\Marketplace\Models\ServiceOrder;

/**
 * Mutual cancellation between buyer and seller. Either side asks and the other accepts or declines.
 * With no answer in time the order is cancelled and the buyer is refunded.
 */
class OrderCancellation
{
    public const RESPONSE_DAYS = 3;

    private const DELIVERY_WINDOW_DAYS = 3;

    private const OPEN_STATUSES = [
        ServiceOrderStatus::PENDING,
        ServiceOrderStatus::PROCESSING,
        ServiceOrderStatus::IN_PROGRESS,
        ServiceOrderStatus::DELIVERED,
        ServiceOrderStatus::REVISION,
    ];

    public function __construct(
        private EscrowService $escrowService,
        private OrderChat $chat,
    ) {}

    public function canRequest(ServiceOrder $order): bool
    {
        return in_array($order->status, self::OPEN_STATUSES, true);
    }

    /** The open request, or null when the order has none. */
    public function pendingRequest(ServiceOrder $order): ?OrderStatusHistory
    {
        if ($order->status !== ServiceOrderStatus::CANCELLATION_REQUESTED) {
            return null;
        }

        return OrderStatusHistory::where('order_id', $order->id)
            ->where('new_status', ServiceOrderStatus::CANCELLATION_REQUESTED->value)
            ->latest('id')
            ->first();
    }

    /** Only the side that did not ask can answer. */
    public function canRespond(ServiceOrder $order, User $user): bool
    {
        $request = $this->pendingRequest($order);
        $isParty = in_array($user->id, [(int) $order->buyer_id, (int) $order->seller_id], true);

        return $request && $isParty && (int) $request->changed_by !== $user->id;
    }

    public function request(ServiceOrder $order, User $requester, string $reason): void
    {
        DB::transaction(function () use ($order, $requester, $reason) {
            $oldStatus = $order->status->value;
            $order->update(['status' => ServiceOrderStatus::CANCELLATION_REQUESTED]);
            $this->record($order, $oldStatus, ServiceOrderStatus::CANCELLATION_REQUESTED, $requester->id, $reason);
            $this->chat->post($order, $requester->id, __('marketplace.order_cancel_requested_message', ['reason' => $reason]));
        });

        $this->notifyOtherSide($order, $requester, $reason);
    }

    /** Cancels the order and returns the money to the buyer. */
    public function accept(ServiceOrder $order, int $actorId, string $chatMessageKey = 'marketplace.order_cancel_accepted_message'): void
    {
        DB::transaction(function () use ($order, $actorId, $chatMessageKey) {
            $locked = ServiceOrder::whereKey($order->id)->lockForUpdate()->first();
            if ($locked->status !== ServiceOrderStatus::CANCELLATION_REQUESTED) {
                return;
            }

            $locked->update(['status' => ServiceOrderStatus::CANCELLED]);
            $this->record($locked, ServiceOrderStatus::CANCELLATION_REQUESTED->value, ServiceOrderStatus::CANCELLED, $actorId);

            $escrow = MarketplaceEscrow::where('order_id', $locked->id)->first();
            if ($escrow) {
                $this->escrowService->refundFunds($escrow);
            }

            $this->chat->post($locked, $actorId, __($chatMessageKey));
        });
    }

    /** Puts the order back where it was. A delivered order gets a fresh review window. */
    public function decline(ServiceOrder $order, User $responder): void
    {
        $previous = ServiceOrderStatus::from($this->pendingRequest($order)->old_status);

        DB::transaction(function () use ($order, $responder, $previous) {
            $order->update(['status' => $previous] + ($previous === ServiceOrderStatus::DELIVERED
                ? ['auto_complete_at' => now('Africa/Cairo')->addDays(self::DELIVERY_WINDOW_DAYS)]
                : []));
            $this->record($order, ServiceOrderStatus::CANCELLATION_REQUESTED->value, $previous, $responder->id);
            $this->chat->post($order, $responder->id, __('marketplace.order_cancel_declined_message'));
        });
    }

    /** Requests nobody answered in time are accepted. Returns how many were cancelled. */
    public function acceptExpiredRequests(): int
    {
        $orders = ServiceOrder::where('status', ServiceOrderStatus::CANCELLATION_REQUESTED->value)->get();
        $cancelled = 0;

        foreach ($orders as $order) {
            $request = $this->pendingRequest($order);
            if ($request && $request->created_at->lte(now()->subDays(self::RESPONSE_DAYS))) {
                $this->accept($order, (int) $request->changed_by, 'marketplace.order_cancel_expired_message');
                $cancelled++;
            }
        }

        return $cancelled;
    }

    private function record(ServiceOrder $order, string $from, ServiceOrderStatus $to, int $changedBy, ?string $note = null): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'old_status' => $from,
            'new_status' => $to->value,
            'changed_by' => $changedBy,
            'note' => $note,
        ]);
    }

    /** The request is already saved, so a mail problem is logged and does not undo it. */
    private function notifyOtherSide(ServiceOrder $order, User $requester, string $reason): void
    {
        try {
            $other = $requester->id === (int) $order->buyer_id ? $order->seller : $order->buyer;
            $other?->notify(new OrderCancellationRequestedNotification($order, $reason));
        } catch (\Throwable $e) {
            Log::error('marketplace.order.cancel_notice_failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }
}
