<?php

namespace App\Http\Controllers;

use App\Models\PaymentLink;
use App\Helpers\KashierHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class GuestPaymentLinkController extends Controller
{
    public function show(Request $request, $uuid)
    {
        $paymentLink = PaymentLink::with('currency')->where('uuid', $uuid)->firstOrFail();

        if ($paymentLink->status === 'paid') {
            return Inertia::render('Guest/PaymentResult', [
                'status' => 'success',
                'message' => __('general.payment_already_paid', ['default' => 'This payment link has already been paid.'])
            ]);
        }

        return Inertia::render('Guest/PaymentLinkShow', [
            'paymentLink' => $paymentLink,
            'pay_url' => route('guest.payment-links.pay', ['uuid' => $paymentLink->uuid])
        ]);
    }

    public function initiatePay(Request $request, $uuid)
    {
        $paymentLink = PaymentLink::with('currency')->where('uuid', $uuid)->firstOrFail();

        if ($paymentLink->status === 'paid') {
            return redirect()->back()->with('error', __('general.payment_already_paid', ['default' => 'This payment link has already been paid.']));
        }

        $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_email' => 'required|email|max:255',
        ]);

        $amount = (float) $paymentLink->amount;
        if ($amount <= 0) {
            return redirect()->back()->with('error', __('general.invoice_total_zero'));
        }

        $currency = $paymentLink->currency ? $paymentLink->currency->code : 'EGP';

        $paymentUrl = KashierHelper::buildPaymentLinkUrl(
            $amount,
            $paymentLink->id,
            $paymentLink->user_id,
            $request->input('guest_name'),
            $request->input('guest_email'),
            $currency
        );

        return Inertia::location($paymentUrl);
    }

    public function paymentSuccess(Request $request)
    {
        return Inertia::render('Guest/PaymentResult', [
            'status' => 'success',
            'message' => __('general.payment_successful_thank_you')
        ]);
    }

    public function paymentFailure(Request $request)
    {
        return Inertia::render('Guest/PaymentResult', [
            'status' => 'error',
            'message' => __('general.payment_failed_please_try_again')
        ]);
    }

    public function paymentWebhook(Request $request)
    {
        if (!KashierHelper::validatePayload()) {
            Log::warning('Guest Payment Link Kashier webhook: Invalid signature.');
            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 400);
        }

        $payload = json_decode($request->getContent(), true);
        if (!$payload || !isset($payload['data'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload format'], 400);
        }

        $data = $payload['data'];
        $metaData = json_decode($data['metaData'] ?? '{}', true);

        if (($metaData['source'] ?? '') !== 'payment-link') {
            return response()->json(['status' => 'ignored', 'message' => 'Not a payment link payment']);
        }

        $paymentLinkId = $metaData['payment_link_id'] ?? null;
        if (!$paymentLinkId) {
            return response()->json(['status' => 'error', 'message' => 'Missing payment link ID'], 400);
        }

        $paymentLink = PaymentLink::find($paymentLinkId);
        if (!$paymentLink) {
            return response()->json(['status' => 'error', 'message' => 'Payment link not found'], 404);
        }

        if ($data['status'] === 'SUCCESS') {
            if ($paymentLink->status !== 'paid') {
                try {
                    $paymentLink->status = 'paid';
                    $paymentLink->paid_at = now();
                    $paymentLink->save();
                    Log::info("Guest Payment Link successful for link #{$paymentLink->id}");
                } catch (\Exception $e) {
                    Log::error("Guest Payment Link failed to mark as paid for link #{$paymentLink->id}: " . $e->getMessage());
                    return response()->json(['status' => 'error', 'message' => 'Failed to process payment internally'], 500);
                }
            }
        } else {
            Log::info("Guest Payment Link failed for link #{$paymentLink->id}, Status: " . $data['status']);
        }

        return response()->json(['status' => 'success']);
    }
}
