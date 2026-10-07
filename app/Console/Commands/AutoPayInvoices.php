<?php

namespace App\Console\Commands;

use App\Models\AdminSettings;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class AutoPayInvoices extends Command
{
    private const CHUNK_SIZE = 200;

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
     * Pay unpaid invoices older than the configured number of days from the
     * client's wallet, oldest first, in chunks. bill_invoice(true) re-checks the
     * wallet under a row lock, so the balance check here is only a fast filter.
     */
    public function handle(): int
    {
        $days = (int) AdminSettings::GetValue('auto_pay_after_days', 3);
        $thresholdDate = now('Africa/Cairo')->subDays($days);

        Invoice::with('user')
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->where('archive', '0')
            ->where('unpaid', '>', 0)
            ->where('created_at', '<=', $thresholdDate)
            ->chunkById(self::CHUNK_SIZE, function ($invoices) {
                foreach ($invoices as $invoice) {
                    $this->autoPay($invoice);
                }
            });

        $this->info('Auto-payment check completed.');

        return Command::SUCCESS;
    }

    private function autoPay(Invoice $invoice): void
    {
        $client = $invoice->user;
        if (! $client) {
            return;
        }

        try {
            $balance = (float) $client->balance($invoice->currency_id);
            $unpaidTotal = (float) $invoice->unpaid_total();
            if ($unpaidTotal <= 0 || $balance < $unpaidTotal) {
                return;
            }

            $this->info("Auto-paying invoice #{$invoice->id} for user #{$client->id} (unpaid: {$unpaidTotal}, balance: {$balance})");
            $invoice->bill_invoice(true);
            $client->refresh();
        } catch (Throwable $e) {
            $this->error("Failed to auto-pay invoice #{$invoice->id}: ".$e->getMessage());
            Log::error('AutoPayInvoices: failed to auto-pay invoice', [
                'invoice_id' => $invoice->id,
                'user_id' => $client->id,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
