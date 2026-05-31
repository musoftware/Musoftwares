<?php

namespace App\Console\Commands;

use App\Models\RecurringSalary;
use Illuminate\Console\Command;

class AddRecurringSalaries extends Command
{
    protected $signature = 'add:recurring_salaries';

    protected $description = 'Apply recurring salaries (earned transactions, exchanged to user currency)';

    public function handle(): int
    {
        foreach (RecurringSalary::all() as $salary) {
            $salary->apply();
        }

        return Command::SUCCESS;
    }
}
