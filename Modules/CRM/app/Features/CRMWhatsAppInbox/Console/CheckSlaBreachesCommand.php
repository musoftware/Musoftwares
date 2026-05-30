<?php

namespace Modules\CRM\app\Features\CRMWhatsAppInbox\Console;

use Modules\CRM\app\Features\CRMWhatsAppInbox\Jobs\CheckSlaBreachesJob;
use Illuminate\Console\Command;

class CheckSlaBreachesCommand extends Command
{
    protected $signature = 'crm:whatsapp:check-sla';
    protected $description = 'Check all open WhatsApp conversations for SLA breaches';

    public function handle(): int
    {
        CheckSlaBreachesJob::dispatch();
        $this->info('SLA breach check job dispatched.');
        return self::SUCCESS;
    }
}
