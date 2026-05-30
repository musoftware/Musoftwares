<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Console;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Jobs\RecoverFailedCampaignDeliveriesJob;
use Illuminate\Console\Command;

class RecoverFailedCampaignDeliveriesCommand extends Command
{
    protected $signature = 'crm:wa-campaigns:recover-failed';
    protected $description = 'Recover and retry failed campaign deliveries.';

    public function handle(): int
    {
        RecoverFailedCampaignDeliveriesJob::dispatch();
        $this->info('Failed campaign delivery recovery job dispatched.');
        return self::SUCCESS;
    }
}
