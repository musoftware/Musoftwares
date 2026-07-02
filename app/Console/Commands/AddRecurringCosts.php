<?php

namespace App\Console\Commands;

use App\Models\RecurringCost;
use Illuminate\Console\Command;

class AddRecurringCosts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:recurring_costs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Recurring Costs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (RecurringCost::where('is_active', true)->get() as $item) {
            $item->apply();
        }

        return Command::SUCCESS;
    }
}
