<?php

namespace App\Modules\CRMWhatsAppInbox\Console;

use App\Modules\CRMWhatsAppInbox\Services\CRMWhatsAppLimitsService;
use Illuminate\Console\Command;

class ResetMonthlyMessageUsageCommand extends Command
{
    protected $signature = 'crm:whatsapp:reset-usage';
    protected $description = 'Reset monthly WhatsApp message usage counters';

    public function handle(CRMWhatsAppLimitsService $limitsService): int
    {
        $count = $limitsService->resetMonthlyUsage();
        $this->info("Reset {$count} monthly usage counters.");
        return self::SUCCESS;
    }
}
