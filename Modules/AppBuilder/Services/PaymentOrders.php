<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderPaymentOrder;
use Modules\AppBuilder\Models\BuilderProject;
use RuntimeException;

/**
 * What each payment link was made for. A webhook pays exactly what its own link asked for, never the project's
 * latest price, and only when the gateway's signed amount covers it. Each link pays once.
 */
class PaymentOrders
{
    /** Rounding between our conversion and the gateway's. */
    private const TOLERANCE = 0.01;

    /** Saves the link's amounts under its order id (both read back from the link the gateway will see). */
    public function record(BuilderProject $project, string $url, float $amount, string $invoice): void
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        if (empty($query['orderId']) || ! isset($query['amount'], $query['currency'])) {
            throw new RuntimeException('The payment link has no order id or amount.');
        }

        BuilderPaymentOrder::create([
            'project_id' => $project->id,
            'order_id' => (string) $query['orderId'],
            'invoice' => $invoice,
            'amount' => round($amount, 2),
            'currency' => $project->currency,
            'gateway_amount' => round((float) $query['amount'], 2),
            'gateway_currency' => strtoupper((string) $query['currency']),
        ]);
    }

    /**
     * The link a successful, signed webhook paid, marked paid now; null when it is unknown, already paid, or the
     * gateway's signed amount or currency does not match it.
     *
     * @param  array<string, mixed>  $data  the webhook's `data`, already signature-checked
     */
    public function claim(array $data): ?BuilderPaymentOrder
    {
        return DB::transaction(function () use ($data): ?BuilderPaymentOrder {
            $order = BuilderPaymentOrder::where('order_id', (string) ($data['merchantOrderId'] ?? ''))->lockForUpdate()->first();
            $problem = match (true) {
                $order === null => 'unknown payment link',
                $order->paid_at !== null => 'payment link already paid',
                ! $this->amountMatches($order, $data) => 'paid amount or currency does not match the link',
                default => null,
            };
            if ($problem !== null) {
                Log::warning('App builder: webhook not credited: '.$problem, ['order' => $data['merchantOrderId'] ?? null, 'transaction' => $data['transactionId'] ?? null]);

                return null;
            }

            $order->update(['paid_at' => now(), 'transaction_id' => $data['transactionId'] ?? null]);

            return $order;
        });
    }

    /** Only what the gateway signed is trusted; an unsigned amount is ignored and the link's own amount stands. */
    private function amountMatches(BuilderPaymentOrder $order, array $data): bool
    {
        $signed = array_map('strval', (array) ($data['signatureKeys'] ?? []));
        if (in_array('amount', $signed, true) && (float) ($data['amount'] ?? 0) + self::TOLERANCE < (float) $order->gateway_amount) {
            return false;
        }

        return ! in_array('currency', $signed, true) || strtoupper((string) ($data['currency'] ?? '')) === $order->gateway_currency;
    }
}
