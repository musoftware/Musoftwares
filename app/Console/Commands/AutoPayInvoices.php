<?php

namespace App\Console\Commands;

use App\Models\AdminSettings;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class AutoPayInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:auto-pay';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically pay unpaid invoices after X days using client balance';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) AdminSettings::GetValue('auto_pay_after_days', 3);
        $thresholdDate = now('Africa/Cairo')->subDays($days);

        // Find invoices:
        // - status in ['unpaid', 'partially_paid']
        // - final_total > 0 (to make sure it's not a zero invoice)
        // - unpaid > 0
        // - created_at <= thresholdDate (created at least X days ago)
        // - archive = 0
        // - Order by ID ascending so older invoices are processed first
        $invoices = Invoice::whereIn('status', ['unpaid', 'partially_paid'])
            ->where('archive', '0')
            ->where('unpaid', '>', 0)
            ->where('created_at', '<=', $thresholdDate)
            ->orderBy('id', 'asc')
            ->get();

        $this->info("Found " . $invoices->count() . " unpaid invoices to check for auto-payment.");

        foreach ($invoices as $invoice) {
            $client = $invoice->user;
            if (!$client) {
                continue;
            }

            try {
                // Get client balance in invoice currency
                $balance = (float) $client->balance($invoice->currency_id);
                $unpaidTotal = (float) $invoice->unpaid_total();

                if ($balance >= $unpaidTotal && $unpaidTotal > 0) {
                    $this->info("Auto-paying invoice #{$invoice->id} for user #{$client->id} (unpaid: {$unpaidTotal}, balance: {$balance})");
                    
                    // Call bill_invoice with autoPaid = true
                    $invoice->bill_invoice(true);
                }
            } catch (\Exception $e) {
                $this->error("Failed to auto-pay invoice #{$invoice->id}: " . $e->getMessage());
                Log::error("Failed to auto-pay invoice #{$invoice->id}: " . $e->getMessage(), [
                    'invoice_id' => $invoice->id,
                    'exception' => $e
                ]);
            }
        }

        $this->info("Auto-payment check completed.");
    }
}
