<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AppliesRecurringItems;
use App\Models\RecurringCost;
use Illuminate\Console\Command;

class AddRecurringCosts extends Command
{
    use AppliesRecurringItems;

    protected $signature = 'add:recurring_costs';

    protected $description = 'Add Recurring Costs';

    public function handle(): int
    {
        $failed = $this->applyRecurringItems(RecurringCost::where('is_active', true), 'AddRecurringCosts');

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
