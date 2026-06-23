<?php

namespace App\Console\Commands;

use App\Models\RecurringInvoice;
use Illuminate\Console\Command;

class AddRecurringInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:recurring_invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Recurring Invoices';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (RecurringInvoice::where('is_active', true)->get() as $item) {
            $item->apply();
        }

        return Command::SUCCESS;
    }
}
