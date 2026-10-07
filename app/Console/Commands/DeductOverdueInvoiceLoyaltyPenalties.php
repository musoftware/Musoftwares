<?php

namespace App\Console\Commands;

use App\Services\LoyaltyService;
use Illuminate\Console\Command;

class DeductOverdueInvoiceLoyaltyPenalties extends Command
{
    protected $signature = 'loyalty:deduct-overdue-penalties {--dry-run : Simulate point deductions without modifying database records}';

    protected $description = 'Deduct loyalty points daily for overdue unpaid invoices with job_status = done.';

    public function handle(LoyaltyService $loyaltyService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $this->info($dryRun ? 'Running in DRY-RUN mode...' : 'Executing daily loyalty penalty deductions...');

        $result = $loyaltyService->deductOverdueInvoicePenalties($dryRun);

        if (($result['status'] ?? null) === 'rule_inactive') {
            $this->warn('Overdue penalty rule is inactive. No points deducted.');
            return self::SUCCESS;
        }

        $this->info("Processed {$result['processed_count']} overdue invoices.");
        $this->info("Penalized {$result['penalized_invoices']} invoices.");
        $this->info("Total points deducted: {$result['total_points_deducted']} PTS.");

        return self::SUCCESS;
    }
}
