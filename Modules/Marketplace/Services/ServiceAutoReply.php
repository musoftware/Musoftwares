<?php

namespace Modules\Marketplace\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Models\OrderStatusHistory;
use Modules\Marketplace\Models\Service;
use Modules\Marketplace\Models\ServiceOrder;

/**
 * Runs right after a paid order is created. Sends the service's auto reply into the order chat and,
 * when the service asks for it, delivers the order at once. The normal hourly command then completes it.
 */
class ServiceAutoReply
{
    private const AUTO_COMPLETE_DAYS = 3;

    /** The buyer has already paid, so a failure here is logged and never cancels the purchase. */
    public function handle(ServiceOrder $order): void
    {
        try {
            $service = $order->package?->service;
            $text = $service ? $this->textFor($service) : '';
            if ($text === '') {
                return;
            }

            DB::transaction(function () use ($order, $service, $text) {
                app(OrderChat::class)->post($order, (int) $order->seller_id, $text);
                if ($service->auto_complete && $order->status === ServiceOrderStatus::PENDING) {
                    $this->deliver($order, $text);
                }
            });
        } catch (\Throwable $e) {
            Log::error('marketplace.auto_reply.failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
        }
    }

    /** The reply in the buyer's language, or whichever language was written. */
    private function textFor(Service $service): string
    {
        $translations = $service->auto_reply_translations ?? [];

        return trim((string) (($translations[app()->getLocale()] ?? null) ?: $service->auto_reply));
    }

    private function deliver(ServiceOrder $order, string $text): void
    {
        $order->update([
            'status' => ServiceOrderStatus::DELIVERED,
            'delivered_at' => now('Africa/Cairo'),
            'auto_complete_at' => now('Africa/Cairo')->addDays(self::AUTO_COMPLETE_DAYS),
            'delivery_payload' => ['message' => $text, 'links' => ''],
        ]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'old_status' => ServiceOrderStatus::PENDING->value,
            'new_status' => ServiceOrderStatus::DELIVERED->value,
            'changed_by' => $order->seller_id,
            'note' => 'Delivered automatically with the service auto reply.',
        ]);
    }
}
