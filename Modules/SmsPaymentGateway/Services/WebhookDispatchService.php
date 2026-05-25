<?php

namespace Modules\SmsPaymentGateway\Services;

use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayWebhook;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Modules\SmsPaymentGateway\Jobs\DispatchWebhookJob;

class WebhookDispatchService
{
    /**
     * Dispatch webhook for a transaction asynchronously
     */
    public function dispatchTransactionWebhook(SmsPaymentGatewayTransaction $transaction): void
    {
        $user = $transaction->user;

        if (!$user) {
            return;
        }

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$webhook) {
            return;
        }

        $payload = [
            'event' => 'transaction.detected',
            'transaction_id' => (string) $transaction->id,
            'device_id' => (string) $transaction->device_id,
            'amount' => (float) $transaction->amount,
            'balance' => (float) $transaction->balance,
            'currency' => $transaction->currency,
            'reference_number' => $transaction->reference_number,
            'sender' => $transaction->sender,
            'phone_number' => $transaction->phone_number,
            'sender_name' => $transaction->sender_name,
            'transaction_date' => $transaction->transaction_date?->toIso8601String(),
            'status' => $transaction->status,
            'sms_message' => $transaction->sms_message,
            'order_id' => $transaction->metadata['order_id'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ];

        // Dispatch to queue instead of sending synchronously
        DispatchWebhookJob::dispatch($webhook, $payload)->onQueue('webhooks');
    }
}
