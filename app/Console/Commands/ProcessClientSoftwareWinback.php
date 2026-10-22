<?php

namespace App\Console\Commands;

use App\Services\ClientSoftwareWinbackService;
use Illuminate\Console\Command;

class ProcessClientSoftwareWinback extends Command
{
    protected $signature = 'winback:software-clients {--dry-run : Only preview eligible clients without sending}';

    protected $description = 'Check inactive clients (>6 months) and dispatch 50% discount software winback emails.';

    public function __construct(private readonly ClientSoftwareWinbackService $winbackService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode (no emails will be sent)...');
        }

        $stats = $this->winbackService->processAllEligible($dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed Candidates', $stats['processed']],
                [$dryRun ? 'Eligible (Dry-Run)' : 'Dispatched', $stats['sent']],
                ['Skipped / Ineligible', $stats['skipped']],
                ['Errors', $stats['errors']],
            ]
        );

        $this->info('Client software winback processing completed.');

        return self::SUCCESS;
    }
}
