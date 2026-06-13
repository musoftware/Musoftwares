<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\TenantClient;
use Modules\ERP\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Client-facing ERP invoice payment controller.
 *
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  FULLY ISOLATED ERP FINANCIAL LAYER                              ║
 * ║                                                                  ║
 * ║  All payments operate exclusively within the ERP's own ledger.  ║
 * ║  The platform wallet (user_balance) is NOT used here.           ║
 * ║  Client balance = sum of erp_client_transactions for that client ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */
class InvoicePaymentController extends Controller
{
    /**
     * Resolve the ERP TenantClient record for the currently authenticated platform user.
     * Clients are linked to platform users via the user_id FK on erp_tenant_clients.
     */
    private function resolveClient(): ?TenantClient
    {
        $user = Auth::user();
        if (!$user) {
            return null;
        }

        return TenantClient::where('user_id', $user->id)->first();
    }

    /**
     * Show invoice payment page for the authenticated platform user acting as an ERP client.
     */
    public function show(Request $request, int $id)
    {
        $client = $this->resolveClient();

        if (!$client) {
            abort(404, __('erp.client_not_found'));
        }

        $invoice = Invoice::withoutGlobalScopes()
            ->where('id', $id)
            ->where('client_id', $client->id)
            ->with(['items', 'currency', 'tenant'])
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('billing.invoices.index')
                ->with('info', __('erp.invoice_already_paid'));
        }

        return Inertia::render('ERP/ClientInvoices/Pay', [
            'invoice' => [
                'id'             => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount'         => round((float) $invoice->amount, 2),
                'paid_amount'    => round((float) $invoice->paid_amount, 2),
                'remaining'      => $invoice->unpaidAmount(),
                'currency'       => $invoice->currency,
                'status'         => $invoice->status,
                'due_date'       => $invoice->due_date?->format('Y-m-d'),
                'issued_at'      => $invoice->issued_at?->format('Y-m-d'),
                'notes'          => $invoice->notes,
                'items'          => $invoice->items->map(fn($i) => [
                    'title'      => $i->title,
                    'quantity'   => $i->quantity,
                    'unit_price' => $i->unit_price,
                    'total'      => $i->total,
                ]),
            ],
            'client_balance'  => round((float) $client->balance(), 2),
            'client_currency' => $client->currency,
        ]);
    }

    /**
     * Process a payment for an ERP invoice using the client's ERP wallet balance.
     * Uses ONLY the ERP ledger (erp_client_transactions). Platform wallet is NOT touched.
     */
    public function processWalletPayment(Request $request, int $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $client = $this->resolveClient();

        if (!$client) {
            return response()->json(['success' => false, 'message' => __('erp.client_not_found')], 404);
        }

        $invoice = Invoice::withoutGlobalScopes()
            ->where('id', $id)
            ->where('client_id', $client->id)
            ->with(['tenant'])
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return response()->json(['success' => false, 'message' => __('erp.invoice_already_paid')], 400);
        }

        if (!in_array($invoice->status, ['sent', 'partial'])) {
            return response()->json(['success' => false, 'message' => __('erp.invoice_must_be_sent')], 400);
        }

        $remaining = $invoice->unpaidAmount();
        $amount    = min((float) $request->input('amount'), $remaining);

        // Check ERP client balance (NOT platform wallet)
        if ((float) $client->balance() < $amount) {
            return response()->json([
                'success' => false,
                'message' => __('erp.insufficient_client_balance'),
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($invoice, $client, $amount, $remaining) {
                $isPaid = abs($amount - $remaining) < 0.01;

                // Proportion for business amount
                $ratio          = $amount / max(0.01, (float) $invoice->amount);
                $businessAmount = round((float) $invoice->business_amount * $ratio, 2);

                // Debit the client's ERP wallet (erp_client_transactions)
                WalletTransaction::create([
                    'tenant_id'            => $invoice->tenant_id,
                    'client_id'            => $client->id,
                    'project_id'           => $invoice->project_id,
                    'type'                 => 'used',
                    'direction'            => 'debit',
                    'amount'               => -$amount,
                    'currency_id'          => $invoice->currency_id,
                    'business_amount'      => -$businessAmount,
                    'business_currency_id' => $invoice->tenant?->base_currency_id,
                    'exchange_rate'        => (float) $invoice->exchange_rate,
                    'exchange_rate_date'   => $invoice->exchange_rate_date ?? now()->toDateString(),
                    'reference_type'       => Invoice::class,
                    'reference_id'         => $invoice->id,
                    'note'                 => ($isPaid ? 'Full payment' : 'Partial payment') . ' for Invoice #' . $invoice->invoice_number . ' via client portal.',
                    'created_by'           => Auth::id(),
                ]);

                // Update invoice status
                $newPaid = round((float) $invoice->paid_amount + $amount, 2);
                $invoice->update([
                    'paid_amount' => $isPaid ? $invoice->amount : $newPaid,
                    'status'      => $isPaid ? 'paid' : 'partial',
                    'paid_at'     => $isPaid ? now() : null,
                ]);

                return $isPaid;
            });

            return response()->json([
                'success'      => true,
                'message'      => __('erp.payment_recorded_success'),
                'fully_paid'   => $result,
                'redirect_url' => route('billing.invoices.index'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => __('erp.payment_failed'),
            ], 500);
        }
    }

    /**
     * Client invoice list — ERP invoices addressed to the authenticated user's TenantClient record.
     */
    public function clientIndex(Request $request)
    {
        $client = $this->resolveClient();

        if (!$client) {
            return Inertia::render('ERP/ClientInvoices/Index', [
                'invoices'       => [],
                'unpaid_invoices'=> [],
                'paid_invoices'  => [],
                'client_balance' => 0,
                'client_currency'=> null,
            ]);
        }

        $invoices = Invoice::withoutGlobalScopes()
            ->where('client_id', $client->id)
            ->with(['currency'])
            ->latest()
            ->paginate(14)
            ->through(fn($inv) => [
                'id'             => $inv->id,
                'invoice_number' => $inv->invoice_number,
                'amount'         => round((float) $inv->amount, 2),
                'paid_amount'    => round((float) $inv->paid_amount, 2),
                'remaining'      => $inv->unpaidAmount(),
                'currency'       => $inv->currency,
                'status'         => $inv->status,
                'due_date'       => $inv->due_date?->format('Y-m-d'),
                'issued_at'      => $inv->issued_at?->format('Y-m-d'),
            ]);

        $collection      = $invoices->getCollection();
        $unpaidInvoices  = $collection->filter(fn($i) => $i['status'] !== 'paid')->values();
        $paidInvoices    = $collection->filter(fn($i) => $i['status'] === 'paid')->values();

        return Inertia::render('ERP/ClientInvoices/Index', [
            'invoices'        => $invoices,
            'unpaid_invoices' => $unpaidInvoices,
            'paid_invoices'   => $paidInvoices,
            'client_balance'  => round((float) $client->balance(), 2),
            'client_currency' => $client->currency,
        ]);
    }
}
