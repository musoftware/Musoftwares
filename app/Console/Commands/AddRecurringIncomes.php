<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AppliesRecurringItems;
use App\Models\RecurringIncome;
use Illuminate\Console\Command;

class AddRecurringIncomes extends Command
{
    use AppliesRecurringItems;

    protected $signature = 'add:recurring_incomes';

    protected $description = 'Add Recurring Incomes';

    public function handle(): int
    {
        $failed = $this->applyRecurringItems(RecurringIncome::where('is_active', true), 'AddRecurringIncomes');

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
