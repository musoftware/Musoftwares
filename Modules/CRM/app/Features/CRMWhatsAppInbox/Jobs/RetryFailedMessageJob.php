<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs;

use Modules\CRM\Models\WhatsAppMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryFailedMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(
        public WhatsAppMessage $message,
    ) {
        $this->onQueue('whatsapp-retry');
    }

    public function handle(): void
    {
        if ($this->message->delivery_status !== 'failed') {
            return;
        }

        // Reset status and re-dispatch
        $this->message->update([
            'delivery_status' => 'pending',
            'failed_reason'   => null,
        ]);

        SendWhatsAppMessageJob::dispatch($this->message)->onQueue('whatsapp-outgoing');
    }
}
