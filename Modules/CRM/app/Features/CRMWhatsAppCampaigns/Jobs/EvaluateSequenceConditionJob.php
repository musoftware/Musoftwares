<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services\CampaignSequenceEngine;
use Modules\CRM\Models\WhatsAppCampaignDelivery;
use Modules\CRM\Models\WhatsAppCampaignSequenceStep;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateSequenceConditionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct(
        public WhatsAppCampaignDelivery $delivery,
        public WhatsAppCampaignSequenceStep $step,
    ) {
        $this->onQueue('wa-campaign-sequence');
    }

    public function handle(CampaignSequenceEngine $sequenceEngine): void
    {
        $sequenceEngine->executeStep($this->delivery, $this->step);
    }
}
