<?php

namespace App\Modules\CRMWhatsAppCampaigns\Jobs;

use App\Modules\CRMWhatsAppCampaigns\Services\CampaignDeliveryManager;
use Modules\CRM\Models\WhatsAppAccount;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCampaignWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [5, 15, 30];

    public function __construct(
        public WhatsAppAccount $account,
        public array $payload,
    ) {
        $this->onQueue('wa-campaign-webhook');
    }

    public function handle(CampaignDeliveryManager $deliveryManager): void
    {
        $messageId = $this->payload['message_id'] ?? null;
        $status = $this->payload['status'] ?? null;

        if ($messageId && $status) {
            $deliveryManager->updateDeliveryStatus($messageId, $status);
        }
    }
}
