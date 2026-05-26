<?php

namespace App\Modules\CRMWhatsAppCampaigns\Jobs;

use App\Modules\CRMWhatsAppCampaigns\Services\CampaignDeliveryManager;
use Modules\CRM\Models\WhatsAppAccount;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCampaignMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public WhatsAppCampaignDelivery $delivery,
        public WhatsAppAccount $account,
    ) {
        $this->onQueue('wa-campaign-delivery');
    }

    public function handle(CampaignDeliveryManager $deliveryManager): void
    {
        $deliveryManager->sendMessage($this->delivery, $this->account);
    }

    public function failed(\Throwable $exception): void
    {
        $this->delivery->update([
            'status'        => 'failed',
            'failed_reason' => $exception->getMessage(),
        ]);
    }
}
