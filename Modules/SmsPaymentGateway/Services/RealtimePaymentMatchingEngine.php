<?php

namespace Modules\SmsPaymentGateway\Services;

use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayPaymentOrder;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayOrderLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Event;

/**
 * Real-Time Deterministic Payment Matching Engine
 */
class RealtimePaymentMatchingEngine
{
    /**
     * @var float The allowed variance between the order amount and transaction amount
     */
    protected float $amountTolerance = 1.00; // Allow 1 EGP difference

    /**
     * @var int Hours to look back for an active order
     */
    protected int $timeWindowHours = 24;

    /**
     * Main Entry Point: Attempt to match an incoming transaction to an unpaid order.
     */
    public function matchTransaction(SmsPaymentGatewayTransaction $transaction): bool
    {
        // 1. Prevent Transaction Replay/Duplicate Matching
        if ($transaction->order_id !== null) {
            Log::info("Transaction {$transaction->id} is already matched.");
            return false;
        }

        return DB::transaction(function () use ($transaction) {
            // Lock the transaction row to prevent race conditions during matching
            $lockedTx = SmsPaymentGatewayTransaction::where('id', $transaction->id)
                ->lockForUpdate()
                ->first();

            if ($lockedTx->order_id !== null) {
                return false; // Matched by another concurrent process
            }

            // 2. Find Candidate Orders using Reference OR Phone
            $order = $this->findMatchingOrder($lockedTx);

            if (!$order) {
                Log::info("No matching order found for Transaction {$lockedTx->id}");
                return false;
            }

            // 3. Attach and Lock
            $this->attachTransactionToOrder($order, $lockedTx);

            // 4. Dispatch Realtime Events & Webhooks
            $this->dispatchSuccessEvents($order, $lockedTx);

            return true;
        });
    }

    /**
     * Find an unpaid order matching the transaction's reference or phone,
     * validating amount and time proximity.
     */
    private function findMatchingOrder(SmsPaymentGatewayTransaction $tx): ?SmsPaymentGatewayPaymentOrder
    {
        // Get potential order links (user sessions) or direct orders
        $query = SmsPaymentGatewayPaymentOrder::where('user_id', $tx->user_id)
            ->whereIn('status', ['pending', 'unpaid'])
            ->where('created_at', '>=', now()->subHours($this->timeWindowHours))
            ->lockForUpdate(); // Ensure order isn't concurrently matched

        // Priority 1: Exact Reference Match
        if (!empty($tx->reference_number)) {
            $referenceMatch = clone $query;
            $order = $referenceMatch->where('expected_reference', $tx->reference_number)->first();
            
            if ($order && $this->verifyAmount($order->amount, $tx->amount)) {
                return $order;
            }
        }

        // Priority 2: Normalized Phone Match via Order Links
        if (!empty($tx->phone_number)) {
            // Look up the active order link for this phone number
            $link = SmsPaymentGatewayOrderLink::where('user_id', $tx->user_id)
                ->where('phone_number', $tx->phone_number)
                ->where('status', 'pending')
                ->lockForUpdate()
                ->first();

            if ($link) {
                $phoneMatch = clone $query;
                $order = $phoneMatch->where('id', $link->order_id)->first();

                if ($order && $this->verifyAmount($order->amount, $tx->amount)) {
                    // Update the link to prevent reuse
                    $link->update(['status' => 'matched']);
                    return $order;
                }
            }
        }

        return null;
    }

    /**
     * Verify the transaction amount against the expected order amount, allowing for configured tolerance.
     */
    private function verifyAmount(float $expectedAmount, float $actualAmount): bool
    {
        $difference = abs($expectedAmount - $actualAmount);
        return $difference <= $this->amountTolerance;
    }

    /**
     * Atomically bind the transaction to the order and update statuses.
     */
    private function attachTransactionToOrder(SmsPaymentGatewayPaymentOrder $order, SmsPaymentGatewayTransaction $tx): void
    {
        // Mark transaction as consumed
        $tx->order_id = $order->id;
        $tx->status = 'matched';
        $tx->save();

        // Mark order as paid
        $order->status = 'paid';
        $order->transaction_id = $tx->id;
        $order->paid_at = now();
        $order->save();

        Log::info("Successfully matched Transaction {$tx->id} to Order {$order->id}");
    }

    /**
     * Trigger realtime updates for the frontend and dispatch webhooks.
     */
    private function dispatchSuccessEvents(SmsPaymentGatewayPaymentOrder $order, SmsPaymentGatewayTransaction $tx): void
    {
        // 1. Trigger WebSocket/Realtime UI Update for the Checkout Widget
        // Allows the waiting browser to instantly show "Payment Successful"
        Event::dispatch('SmsPaymentGateway.OrderPaid', [
            'order_id' => $order->id,
            'payment_link_id' => $order->payment_link_id,
            'transaction_id' => $tx->id,
            'status' => 'paid'
        ]);

        // 2. Dispatch Webhook to the Merchant's External System
        if (!empty($order->webhook_url)) {
            $this->dispatchWebhook($order, $tx);
        }
    }

    /**
     * Dispatch the verified payment payload to the merchant.
     */
    private function dispatchWebhook(SmsPaymentGatewayPaymentOrder $order, SmsPaymentGatewayTransaction $tx): void
    {
        $payload = [
            'event' => 'payment.success',
            'data' => [
                'order_id' => $order->id,
                'transaction_id' => $tx->id,
                'external_reference' => $order->external_reference ?? null,
                'amount' => $tx->amount,
                'currency' => $tx->currency,
                'provider' => $this->cloneProviderName($tx->sender),
                'sender_phone' => $tx->phone_number,
                'receipt_reference' => $tx->reference_number,
                'matched_at' => $order->paid_at->toIso8601String(),
            ]
        ];

        try {
            Http::timeout(10)
                ->withHeaders(['X-Signature' => $this->generateWebhookSignature($payload, $order->user_id)])
                ->post($order->webhook_url, $payload);
        } catch (\Exception $e) {
            Log::error("Failed to dispatch webhook for Order {$order->id}: " . $e->getMessage());
            // In a full production system, this would push to a failed jobs table for retry.
        }
    }

    private function generateWebhookSignature(array $payload, int $userId): string
    {
        // Get the merchant's secret key from settings
        $secret = config("sms_gateway.webhook_secrets.{$userId}", 'default_secret');
        return hash_hmac('sha256', json_encode($payload), $secret);
    }
    
    private function cloneProviderName(string $sender): string
    {
        if (mb_stripos($sender, 'vodafone') !== false) return 'Vodafone Cash';
        if (mb_stripos($sender, 'etisalat') !== false) return 'Etisalat Cash';
        if (mb_stripos($sender, 'orange') !== false) return 'Orange Cash';
        if (mb_stripos($sender, 'we') !== false) return 'WE Pay';
        if (mb_stripos($sender, 'instapay') !== false || mb_stripos($sender, 'تحويل لحظي') !== false) return 'InstaPay';
        return $sender;
    }
}
