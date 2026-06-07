<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Client-facing invoice payment controller.
 *
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  FINANCIAL INTEGRATION                                           ║
 * ║                                                                  ║
 * ║  Invoice payments use the platform's Core\Wallet.                ║
 * ║  The client pays directly from their main platform balance.      ║
 * ╚══════════════════════════════════════════════════════════════════╝
 */
class InvoicePaymentController extends Controller
{
    /**
     * Show invoice payment page for the authenticated platform user.
     * The user is matched to a TenantClient via the user_id FK or email fallback.
     */
    public function show(Request $request, string $uuid)
    {
        $user = Auth::user();

        $invoice = Invoice::where('id', $uuid)
            ->where('user_id', $user->id)
            ->with(['items'])
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('billing.invoices.index')
                ->with('info', __('billing.invoice_already_paid'));
        }

        // Load the legacy user balance
        $balance = (float) $user->user_balance;

        return Inertia::render('Billing/InvoicePay', [
            'invoice' => [
                'id'            => $invoice->id,
                'uuid'          => $invoice->uuid ?? $invoice->id,
                'invoice_number'=> 'INV-' . str_pad($invoice->id, 5, '0', STR_PAD_LEFT),
                'amount'        => (float) $invoice->total(),
                'paid_amount'   => (float) $invoice->paid,
                'remaining'     => round((float) $invoice->total() - (float) $invoice->paid, 2),
                'currency'      => \App\Models\Currency::find($invoice->currency),
                'status'        => $invoice->status,
                'due_date'      => $invoice->created_at?->format('Y-m-d'),
                'items'         => $invoice->items()->get()->map(fn($i) => [
                    'title'      => $i->item_title,
                    'quantity'   => $i->qty,
                    'unit_price' => $i->amount,
                    'total'      => $i->total(),
                ]),
            ],
            'client_balance'  => $balance,
            'wallet_currency' => $user->currency_id ? \App\Models\Currency::find($user->currency_id) : \App\Models\Currency::find($invoice->currency),
        ]);
    }

    /**
     * Process wallet payment for an invoice.
     * Deducts from the platform Core\Wallet.
     */
    public function processWalletPayment(Request $request, string $uuid)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = Auth::user();

        $invoice = Invoice::where('id', $uuid)
            ->where('user_id', $user->id)
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Invoice is already paid.'], 400);
        }

        if (!in_array($invoice->status, ['unpaid', 'partially_paid'])) {
            return response()->json(['success' => false, 'message' => 'Invoice must be in unpaid or partial state.'], 400);
        }

        $remaining = round((float) $invoice->total() - (float) $invoice->paid, 2);
        $amount    = min((float) $request->input('amount'), $remaining);

        try {
            $result = DB::transaction(function () use ($invoice, $user, $amount) {
                if ((float) $user->user_balance < $amount) {
                    throw new \Exception('Insufficient user balance.');
                }

                // Use the legacy user add_balance method
                $user->add_balance(-1 * $amount, 'Invoice #' . $invoice->id . ' payment', 'used', $invoice->currency);

                // Update invoice paid state
                $newPaid = round((float) $invoice->paid + $amount, 2);
                $isPaid  = $newPaid >= (float) $invoice->total();

                $invoice->update([
                    'paid' => $isPaid ? $invoice->total() : $newPaid,
                    'unpaid' => $isPaid ? 0 : round($invoice->total() - $newPaid, 2),
                    'status' => $isPaid ? 'paid' : 'partially_paid',
                ]);

                return $isPaid;
            });

            return response()->json([
                'success'      => true,
                'message'      => 'Payment recorded successfully.',
                'fully_paid'   => $result,
                'redirect_url' => route('billing.invoices.index'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Client invoice list — invoices addressed to the authenticated user.
     */
    public function clientIndex(Request $request)
    {
        $user = Auth::user();

        $invoices = Invoice::where('user_id', $user->id)
            ->latest()
            ->paginate(14)
            ->through(fn($inv) => [
                'id'            => $inv->id,
                'uuid'          => $inv->uuid ?? $inv->id,
                'invoice_number'=> 'INV-' . str_pad($inv->id, 5, '0', STR_PAD_LEFT),
                'amount'        => (float) $inv->total(),
                'paid_amount'   => (float) $inv->paid,
                'remaining'     => round((float) $inv->total() - (float) $inv->paid, 2),
                'currency'      => \App\Models\Currency::find($inv->currency),
                'status'        => $inv->status,
                'due_date'      => $inv->created_at?->format('Y-m-d'),
                'issued_at'     => $inv->created_at?->format('Y-m-d'),
            ]);

        $collection      = $invoices->getCollection();
        $unpaidInvoices  = $collection->filter(fn($i) => $i['status'] !== 'paid')->values();
        $paidInvoices    = $collection->filter(fn($i) => $i['status'] === 'paid')->values();

        // Legacy user balance for platform
        $balance = (float) $user->user_balance;

        return Inertia::render('Billing/Invoices', [
            'invoices'        => $invoices,
            'unpaid_invoices' => $unpaidInvoices,
            'paid_invoices'   => $paidInvoices,
            'client_balance'  => $balance,
            'wallet_currency' => $user->currency_id ? \App\Models\Currency::find($user->currency_id) : \App\Models\Currency::find($user->currency),
        ]);
    }
}
