<?php

namespace Modules\CRM\Domains\Communication\Actions;

use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs\ProcessWhatsAppWebhookJob;

class ReceiveWhatsAppWebhookAction
{
    /**
     * Handle the incoming webhook idempotently.
     */
    public function execute(WhatsAppAccount $account, array $payload, string $eventType): void
    {
        // Generate an idempotency key based on message ID or raw payload hash
        $idempotencyKey = $this->extractIdempotencyKey($payload);
        
        if (!$idempotencyKey) {
            // Fallback for events without clear IDs: just dispatch it
            ProcessWhatsAppWebhookJob::dispatch($account, $payload, $eventType)
                ->onQueue('whatsapp-incoming')
                ->afterCommit();
            return;
        }

        $lockKey = "whatsapp_webhook_lock:{$idempotencyKey}";
        
        // Attempt to acquire lock using Redis (1 hour expiration)
        // 'NX' means set only if it does not exist
        $acquired = Redis::set($lockKey, 'processing', 'EX', 3600, 'NX');

        if (!$acquired) {
            Log::info("Webhook idempotency lock acquired. Dropping duplicate event.", [
                'idempotency_key' => $idempotencyKey,
            ]);
            return;
        }

        // Dispatch job and pass the idempotency key so the job can mark it completed
        ProcessWhatsAppWebhookJob::dispatch($account, $payload, $eventType, $idempotencyKey)
            ->onQueue('whatsapp-incoming')
            ->afterCommit();
    }

    /**
     * Extract a unique identifier from the webhook payload.
     */
    protected function extractIdempotencyKey(array $payload): ?string
    {
        // Cloud API Message ID
        if (isset($payload['entry'][0]['changes'][0]['value']['messages'][0]['id'])) {
            return $payload['entry'][0]['changes'][0]['value']['messages'][0]['id'];
        }

        // Generic fallback for custom provider
        if (isset($payload['message_id'])) {
            return $payload['message_id'];
        }
        
        // Status updates ID
        if (isset($payload['entry'][0]['changes'][0]['value']['statuses'][0]['id'])) {
            return $payload['entry'][0]['changes'][0]['value']['statuses'][0]['id'] . '_status';
        }

        return null;
    }
}
