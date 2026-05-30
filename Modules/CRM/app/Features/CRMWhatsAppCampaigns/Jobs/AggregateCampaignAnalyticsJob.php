<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services\CampaignAnalyticsAggregator;
use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AggregateCampaignAnalyticsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    public function __construct()
    {
        $this->onQueue('wa-campaign-analytics');
    }

    public function handle(CampaignAnalyticsAggregator $aggregator): void
    {
        // Aggregate analytics for all running campaigns
        WhatsAppCampaign::withoutGlobalScopes()
            ->whereIn('status', ['running', 'completed'])
            ->where('updated_at', '>=', now()->subHour())
            ->cursor()
            ->each(fn(WhatsAppCampaign $campaign) => $aggregator->aggregate($campaign));
    }
}
