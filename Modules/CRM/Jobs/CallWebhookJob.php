<?php

namespace Modules\CRM\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Modules\CRM\Models\Webhook;

class CallWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    protected $webhook;
    protected $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(Webhook $webhook, array $payload)
    {
        $this->webhook = $webhook;
        $this->payload = $payload;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!$this->webhook->is_active) {
            return;
        }

        $headers = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'Musoftware-CRM-Webhook/1.0',
            'X-Musoftware-Event' => $this->payload['event'] ?? 'unknown',
        ];

        // If a secret is provided, sign the payload
        if ($this->webhook->secret) {
            $signature = hash_hmac('sha256', json_encode($this->payload), $this->webhook->secret);
            $headers['X-Musoftware-Signature'] = $signature;
        }

        Http::timeout(10)->withHeaders($headers)->post($this->webhook->url, $this->payload);
    }
}
