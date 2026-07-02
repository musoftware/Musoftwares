<?php

namespace App\Console\Commands;

use App\Models\RecurringIncome;
use Illuminate\Console\Command;

class AddRecurringIncomes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:recurring_incomes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Recurring Incomes';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (RecurringIncome::where('is_active', true)->get() as $item) {
            $item->apply();
        }

        return Command::SUCCESS;
    }
}
