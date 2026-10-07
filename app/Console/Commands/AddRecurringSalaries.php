<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AppliesRecurringItems;
use App\Models\RecurringSalary;
use Illuminate\Console\Command;

class AddRecurringSalaries extends Command
{
    use AppliesRecurringItems;

    protected $signature = 'add:recurring_salaries';

    protected $description = 'Apply recurring salaries (earned transactions, exchanged to user currency)';

    public function handle(): int
    {
        $failed = $this->applyRecurringItems(RecurringSalary::where('is_active', true), 'AddRecurringSalaries');

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
