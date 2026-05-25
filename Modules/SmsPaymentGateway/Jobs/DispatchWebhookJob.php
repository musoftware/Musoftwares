<?php

namespace Modules\SmsPaymentGateway\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayWebhook;

class DispatchWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $webhook;
    public $payload;

    // Exponential backoff configuration
    public $tries = 5;
    public $backoff = [15, 60, 300, 1800]; // 15s, 1m, 5m, 30m

    public function __construct(SmsPaymentGatewayWebhook $webhook, array $payload)
    {
        $this->webhook = $webhook;
        $this->payload = $payload;
    }

    public function handle()
    {
        $payloadJson = json_encode($this->payload);
        $signature = hash_hmac('sha256', $payloadJson, $this->webhook->webhook_secret);

        $response = Http::withHeaders([
            'X-AutoSMS-Signature' => $signature,
            'X-AutoSMS-Event' => $this->payload['event'] ?? 'unknown',
            'Content-Type' => 'application/json',
            'User-Agent' => 'AutoSMS-Payment-Hub/1.0',
        ])->timeout(10)->post($this->webhook->webhook_url, $this->payload);

        $statusCode = $response->status();

        if ($statusCode >= 200 && $statusCode < 300) {
            $this->webhook->increment('success_count');
            $this->webhook->update(['last_triggered_at' => now()]);
            Log::info('AutoSMS Webhook sent successfully', [
                'webhook_id' => $this->webhook->id,
                'status_code' => $statusCode,
            ]);
            return;
        }

        // Throwing an exception triggers the retry mechanism based on exponential backoff
        throw new \Exception("Webhook delivery failed with status {$statusCode}: " . substr($response->body(), 0, 500));
    }

    public function failed(\Throwable $exception)
    {
        $this->webhook->increment('failure_count');
        $this->webhook->update(['last_triggered_at' => now()]);

        Log::error('AutoSMS Webhook permanently failed after retries', [
            'webhook_id' => $this->webhook->id,
            'error' => $exception->getMessage(),
        ]);

        // Dead Letter Queue (DLQ) Implementation
        DB::table('sms_payment_gateway_failed_webhooks')->insert([
            'tenant_id' => $this->webhook->tenant_id,
            'user_id' => $this->webhook->user_id,
            'webhook_id' => $this->webhook->id,
            'payload' => json_encode($this->payload),
            'error_message' => $exception->getMessage(),
            'failed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
