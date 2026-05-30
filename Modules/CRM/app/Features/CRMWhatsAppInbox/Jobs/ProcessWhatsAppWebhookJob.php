<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppInboxService;
use Modules\CRM\Domains\Communication\Actions\ProcessIncomingWhatsAppMessageAction;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 15, 30];

    public function __construct(
        public WhatsAppAccount $account,
        public array $payload,
        public string $eventType,
        public ?string $idempotencyKey = null,
    ) {
        $this->onQueue('whatsapp-incoming');
    }

    public function handle(WhatsAppInboxService $inboxService, ProcessIncomingWhatsAppMessageAction $processAction): void
    {
        match ($this->eventType) {
            'message'         => $this->handleIncomingMessage($processAction),
            'status_update'   => $this->handleStatusUpdate(),
            'connection'      => $this->handleConnectionUpdate(),
            default           => Log::info("Unhandled webhook event type: {$this->eventType}", $this->payload),
        };

        if ($this->idempotencyKey) {
            Redis::set("whatsapp_webhook_lock:{$this->idempotencyKey}", 'completed', 'EX', 3600);
        }
    }

    protected function handleIncomingMessage(ProcessIncomingWhatsAppMessageAction $processAction): void
    {
        $processAction->execute($this->account, $this->payload);
    }

    protected function handleStatusUpdate(): void
    {
        $messageId = $this->payload['message_id'] ?? null;
        $status = $this->payload['status'] ?? null;

        if ($messageId && $status) {
            app(\Modules\CRM\app\Features\CRMWhatsAppInbox\Services\MessageDeliveryService::class)
                ->updateDeliveryStatus($messageId, $status);
        }
    }

    protected function handleConnectionUpdate(): void
    {
        $status = $this->payload['status'] ?? 'disconnected';
        $this->account->update(['status' => $status]);

        if ($status === 'connected' && !empty($this->payload['session_data'])) {
            app(\Modules\CRM\app\Features\CRMWhatsAppInbox\Services\WhatsAppSessionManager::class)
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
