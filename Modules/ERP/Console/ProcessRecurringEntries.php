<?php

namespace Modules\ERP\Console;

use Illuminate\Console\Command;
use Modules\Core\Services\RecurringService;
use Illuminate\Support\Facades\Log;

class ProcessRecurringEntries extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'erp:recurring:process {--dry-run : Preview which entries would be processed without executing}';

    /**
     * The console command description.
     */
    protected $description = 'Process all due recurring ERP entries (income, expense, invoices) and advance their next_run_at dates.';

    public function __construct(protected RecurringService $recurringService)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun
            ? 'Dry-run mode: previewing due recurring entries...'
            : 'Processing due recurring entries...'
        );

        try {
            if ($dryRun) {
                $today = now()->toDateString();
                $due = \Modules\ERP\Models\RecurringEntry::withoutGlobalScopes()
                    ->where('is_active', true)
                    ->where('next_run_at', '<=', $today)
                    ->where(function ($q) use ($today) {
                        $q->whereNull('ends_at')->orWhere('ends_at', '>=', $today);
                    })
                    ->get();

                $this->table(
                    ['ID', 'Tenant', 'Title', 'Frequency', 'Next Run'],
                    $due->map(fn($e) => [
                        $e->id,
                        $e->tenant_id,
                        $e->title,
                        $e->frequency,
                        $e->next_run_at,
                    ])
                );

                $this->info("Would process {$due->count()} entries.");
                return self::SUCCESS;
            }

            $this->recurringService->processDueEntries();
            $this->info('Recurring entries processed successfully.');

        } catch (\Exception $e) {
            Log::error('erp:recurring:process failed: ' . $e->getMessage());
            $this->error('Failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
