<?php

namespace Modules\PaymentGateway\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\PaymentGateway\Models\GatewayClient;
use Modules\PaymentGateway\Models\GatewayPayment;
use App\Helpers\KashierHelper;

class PaymentGatewayService
{
    private const DEFAULT_COMMISSION_RATE = 40.0;

    // ─── Client Management ───────────────────────────────────────────────────

    public function createClient(array $data): GatewayClient
    {
        return GatewayClient::create([
            'name'            => $data['name'],
            'client_id'       => GatewayClient::generateClientId(),
            'client_secret'   => GatewayClient::generateSecret(),
            'webhook_secret'  => GatewayClient::generateWebhookSecret(),
            'website'         => $data['website'] ?? null,
            'status'          => $data['status'] ?? 'active',
            'allowed_ips'     => $data['allowed_ips'] ?? null,
            'commission_rate' => $data['commission_rate'] ?? self::DEFAULT_COMMISSION_RATE,
        ]);
    }

    public function updateClient(GatewayClient $client, array $data): GatewayClient
    {
        $client->update([
            'name'            => $data['name'] ?? $client->name,
            'website'         => $data['website'] ?? $client->website,
            'status'          => $data['status'] ?? $client->status,
            'allowed_ips'     => $data['allowed_ips'] ?? $client->allowed_ips,
            'commission_rate' => $data['commission_rate'] ?? $client->commission_rate,
        ]);

        return $client->fresh();
    }

    public function regenerateClientSecret(GatewayClient $client): GatewayClient
    {
        $client->update([
            'client_secret'  => GatewayClient::generateSecret(),
            'webhook_secret' => GatewayClient::generateWebhookSecret(),
        ]);

        return $client->fresh();
    }

    public function getClientStats(GatewayClient $client): array
    {
        $payments = $client->payments();

        return [
            'total_payments'     => $payments->count(),
            'successful_count'   => $payments->where('status', 'success')->count(),
            'pending_count'      => $payments->where('status', 'pending')->count(),
            'failed_count'       => $payments->where('status', 'failed')->count(),
            'total_volume'       => (float) $payments->where('status', 'success')->sum('amount'),
            'total_commission'   => (float) $payments->where('status', 'success')->sum('commission_amount'),
            'total_net'          => (float) $payments->where('status', 'success')->sum('net_amount'),
            'commission_rate'    => (float) $client->commission_rate,
        ];
    }

    // ─── Commission Calculation ──────────────────────────────────────────────

    public function calculateCommission(float $amount, float $rate = self::DEFAULT_COMMISSION_RATE): array
    {
        $commission = round($amount * ($rate / 100), 2);
        $net        = round($amount - $commission, 2);

        return [
            'amount'            => $amount,
            'commission_rate'   => $rate,
            'commission_amount' => $commission,
            'net_amount'        => $net,
        ];
    }

    // ─── Payment Creation ────────────────────────────────────────────────────

    /**
     * Create a new payment and build the Kashier redirect URL.
     * Called by the public API when a merchant's website sends a payment request.
     */
    public function initiatePayment(GatewayClient $client, array $data): GatewayPayment
    {
        $commissionData = $this->calculateCommission(
            (float) $data['amount'],
            (float) $client->commission_rate
        );

        $internalOrderId = 'pgw_' . strtolower(Str::random(16));

        $payment = GatewayPayment::create([
            'client_id'          => $client->id,
            'internal_order_id'  => $internalOrderId,
            'external_order_id'  => $data['order_id'],
            'amount'             => $commissionData['amount'],
            'currency'           => $data['currency'] ?? 'EGP',
            'commission_rate'    => $commissionData['commission_rate'],
            'commission_amount'  => $commissionData['commission_amount'],
            'net_amount'         => $commissionData['net_amount'],
            'description'        => $data['description'] ?? null,
            'customer_name'      => $data['customer']['name'] ?? null,
            'customer_email'     => $data['customer']['email'] ?? null,
            'customer_phone'     => $data['customer']['phone'] ?? null,
            'success_url'        => $data['success_url'],
            'failure_url'        => $data['failure_url'] ?? null,
            'webhook_url'        => $data['webhook_url'] ?? null,
            'metadata'           => $data['metadata'] ?? null,
            'status'             => 'pending',
        ]);

        // Build the Kashier payment URL
        $kashierUrl = $this->buildKashierUrl($payment, $client);
        $payment->update(['kashier_payment_url' => $kashierUrl]);

        return $payment->fresh();
    }

    /**
     * Build the Kashier payment redirect URL for a gateway payment.
     */
    private function buildKashierUrl(GatewayPayment $payment, GatewayClient $client): string
    {
        $merchantId = config('services.kashier.merchant_id', 'MID-12345');
        $mode       = config('services.kashier.mode', 'live');

        $successUrl = route('api.payment-gateway.webhook.success', $payment->internal_order_id);
        $failureUrl = route('api.payment-gateway.webhook.failure', $payment->internal_order_id);
        $webhookUrl = route('api.payment-gateway.webhook.kashier');

        $hash = KashierHelper::generateHash(
            $merchantId,
            $payment->internal_order_id,
            $payment->amount,
            $payment->currency,
            'pgw_client_' . $client->id
        );

        $customer = array_filter([
            'firstName' => $payment->customer_name,
            'email'     => $payment->customer_email,
            'phone'     => $payment->customer_phone,
            'reference' => 'pgw_client_' . $client->id,
        ]);

        $params = [
            'merchantId'         => $merchantId,
            'orderId'            => $payment->internal_order_id,
            'amount'             => $payment->amount,
            'currency'           => $payment->currency,
            'hash'               => $hash,
            'mode'               => $mode,
            'merchantRedirect'   => urlencode($successUrl),
            'serverWebhook'      => urlencode($webhookUrl),
            'failureRedirect'    => urlencode($failureUrl),
            'redirectMethod'     => 'get',
            'type'               => 'external',
            'brandColor'         => '#000000',
            'display'            => 'en',
            'manualCapture'      => 'false',
            'customer'           => json_encode($customer),
            'interactionSource'  => 'Ecommerce',
            'enable3DS'          => 'true',
            'allowedMethods'     => 'card,wallet',
            'CustomerReference'  => 'pgw_client_' . $client->id,
            'metaData'           => json_encode([
                'source'              => 'musoftware-payment-gateway',
                'internal_order_id'   => $payment->internal_order_id,
                'client_id'           => $client->client_id,
            ]),
        ];

        return 'https://payments.kashier.io/?' . http_build_query($params);
    }

    // ─── Webhook Processing ──────────────────────────────────────────────────

    /**
     * Process a successful Kashier webhook for a gateway payment.
     */
    public function processKashierWebhook(array $data): bool
    {
        $metadata     = $data['metaData'] ?? [];
        if (is_string($metadata)) {
            $metadata = json_decode($metadata, true) ?? [];
        }

        $internalOrderId  = $metadata['internal_order_id'] ?? null;
        $kashierTrxId     = $data['transactionId'] ?? null;
        $status           = $data['status'] ?? null;

        if (!$internalOrderId) {
            Log::warning('[PaymentGateway] Webhook received with no internal_order_id', $data);
            return false;
        }

        $payment = GatewayPayment::where('internal_order_id', $internalOrderId)->first();
        if (!$payment) {
            Log::warning("[PaymentGateway] Payment not found: {$internalOrderId}");
            return false;
        }

        if ($payment->status === 'success') {
            Log::info("[PaymentGateway] Already processed: {$internalOrderId}");
            return true; // Idempotent
        }

        $newStatus = match (strtoupper($status)) {
            'SUCCESS' => 'success',
            'FAILED'  => 'failed',
            default   => 'failed',
        };

        $payment->update([
            'status'                 => $newStatus,
            'kashier_transaction_id' => $kashierTrxId,
        ]);

        // Notify the merchant's webhook URL
        if ($newStatus === 'success' && $payment->webhook_url) {
            $this->notifyMerchant($payment);
        }

        Log::info("[PaymentGateway] Payment {$internalOrderId} marked {$newStatus}");
        return true;
    }

    /**
     * Fire the merchant's own webhook with the payment result.
     */
    private function notifyMerchant(GatewayPayment $payment): void
    {
        try {
            $payload = [
                'event'          => 'payment.success',
                'order_id'       => $payment->external_order_id,
                'internal_id'    => $payment->internal_order_id,
                'amount'         => $payment->amount,
                'currency'       => $payment->currency,
                'status'         => $payment->status,
                'transaction_id' => $payment->kashier_transaction_id,
                'timestamp'      => now()->toIso8601String(),
            ];

            // Sign the payload with the client's webhook secret
            $secret    = $payment->client->webhook_secret;
            $signature = hash_hmac('sha256', json_encode($payload), $secret);

            $response = Http::timeout(10)
                ->withHeaders(['X-Gateway-Signature' => $signature])
                ->post($payment->webhook_url, $payload);

            $payment->update([
                'webhook_sent_at'  => now(),
                'webhook_response' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error("[PaymentGateway] Failed to notify merchant: " . $e->getMessage());
        }
    }

    // ─── Client Authentication ───────────────────────────────────────────────

    /**
     * Authenticate a client by client_id + client_secret.
     * Returns the client or null.
     */
    public function authenticateClient(string $clientId, string $clientSecret): ?GatewayClient
    {
        $client = GatewayClient::where('client_id', $clientId)
            ->where('status', 'active')
            ->first();

        if (!$client) {
            return null;
        }

        if (!hash_equals($client->client_secret, $clientSecret)) {
            return null;
        }

        return $client;
    }
}
