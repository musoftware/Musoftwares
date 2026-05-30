<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs;

use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RetryCampaignDeliveryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(
        public WhatsAppCampaignDelivery $delivery,
    ) {
        $this->onQueue('wa-campaign-retry');
    }

    public function handle(): void
    {
        if (!$this->delivery->canRetry()) {
            return;
        }

        $this->delivery->update([
            'status'        => 'pending',
            'failed_reason' => null,
            'retry_count'   => $this->delivery->retry_count + 1,
        ]);

        $account = $this->delivery->account;
        if ($account) {
            SendCampaignMessageJob::dispatch($this->delivery, $account);
        }
    }
}
