<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs;

use Modules\CRM\Models\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecoverFailedDeliveriesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct()
    {
        $this->onQueue('whatsapp-retry');
    }

    public function handle(): void
    {
        // Find messages that failed within the last 24 hours and haven't been retried more than once
        $failedMessages = WhatsAppMessage::withoutGlobalScopes()
            ->where('delivery_status', 'failed')
            ->where('created_at', '>=', now()->subHours(24))
            ->where(function ($q) {
                $q->whereNull('metadata->retry_count')
                  ->orWhereRaw("JSON_EXTRACT(metadata, '$.retry_count') < 2");
            })
            ->cursor();

        foreach ($failedMessages as $message) {
            // Increment retry count in metadata
            $metadata = $message->metadata ?? [];
            $metadata['retry_count'] = ($metadata['retry_count'] ?? 0) + 1;
            $message->update(['metadata' => $metadata]);

            RetryFailedMessageJob::dispatch($message)->onQueue('whatsapp-retry');
        }
    }
}
