<?php

namespace App\Modules\CRMWhatsAppInbox\Jobs;

use App\Modules\CRMWhatsAppInbox\Services\WhatsAppInboxService;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 15, 30];
    public $queue = 'whatsapp-incoming';

    public function __construct(
        public WhatsAppAccount $account,
        public array $payload,
        public string $eventType,
    ) {}

    public function handle(WhatsAppInboxService $inboxService): void
    {
        match ($this->eventType) {
            'message'         => $this->handleIncomingMessage($inboxService),
            'status_update'   => $this->handleStatusUpdate(),
            'connection'      => $this->handleConnectionUpdate(),
            default           => Log::info("Unhandled webhook event type: {$this->eventType}", $this->payload),
        };
    }

    protected function handleIncomingMessage(WhatsAppInboxService $inboxService): void
    {
        $inboxService->processIncomingMessage($this->account, $this->payload);
    }

    protected function handleStatusUpdate(): void
    {
        $messageId = $this->payload['message_id'] ?? null;
        $status = $this->payload['status'] ?? null;

        if ($messageId && $status) {
            app(\App\Modules\CRMWhatsAppInbox\Services\MessageDeliveryService::class)
                ->updateDeliveryStatus($messageId, $status);
        }
    }

    protected function handleConnectionUpdate(): void
    {
        $status = $this->payload['status'] ?? 'disconnected';
        $this->account->update(['status' => $status]);

        if ($status === 'connected' && !empty($this->payload['session_data'])) {
            app(\App\Modules\CRMWhatsAppInbox\Services\WhatsAppSessionManager::class)
                ->handleConnectionSuccess($this->account, $this->payload['session_data']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Webhook processing failed for account {$this->account->id}: {$exception->getMessage()}", [
            'account_id' => $this->account->id,
            'event_type' => $this->eventType,
            'payload'    => $this->payload,
        ]);
    }
}
