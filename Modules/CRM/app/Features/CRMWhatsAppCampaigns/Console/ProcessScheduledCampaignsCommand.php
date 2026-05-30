<?php

namespace Modules\CRM\app\Features\CRMWhatsAppCampaigns\Console;

use Modules\CRM\app\Features\CRMWhatsAppCampaigns\Services\WhatsAppCampaignService;
use Modules\CRM\Models\WhatsAppCampaign;
use Illuminate\Console\Command;

class ProcessScheduledCampaignsCommand extends Command
{
    protected $signature = 'crm:wa-campaigns:process-scheduled';
    protected $description = 'Start campaigns that are scheduled to run now.';

    public function handle(WhatsAppCampaignService $campaignService): int
    {
        $campaigns = WhatsAppCampaign::withoutGlobalScopes()
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($campaigns as $campaign) {
            try {
                $campaignService->start($campaign);
                $count++;
                $this->info("Started campaign: {$campaign->name} (#{$campaign->id})");
            } catch (\Exception $e) {
                $this->error("Failed to start campaign #{$campaign->id}: {$e->getMessage()}");
            }
        }

        $this->info("Processed {$count} scheduled campaigns.");
        return self::SUCCESS;
    }
}
