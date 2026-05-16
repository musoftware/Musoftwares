<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Core\Services\RecurringService;

class ProcessRecurringEntries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring:process';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process due recurring entries';

    /**
     * Execute the console command.
     */
    public function handle(RecurringService $recurringService)
    {
        $this->info('Starting to process recurring entries...');

        $recurringService->processDueEntries();

        $this->info('Finished processing recurring entries.');
    }
}
