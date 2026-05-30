<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Console;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs\AggregateCampaignAnalyticsJob;
use Illuminate\Console\Command;

class AggregateCampaignAnalyticsCommand extends Command
{
    protected $signature = 'crm:wa-campaigns:aggregate-analytics';
    protected $description = 'Aggregate campaign analytics for all active campaigns.';

    public function handle(): int
    {
        AggregateCampaignAnalyticsJob::dispatch();
        $this->info('Campaign analytics aggregation job dispatched.');
        return self::SUCCESS;
    }
}
