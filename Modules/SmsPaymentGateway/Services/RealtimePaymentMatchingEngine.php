<?php

namespace Modules\SmsPaymentGateway\Services;

use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use App\Models\PaymentOrder;
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
     * Main Entry Point: Attempt to match a transaction manually based on user input.
     */
    public function manualMatch(PaymentOrder $order, string $userInputTransactionId): ?SmsPaymentGatewayTransaction
    {
        // Prevent duplicate matching
        if ($order->status === 'paid' || $order->transaction_id !== null) {
            return null;
        }

        return DB::transaction(function () use ($order, $userInputTransactionId) {
            // Find a transaction that matches the input (either reference number or phone number)
            // and roughly matches the amount
            $transaction = SmsPaymentGatewayTransaction::where('user_id', $order->user_id)
                ->where('amount', '>=', $order->total_amount * 0.99) // 1% tolerance
                ->where('amount', '<=', $order->total_amount * 1.01)
                ->whereIn('status', ['pending', 'unmatched'])
                ->where('created_at', '>=', now()->subHours($this->timeWindowHours))
                ->where(function ($q) use ($userInputTransactionId) {
                    $q->where('reference_number', $userInputTransactionId)
                      ->orWhere('phone_number', $userInputTransactionId);
                })
                ->lockForUpdate()
                ->first();

            if (!$transaction) {
                return null;
            }

            // Lock the order
            $lockedOrder = PaymentOrder::where('id', $order->id)->lockForUpdate()->first();
            if ($lockedOrder->status === 'paid') {
                return null;
            }

            // Attach and Lock
            $this->attachTransactionToOrder($lockedOrder, $transaction);

            // Dispatch Events & Webhooks
            $this->dispatchSuccessEvents($lockedOrder, $transaction);

            return $transaction;
        });
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
    private function attachTransactionToOrder(PaymentOrder $order, SmsPaymentGatewayTransaction $tx): void
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
    private function dispatchSuccessEvents(PaymentOrder $order, SmsPaymentGatewayTransaction $tx): void
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
    private function dispatchWebhook(PaymentOrder $order, SmsPaymentGatewayTransaction $tx): void
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
