<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Privacy\DataRetentionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class EnforceDataRetentionPolicy extends Command
{
    protected $signature = 'privacy:enforce-retention';

    protected $description = 'Purge expired OTPs/temp logs and anonymize unverified or abandoned accounts per PDPL retention policy.';

    public function handle(DataRetentionService $retentionService): int
    {
        $metrics = $retentionService->runScheduledCleanup();

        $this->table(
            ['Retention Task', 'Affected Records'],
            [
                ['Expired Verification & Auth Tokens Purged', $metrics['expired_tokens_purged']],
                ['Temporary Session & Email Logs Purged', $metrics['temp_logs_purged']],
                ['Unverified Accounts Anonymized', $metrics['unverified_accounts_anonymized']],
                ['Abandoned Accounts Anonymized', $metrics['abandoned_accounts_anonymized']],
            ]
        );

        Log::info('[PDPL Retention] Scheduled data retention policy executed', $metrics);

        return self::SUCCESS;
    }
}
