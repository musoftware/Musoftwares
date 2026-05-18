<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\Invoice;
use Modules\Core\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Client-facing invoice payment controller.
 * Recovered from old project: Client/InvoicePaymentController.
 *
 * Flow recovered:
 * 1. Client sees invoice on their dashboard
 * 2. Client pays from wallet balance → `markPaid()` debits wallet
 * 3. Status → 'paid', WalletTransaction recorded, activity logged
 * 4. Client can optionally pay via Kashier (external gateway)
 */
class InvoicePaymentController extends Controller
{
    /**
     * Show invoice payment page for the authenticated client.
     * Recovered from old project: Client/InvoicePaymentController::show()
     */
    public function show(Request $request, $uuid)
    {
        // Invoice must belong to a client linked to the authenticated user
        $user    = Auth::user();
        $invoice = Invoice::where('uuid', $uuid)
            ->whereHas('client', fn($q) => $q->where('email', $user->email))
            ->with(['items', 'client'])
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('erp.client-invoices.index')
                ->with('error', 'This invoice is already paid.');
        }

        $wallet     = $user->getWallet();
        $userBalance = $wallet ? (float) $wallet->balance : 0.0;

        return Inertia::render('ERP/ClientInvoices/Pay', [
            'invoice'     => [
                'id'          => $invoice->id,
                'uuid'        => $invoice->uuid,
                'title'       => $invoice->title,
                'total'       => (float) $invoice->total,
                'paid'        => (float) $invoice->paid_amount,
                'remaining'   => (float) ($invoice->total - $invoice->paid_amount),
                'currency'    => $invoice->currency,
                'status'      => $invoice->status,
                'items'       => $invoice->items->map(fn($i) => [
                    'description' => $i->description,
                    'quantity'    => $i->quantity,
                    'unit_price'  => $i->unit_price,
                    'total'       => $i->quantity * $i->unit_price,
                ]),
            ],
            'user_balance' => $userBalance,
            'wallet_currency' => $wallet?->currency ?? 'USD',
        ]);
    }

    /**
     * Process wallet payment for an invoice.
     * Recovered from old project: Client/InvoicePaymentController::process()
     *
     * Business rules:
     * - Must have sufficient balance
     * - Invoice must be unpaid or partially_paid
     * - Wraps in DB transaction for atomicity
     */
    public function processWalletPayment(Request $request, $uuid)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user    = Auth::user();
        $invoice = Invoice::where('uuid', $uuid)
            ->whereHas('client', fn($q) => $q->where('email', $user->email))
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Invoice already paid.'], 400);
        }

        $wallet  = $user->getWallet();
        $amount  = (float) $request->input('amount');
        $remaining = (float) ($invoice->total - $invoice->paid_amount);
        $amount  = min($amount, $remaining); // cap at remaining

        if ((float) $wallet->balance < $amount) {
            return response()->json(['success' => false, 'message' => 'Insufficient wallet balance.'], 400);
        }

        try {
            DB::transaction(function () use ($invoice, $wallet, $amount, $user) {
                // Debit wallet
                $wallet->decrement('balance', $amount);

                // Record wallet transaction
                WalletTransaction::create([
                    'wallet_id'   => $wallet->id,
                    'type'        => 'debit',
                    'amount'      => $amount,
                    'currency'    => $wallet->currency,
                    'description' => 'Invoice payment #' . $invoice->id,
                    'meta'        => json_encode(['invoice_id' => $invoice->id, 'source' => 'client_wallet_pay']),
                ]);

                // Update invoice
                $newPaid = (float) $invoice->paid_amount + $amount;
                if (round($newPaid, 2) >= round((float) $invoice->total, 2)) {
                    $invoice->update([
                        'paid_amount' => $invoice->total,
                        'status'      => 'paid',
                        'paid_at'     => now(),
                    ]);
                } else {
                    $invoice->update([
                        'paid_amount' => $newPaid,
                        'status'      => 'partially_paid',
                    ]);
                }
            });

            return response()->json([
                'success'      => true,
                'message'      => 'Payment processed successfully.',
                'new_status'   => $invoice->fresh()->status,
                'redirect_url' => route('erp.client-invoices.index'),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Client invoice list — invoices addressed to the authenticated user.
     * Recovered from old project: Client/InvoicesController::index()
     */
    public function clientIndex(Request $request)
    {
        $user = Auth::user();

        $invoices = Invoice::whereHas('client', fn($q) => $q->where('email', $user->email))
            ->with('items')
            ->when($request->get('status') === 'unpaid', fn($q) => $q->whereIn('status', ['unpaid', 'partially_paid']))
            ->latest()
            ->paginate(14)
            ->through(fn($inv) => [
                'id'          => $inv->id,
                'uuid'        => $inv->uuid,
                'title'       => $inv->title,
                'total'       => (float) $inv->total,
                'paid_amount' => (float) $inv->paid_amount,
                'remaining'   => round((float) $inv->total - (float) $inv->paid_amount, 2),
                'currency'    => $inv->currency,
                'status'      => $inv->status,
                'sent_at'     => $inv->sent_at,
                'created_at'  => $inv->created_at,
            ]);

        // Split into unpaid and paid — recovered from old project
        $unpaid = $invoices->getCollection()->filter(fn($i) => $i['status'] !== 'paid');
        $paid   = $invoices->getCollection()->filter(fn($i) => $i['status'] === 'paid');

        return Inertia::render('ERP/ClientInvoices/Index', [
            'invoices'       => $invoices,
            'unpaid_invoices'=> $unpaid->values(),
            'paid_invoices'  => $paid->values(),
            'filters'        => $request->only(['status']),
        ]);
    }
}
