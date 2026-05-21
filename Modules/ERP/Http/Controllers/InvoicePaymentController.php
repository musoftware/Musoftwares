<?php

namespace Modules\ERP\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\ERP\Models\Invoice;
use Modules\ERP\Models\ClientWallet;
use Modules\ERP\Models\WalletTransaction as ClientWalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Modules\ERP\Models\Tenant;

/**
 * Client-facing invoice payment controller.
 *
 * ╔══════════════════════════════════════════════════════════════════╗
 * ║  FINANCIAL ISOLATION BOUNDARY                                    ║
 * ║                                                                  ║
 * ║  Invoice payments use ONLY the ERP ClientWallet.                ║
 * ║  The client's ERP wallet balance represents credit the tenant    ║
 * ║  has loaded for them (e.g. via manual credit, commissions).      ║
 * ║                                                                  ║
 * ║  NEVER use Core\Wallet here. The platform's real money is       ║
 * ║  completely separate from this ERP billing system.               ║
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
            ->where('client_id', $user->id)
            ->where('tenant_id', Tenant::platformId())
            ->with(['items'])
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('erp.client-invoices.index')
                ->with('info', 'This invoice is already paid.');
        }

        // Load the ERP ClientWallet for the platform tenant
        $wallet  = $user->platformWallet;
        $balance = $wallet ? (float) $wallet->balance : 0.0;

        return Inertia::render('ERP/ClientInvoices/Pay', [
            'invoice' => [
                'id'            => $invoice->id,
                'uuid'          => $invoice->uuid ?? $invoice->id,
                'invoice_number'=> $invoice->invoice_number,
                'amount'        => (float) $invoice->amount,
                'paid_amount'   => (float) $invoice->paid_amount,
                'remaining'     => round((float) $invoice->amount - (float) $invoice->paid_amount, 2),
                'currency'      => $invoice->amount_currency,
                'status'        => $invoice->status,
                'due_date'      => $invoice->due_date?->format('Y-m-d'),
                'items'         => $invoice->items->map(fn($i) => [
                    'title'      => $i->title,
                    'quantity'   => $i->quantity,
                    'unit_price' => $i->unit_price,
                    'total'      => $i->total,
                ]),
            ],
            'client_balance'  => $balance,
            'wallet_currency' => $wallet?->currency ?? $invoice->amount_currency,
        ]);
    }

    /**
     * Process wallet payment for an invoice.
     * Deducts from the ERP ClientWallet (NOT the platform Core\Wallet).
     */
    public function processWalletPayment(Request $request, string $uuid)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = Auth::user();

        $invoice = Invoice::where('id', $uuid)
            ->where('client_id', $user->id)
            ->where('tenant_id', Tenant::platformId())
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return response()->json(['success' => false, 'message' => 'Invoice is already paid.'], 400);
        }

        if (!in_array($invoice->status, ['sent', 'partial'])) {
            return response()->json(['success' => false, 'message' => 'Invoice must be in sent or partial state.'], 400);
        }

        $remaining = round((float) $invoice->amount - (float) $invoice->paid_amount, 2);
        $amount    = min((float) $request->input('amount'), $remaining);

        try {
            $result = DB::transaction(function () use ($invoice, $user, $amount) {
                $wallet = ClientWallet::where('tenant_id', Tenant::platformId())
                    ->where('client_id', $user->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((float) $wallet->balance < $amount) {
                    throw new \Exception('Insufficient ERP wallet balance. Available: ' . $wallet->balance);
                }

                $balBefore = (float) $wallet->balance;
                $balAfter  = $balBefore - $amount;

                // Immutable ledger entry in ERP wallet transactions
                ClientWalletTransaction::create([
                    'tenant_id'         => $invoice->tenant_id,
                    'wallet_id'         => $wallet->id,
                    'type'              => 'invoice_paid',
                    'direction'         => 'debit',
                    'amount'            => $amount,
                    'amount_currency'   => $wallet->currency,
                    'business_amount'   => $amount * (float) $invoice->exchange_rate,
                    'business_currency' => $invoice->business_currency ?? $wallet->currency,
                    'exchange_rate'     => (float) $invoice->exchange_rate,
                    'exchange_rate_date'=> $invoice->exchange_rate_date ?? now()->toDateString(),
                    'balance_before'    => $balBefore,
                    'balance_after'     => $balAfter,
                    'reference_type'    => Invoice::class,
                    'reference_id'      => $invoice->id,
                    'note'              => 'Invoice #' . $invoice->invoice_number . ' payment',
                    'created_by'        => Auth::id(),
                ]);

                $wallet->update(['balance' => $balAfter]);

                // Update invoice paid state
                $newPaid = round((float) $invoice->paid_amount + $amount, 2);
                $isPaid  = $newPaid >= (float) $invoice->amount;

                $invoice->update([
                    'paid_amount' => $isPaid ? $invoice->amount : $newPaid,
                    'status'      => $isPaid ? 'paid' : 'partial',
                    'paid_at'     => $isPaid ? now() : null,
                ]);

                return $isPaid;
            });

            return response()->json([
                'success'      => true,
                'message'      => 'Payment recorded successfully.',
                'fully_paid'   => $result,
                'redirect_url' => route('erp.client-invoices.index'),
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

        $invoices = Invoice::where('client_id', $user->id)
            ->where('tenant_id', Tenant::platformId())
            ->with('items')
            ->latest()
            ->paginate(14)
            ->through(fn($inv) => [
                'id'            => $inv->id,
                'uuid'          => $inv->uuid ?? $inv->id,
                'invoice_number'=> $inv->invoice_number,
                'amount'        => (float) $inv->amount,
                'paid_amount'   => (float) $inv->paid_amount,
                'remaining'     => round((float) $inv->amount - (float) $inv->paid_amount, 2),
                'currency'      => $inv->amount_currency,
                'status'        => $inv->status,
                'due_date'      => $inv->due_date?->format('Y-m-d'),
                'issued_at'     => $inv->issued_at?->format('Y-m-d'),
            ]);

        $collection      = $invoices->getCollection();
        $unpaidInvoices  = $collection->filter(fn($i) => $i['status'] !== 'paid')->values();
        $paidInvoices    = $collection->filter(fn($i) => $i['status'] === 'paid')->values();

        // ERP client wallet balance for platform
        $wallet  = $user->platformWallet;
        $balance = $wallet ? (float) $wallet->balance : 0.0;

        return Inertia::render('ERP/ClientInvoices/Index', [
            'invoices'        => $invoices,
            'unpaid_invoices' => $unpaidInvoices,
            'paid_invoices'   => $paidInvoices,
            'client_balance'  => $balance,
            'wallet_currency' => $wallet?->currency ?? 'USD',
        ]);
    }
}
