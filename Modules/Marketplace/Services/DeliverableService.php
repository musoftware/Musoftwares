<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Models\OrderDeliveryFile;
use Modules\Marketplace\Enums\ServiceOrderStatus;
use Modules\Marketplace\Services\EscrowService;
use Illuminate\Support\Facades\DB;
use Exception;

class DeliverableService
{
    /**
     * Submit work deliverable with note and file attachment.
     */
    public function submitDeliverable(ServiceOrder $order, string $note, ?string $filePath = null): ServiceOrder
    {
        if ($order->status !== ServiceOrderStatus::PENDING && $order->status !== ServiceOrderStatus::PROCESSING) {
            throw new Exception("Order cannot receive work submission in current status: {$order->status->value}");
        }

        DB::transaction(function () use ($order, $note, $filePath) {
            if ($filePath) {
                OrderDeliveryFile::create([
                    'order_id' => $order->id,
                    'file_path' => $filePath,
                    'note' => $note,
                    'created_at' => now('Africa/Cairo'),
                ]);
            }

            $order->update([
                'status' => ServiceOrderStatus::DELIVERED,
                'delivered_at' => now('Africa/Cairo'),
                'auto_complete_at' => now('Africa/Cairo')->addDays(3),
                'delivery_payload' => [
                    'message' => $note,
                    'file_path' => $filePath,
                ],
            ]);
        });

        return $order->refresh();
    }

    /**
     * Buyer requests a revision on delivered work.
     */
    public function requestRevision(ServiceOrder $order, string $revisionNote): ServiceOrder
    {
        if ($order->status !== ServiceOrderStatus::DELIVERED) {
            throw new Exception("Revisions can only be requested for delivered orders.");
        }

        $order->update([
            'status' => ServiceOrderStatus::PROCESSING,
            'revision_requested_at' => now('Africa/Cairo'),
            'delivery_payload' => array_merge($order->delivery_payload ?? [], [
                'latest_revision_note' => $revisionNote,
            ]),
        ]);

        return $order->refresh();
    }
}
