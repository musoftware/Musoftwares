<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Helpers\KashierHelper;
use App\Http\Resources\InvoiceResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class GuestInvoiceController extends Controller
{
    /**
     * Show the invoice details for a guest using a signed URL.
     */
    public function show(Request $request, Invoice $invoice)
    {
        // Must be a valid signed route. The middleware 'signed' handles this.
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired signature.');
        }

        $invoice->load(['user', 'project', 'items', 'currency']);

        return Inertia::render('Guest/InvoiceShow', [
            'invoice' => (new InvoiceResource($invoice))->resolve(),
            // Pass down signed URL so the frontend can submit the payment form securely
            'pay_url' => \Illuminate\Support\Facades\URL::signedRoute('guest.invoices.pay', ['invoice' => $invoice->id])
        ]);
    }

    /**
     * Initiate the guest payment for the invoice.
     */
    public function initiatePay(Request $request, Invoice $invoice)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired signature.');
        }

        if ($invoice->status === 'paid') {
            return redirect()->back()->with('error', __('general.invoice_already_paid'));
        }

        $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
        ]);

        $amount = (float) $invoice->unpaid_total();
        if ($amount <= 0) {
            return redirect()->back()->with('error', __('general.invoice_total_zero'));
        }

        if (!$invoice->currency) {
            throw new \Exception("Invoice {$invoice->id} is missing an associated currency relation.");
        }
        $currency = $invoice->currency->currency;

        $paymentUrl = KashierHelper::buildInvoiceGuestPaymentUrl(
            $amount,
            $invoice->id,
            $invoice->user_id,
            $request->input('guest_name'),
            $request->input('guest_email'),
            $currency
        );

        return Inertia::location($paymentUrl);
    }

    /**
     * Handle Kashier success redirect.
     */
    public function paymentSuccess(Request $request)
    {
        return Inertia::render('Guest/PaymentResult', [
            'status' => 'success',
            'message' => __('general.payment_successful_thank_you')
        ]);
    }

    /**
     * Handle Kashier failure redirect.
     */
    public function paymentFailure(Request $request)
    {
        return Inertia::render('Guest/PaymentResult', [
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
            Log::warning('Guest Invoice Kashier webhook: Invalid signature.');
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $payload = json_decode($request->getContent(), true);
        if (!$payload || !isset($payload['data'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload format'], 400);
        }

        $data = $payload['data'];
        $metaData = json_decode($data['metaData'] ?? '{}', true);

        if (($metaData['source'] ?? '') !== 'guest-invoice-payment') {
            return response()->json(['status' => 'ignored', 'message' => 'Not a guest invoice payment']);
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
                    Log::info("Guest Invoice payment successful for invoice #{$invoice->id}");
                } catch (\Exception $e) {
                    Log::error("Guest Invoice payment failed to mark as paid for invoice #{$invoice->id}: " . $e->getMessage());
                    return response()->json(['status' => 'error', 'message' => 'Failed to process payment internally'], 500);
                }
            }
        } else {
            Log::info("Guest Invoice payment failed for invoice #{$invoice->id}, Status: " . $data['status']);
        }

        return response()->json(['status' => 'success']);
    }
}
