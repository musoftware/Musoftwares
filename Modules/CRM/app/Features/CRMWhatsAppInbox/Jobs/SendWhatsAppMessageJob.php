<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Contracts\WhatsAppProviderInterface;
use Modules\CRM\app\Features\CRMWhatsAppInbox\Services\MessageDeliveryService;
use Modules\CRM\Models\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public WhatsAppMessage $message,
    ) {
        $this->onQueue('whatsapp-outgoing');
    }

    public function handle(WhatsAppProviderInterface $provider, MessageDeliveryService $deliveryService): void
    {
        $message = $this->message;
        $conversation = $message->conversation;
        $account = $conversation->account;

        if (!$account || !$account->isConnected()) {
            $deliveryService->markAsFailed($message, 'WhatsApp account is not connected.');
            return;
        }

        try {
            $result = match ($message->type) {
                'text' => $provider->sendText(
                    $account,
                    $conversation->contact_phone,
                    $message->body
                ),
                'image', 'video', 'audio', 'document' => $provider->sendMedia(
                    $account,
                    $conversation->contact_phone,
                    $message->media_url,
                    $message->type,
                    $message->body // caption
                ),
                'template' => $provider->sendTemplate(
                    $account,
                    $conversation->contact_phone,
                    $message->template_name,
                    $message->template_params ?? []
                ),
                'reaction' => $provider->sendText(
                    $account,
                    $conversation->contact_phone,
                    $message->reaction_emoji
                ),
                default => throw new \RuntimeException("Unsupported message type: {$message->type}"),
            };

            $deliveryService->markAsSent($message, $result['message_id'] ?? '');
        } catch (\Exception $e) {
            if ($this->attempts() >= $this->tries) {
                $deliveryService->markAsFailed($message, $e->getMessage());
            }
            throw $e; // Re-throw for retry
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->message->update([
            'delivery_status' => 'failed',
            'failed_reason'   => $exception->getMessage(),
        ]);
    }
}
