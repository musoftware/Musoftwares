<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Helpers\KashierHelper;
use App\Models\AdminSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    /**
     * List all platform invoices for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $invoices = Invoice::where('user_id', $user->id)
            ->with(['currency'])
            ->latest()
            ->paginate(14)
            ->through(fn($inv) => [
                'id'             => $inv->id,
                'uuid'           => $inv->uuid,
                'invoice_number' => $inv->id, // Platform invoices might use id or enc_id(), check this
                'amount'         => round((float) $inv->total(), 2),
                'paid_amount'    => round((float) $inv->paid, 2),
                'remaining'      => $inv->unpaid_total(),
                'currency'       => $inv->currency,
                'status'         => $inv->status,
                'due_date'       => $inv->schedule['start_date'] ?? null, // fallback
                'issued_at'      => $inv->created_at?->format('Y-m-d'),
            ]);

        $collection      = $invoices->getCollection();
        $unpaidInvoices  = $collection->filter(fn($i) => $i['status'] !== 'paid')->values();
        $paidInvoices    = $collection->filter(fn($i) => $i['status'] === 'paid')->values();

        $walletCurrencyId = $user->currency_id;
        if (!$walletCurrencyId) {
            throw new \Exception("User {$user->id} is missing a currency configuration.");
        }
        $walletCurrency = \App\Models\Currency::find($walletCurrencyId);

        return Inertia::render('Billing/Invoices', [
            'invoices'        => $invoices,
            'unpaid_invoices' => $unpaidInvoices,
            'paid_invoices'   => $paidInvoices,
            'client_balance'  => round((float) $user->balance(), 2),
            'wallet_currency' => $walletCurrency,
        ]);
    }

    /**
     * Show the invoice payment page.
     */
    public function show(Request $request, $uuid)
    {
        $user = Auth::user();

        $invoice = Invoice::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->with(['items', 'currency'])
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return redirect()->route('billing.invoices.index')
                ->with('info', __('general.invoice_already_paid'));
        }

        $walletCurrencyId = $user->currency_id;
        if (!$walletCurrencyId) {
            throw new \Exception("User {$user->id} is missing a currency configuration.");
        }
        $walletCurrency = \App\Models\Currency::find($walletCurrencyId);
        
        $remaining = $invoice->unpaid_total();
        $remainingInWalletCurrency = \App\Models\CurrenciesExchange::RateToday($remaining, $invoice->currency_id, $walletCurrencyId);

        return Inertia::render('Billing/InvoicePay', [
            'invoice' => [
                'id'             => $invoice->id,
                'uuid'           => $invoice->uuid,
                'invoice_number' => $invoice->id,
                'amount'         => round((float) $invoice->total(), 2),
                'paid_amount'    => round((float) $invoice->paid, 2),
                'remaining'      => $invoice->unpaid_total(),
                'currency'       => $invoice->currency,
                'status'         => $invoice->status,
                'due_date'       => $invoice->schedule['start_date'] ?? null,
                'issued_at'      => $invoice->created_at?->format('Y-m-d'),
                'notes'          => $invoice->notes,
                'items'          => $invoice->items->map(fn($i) => [
                    'title'      => $i->description ?? $i->item_name,
                    'quantity'   => $i->qty,
                    'unit_price' => $i->price,
                    'total'      => $i->total(),
                ]),
            ],
            'client_balance'  => round((float) $user->balance(), 2),
            'wallet_currency' => $walletCurrency,
            'remaining_in_wallet_currency' => round((float) $remainingInWalletCurrency, 2),
        ]);
    }

    /**
     * Process payment: Wallet deduction if sufficient, else Kashier URL.
     */
    public function processPayment(Request $request, $uuid)
    {
        $user = Auth::user();
        
        $invoice = Invoice::where('uuid', $uuid)
            ->where('user_id', $user->id)
            ->with(['currency'])
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return response()->json(['success' => false, 'message' => __('general.invoice_already_paid')], 400);
        }

        $remaining = $invoice->unpaid_total();
        
        $walletCurrencyId = $user->currency_id;
        if (!$walletCurrencyId) {
            return response()->json(['success' => false, 'message' => "User is missing a currency configuration"], 500);
        }
        
        $remainingInWalletCurrency = \App\Models\CurrenciesExchange::RateToday($remaining, $invoice->currency_id, $walletCurrencyId);
        
        // If wallet balance covers the remaining amount, pay via wallet
        if ((float) $user->balance() >= $remainingInWalletCurrency) {
            try {
                $invoice->bill_invoice();
                return response()->json([
                    'success'      => true,
                    'message'      => __('general.payment_successful_thank_you'),
                    'fully_paid'   => true,
                    'redirect_url' => route('billing.invoices.index'),
                ]);
            } catch (\Exception $e) {
                Log::error("Failed to pay platform invoice via wallet: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => __('general.payment_failed_please_try_again'),
                ], 500);
            }
        }

        // Balance insufficient, redirect to Kashier payment gateway
        if (!$invoice->currency) {
            return response()->json(['success' => false, 'message' => "Currency is missing"], 500);
        }
        
        $currencyCode = $invoice->currency->currency;

        $paymentUrl = KashierHelper::buildUserInvoicePaymentUrl(
            $remaining,
            $invoice->id,
            $user->id,
            $user->name,
            $user->email,
            $currencyCode
        );

        return response()->json([
            'success' => true,
            'redirect_url' => $paymentUrl,
            'gateway' => true
        ]);
    }

    /**
     * Handle Kashier success redirect.
     */
    public function paymentSuccess(Request $request)
    {
        return Inertia::render('Billing/PaymentResult', [
            'status' => 'success',
            'message' => __('general.payment_successful_thank_you')
        ]);
    }

    /**
     * Handle Kashier failure redirect.
     */
    public function paymentFailure(Request $request)
    {
        return Inertia::render('Billing/PaymentResult', [
            'status' => 'error',
            'message' => __('general.payment_failed_please_try_again')
        ]);
    }

    /**
     * Handle Kashier server-to-server webhook.
     */
    public function paymentWebhook(Request $request)
    {
        if (!KashierHelper::validatePayload()) {
            Log::warning('User Invoice Kashier webhook: Invalid signature.');
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $payload = json_decode($request->getContent(), true);
        if (!$payload || !isset($payload['data'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload format'], 400);
        }

        $data = $payload['data'];
        $metaData = json_decode($data['metaData'] ?? '{}', true);

        if (($metaData['source'] ?? '') !== 'user-invoice-payment') {
            return response()->json(['status' => 'ignored', 'message' => 'Not a user invoice payment']);
        }

        $invoiceId = $metaData['invoice_id'] ?? null;
        if (!$invoiceId) {
            return response()->json(['status' => 'error', 'message' => 'Missing invoice ID'], 400);
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return response()->json(['status' => 'error', 'message' => 'Invoice not found'], 404);
        }

        if ($data['status'] === 'SUCCESS') {
            if ($invoice->status !== 'paid') {
                try {
                    $invoice->mark_as_paid();
                    Log::info("User Invoice payment successful for invoice #{$invoice->id}");
                } catch (\Exception $e) {
                    Log::error("User Invoice payment failed to mark as paid for invoice #{$invoice->id}: " . $e->getMessage());
                    return response()->json(['status' => 'error', 'message' => 'Failed to process payment internally'], 500);
                }
            }
        } else {
            Log::info("User Invoice payment failed for invoice #{$invoice->id}, Status: " . $data['status']);
        }

        return response()->json(['status' => 'success']);
    }
}
