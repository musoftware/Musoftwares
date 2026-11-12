<?php

namespace Modules\AppBuilder\Services;

use App\Models\Currency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Models\BuilderRequest;

/**
 * Every payment in the AI builder is also an invoice in the customer's account, so it shows on their billing page
 * with the rest of their invoices. A price paid in parts (a deposit, then the rest) is one invoice, partly paid and
 * then paid. The money already came through the gateway or the wallet, so the invoice only records it: no wallet
 * movement, no referral (the builder pays its referrers itself). A problem here never undoes a payment.
 */
class PaymentInvoices
{
    /** The project's price, or the part of it just paid: one invoice per price, partly paid until all of it is. */
    public function quotePaid(BuilderProject $project, float $paidNow): void
    {
        $this->safely($project, function () use ($project, $paidNow): void {
            $invoice = $project->quote_invoice_version === $project->quote_version ? Invoice::find($project->quote_invoice_id) : null;
            if (! $invoice) {
                $invoice = $this->create($project, [[$project->title ?: __('builder.invoice.program', [], $project->customerLanguage()), (float) $project->amount]]);
                $project->update(['quote_invoice_id' => $invoice->id, 'quote_invoice_version' => $project->quote_version]);
            }
            $this->record($invoice, (float) $invoice->paid + $paidNow);
        });
    }

    /** @param Collection<int, BuilderRequest> $requests the requests one payment covered, one line each */
    public function requestsPaid(BuilderProject $project, Collection $requests): void
    {
        $this->safely($project, function () use ($project, $requests): void {
            $invoice = $this->create($project, $requests->map(fn (BuilderRequest $request) => [$request->title, (float) $request->amount])->all());
            $this->record($invoice, (float) $requests->sum('amount'));
        });
    }

    /** A fixed fee paid on its own: the feasibility study, or the inspection of a project the customer brought. */
    public function feePaid(BuilderProject $project, string $title, float $amount): void
    {
        $this->safely($project, function () use ($project, $title, $amount): void {
            $this->record($this->create($project, [[$title, $amount]]), $amount);
        });
    }

    /** @param list<array{0: string, 1: float}> $lines title and amount */
    private function create(BuilderProject $project, array $lines): Invoice
    {
        return DB::transaction(function () use ($project, $lines): Invoice {
            $invoice = Invoice::create(['user_id' => $project->user_id, 'status' => 'unpaid', 'currency' => Currency::resolve($project->currency)?->id]);
            foreach ($lines as [$title, $amount]) {
                InvoiceItem::create(['invoice_id' => $invoice->id, 'item_title' => mb_substr($title, 0, 250), 'amount' => $amount, 'qty' => 1, 'item_type' => 'simple']);
            }
            // the total is worked out from the items when the invoice is saved
            $invoice->refresh()->save();

            return $invoice->refresh();
        });
    }

    /**
     * Writes what is paid so far. Straight to the row on purpose: the model's own "mark as paid" moves wallet money,
     * pays referrals and refuses while the customer has an older unpaid invoice, none of which fits money already received.
     */
    private function record(Invoice $invoice, float $paid): void
    {
        $total = (float) $invoice->total();
        $paid = min($paid, $total);
        $done = $paid >= $total - 0.005;

        Invoice::whereKey($invoice->id)->update([
            'paid' => $paid,
            'unpaid' => round($total - $paid, 2),
            'status' => $done ? 'paid' : 'partially_paid',
            'paid_at' => $done ? now() : null,
            'job_status' => $done ? 'done' : $invoice->job_status,
        ]);
    }

    private function safely(BuilderProject $project, \Closure $work): void
    {
        if ($project->user_id === null) {
            Log::info('App builder: no account yet, so no invoice for this payment', ['project_id' => $project->id]);

            return;
        }

        try {
            $work();
        } catch (\Throwable $e) {
            Log::error('App builder: the invoice for a payment could not be written', ['project_id' => $project->id, 'error' => $e->getMessage()]);
        }
    }
}
