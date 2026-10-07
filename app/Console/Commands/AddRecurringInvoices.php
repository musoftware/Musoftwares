<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\AppliesRecurringItems;
use App\Models\RecurringInvoice;
use Illuminate\Console\Command;

class AddRecurringInvoices extends Command
{
    use AppliesRecurringItems;

    protected $signature = 'add:recurring_invoices';

    protected $description = 'Add Recurring Invoices';

    public function handle(): int
    {
        $failed = $this->applyRecurringItems(RecurringInvoice::with('user')->where('is_active', true), 'AddRecurringInvoices');

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }
}
