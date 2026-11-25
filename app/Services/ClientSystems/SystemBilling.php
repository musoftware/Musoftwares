<?php

namespace App\Services\ClientSystems;

use App\Events\InvoiceCreated;
use App\Models\ClientSystem;
use App\Models\ClientSystemBill;
use App\Models\CurrenciesExchange;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Monthly bills for client systems. Each bill holds the coming month's fee (paid ahead) and the share of
 * sales reported before it. The first bill locks at once until paid; later bills get the grace days.
 */
final class SystemBilling
{
    /** Most months caught up in one run, so a long-stopped scheduler cannot flood the client with invoices. */
    private const MAX_PERIODS_PER_RUN = 3;

    /** Creates every bill whose date has come. Returns how many bills were made. */
    public function billDue(ClientSystem $system, CarbonInterface $now): int
    {
        $made = 0;
        while ($made < self::MAX_PERIODS_PER_RUN && $system->next_bill_at !== null && $system->next_bill_at->lte($now)) {
            $this->createBill($system, $now);
            $system->refresh();
            $made++;
        }

        return $made;
    }

    private function createBill(ClientSystem $system, CarbonInterface $now): void
    {
        DB::transaction(function () use ($system, $now) {
            $locked = ClientSystem::whereKey($system->id)->lockForUpdate()->firstOrFail();
            $start = $locked->next_bill_at->copy();
            $bill = $this->newBill($locked, $start, $now);
            $locked->sales()->whereNull('client_system_bill_id')->where('sold_at', '<', $start)->update(['client_system_bill_id' => $bill->id]);
            $locked->forceFill(['next_bill_at' => $start->copy()->addMonthNoOverflow()])->save();
            $this->invoiceFor($locked, $bill);
        });
    }

    private function newBill(ClientSystem $system, CarbonInterface $start, CarbonInterface $now): ClientSystemBill
    {
        $salesTotal = (float) $system->sales()->whereNull('client_system_bill_id')->where('sold_at', '<', $start)->where('status', 'paid')->sum('amount');
        $graceDays = $system->bills()->exists() ? $system->grace_days : 0;

        return $system->bills()->create([
            'period_start' => $start,
            'period_end' => $start->copy()->addMonthNoOverflow(),
            'monthly_fee' => $system->monthly_price,
            'sales_total' => $salesTotal,
            'sales_percent' => $system->sales_percent,
            'sales_fee' => round($salesTotal * (float) $system->sales_percent / 100, 2),
            'due_at' => $now,
            'lock_at' => $now->copy()->addDays($graceDays),
        ]);
    }

    /** A bill with nothing to pay gets no invoice and never blocks the system. */
    private function invoiceFor(ClientSystem $system, ClientSystemBill $bill): void
    {
        $lines = array_filter($this->lines($system, $bill), fn (array $line) => $line['amount'] > 0);
        if ($lines === []) {
            return;
        }
        $user = $system->user;
        $invoice = Invoice::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'currency_id' => $user->currency_id,
            'status' => 'unpaid',
            'job_status' => 'pending',
            'paid' => 0,
            'is_suspended' => false,
            'due_date' => $bill->due_at->toDateString(),
        ]);
        foreach ($lines as $line) {
            InvoiceItem::create(['invoice_id' => $invoice->id, 'item_title' => $line['title'], 'item_type' => 'simple', 'qty' => 1, 'amount' => $line['amount']]);
        }
        $invoice->forceFill(['unpaid' => $invoice->total(true)])->save();
        $bill->forceFill(['invoice_id' => $invoice->id])->save();
        DB::afterCommit(fn () => $this->announce($invoice));
    }

    /** Invoice lines in the client's wallet currency. A missing exchange rate stops the bill (it is retried next run). */
    private function lines(ClientSystem $system, ClientSystemBill $bill): array
    {
        $toCurrency = $system->user->currency_id;
        $period = $bill->period_start->format('Y-m-d').' / '.$bill->period_end->format('Y-m-d');

        return [
            [
                'title' => __('client_systems.invoice_monthly', ['system' => $system->name, 'period' => $period]),
                'amount' => (float) CurrenciesExchange::RateToday((float) $bill->monthly_fee, $system->currency_id, $toCurrency),
            ],
            [
                'title' => __('client_systems.invoice_sales', ['system' => $system->name, 'percent' => (float) $bill->sales_percent, 'total' => number_format((float) $bill->sales_total, 2), 'date' => $bill->period_start->format('Y-m-d')]),
                'amount' => (float) CurrenciesExchange::RateToday((float) $bill->sales_fee, $system->currency_id, $toCurrency),
            ],
        ];
    }

    /** Pays from the wallet when it can, then tells the client. Failures here never undo the bill. */
    private function announce(Invoice $invoice): void
    {
        try {
            $invoice->user->try_pay_unpaid_invoices();
            event(new InvoiceCreated($invoice->fresh()));
        } catch (\Throwable $e) {
            Log::warning('ClientSystems: invoice created but auto-pay or notice failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
        }
    }
}
