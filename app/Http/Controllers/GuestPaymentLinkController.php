<?php

namespace App\Http\Controllers;

use App\Builders\KashierCheckoutBuilder;
use App\Jobs\ProcessWebhookJob;
use App\Models\IncomingWebhook;
use App\Models\PaymentLink;
use App\Traits\ConvertsCurrency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class GuestPaymentLinkController extends Controller
{
    use ConvertsCurrency;

    public function show(Request $request, $uuid)
    {
        $paymentLink = PaymentLink::with('currency')->where('uuid', $uuid)->firstOrFail();

        if ($paymentLink->status === 'paid') {
            return Inertia::render('Guest/PaymentResult', [
                'status' => 'success',
                'message' => __('general.payment_already_paid', ['default' => 'This payment link has already been paid.']),
            ]);
        }

        return Inertia::render('Guest/PaymentLinkShow', [
            'paymentLink' => $paymentLink,
            'pay_url' => route('guest.payment-links.pay', ['uuid' => $paymentLink->uuid]),
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

        if (! $paymentLink->currency) {
            throw new \Exception("Payment link {$paymentLink->id} is missing an associated currency relation.");
        }
        $currency = $paymentLink->currency->currency;

        $paymentUrl = KashierCheckoutBuilder::make()
            ->forAmount($amount, $currency)
            ->forGuest($request->input('guest_name'), $request->input('guest_email'), 'guest_plnk_'.$paymentLink->uuid)
            ->withSource('payment-link', 'plnk_')
            ->withMetadata([
                'payment_link_id' => $paymentLink->id,
                'user_id' => $paymentLink->user_id,
            ])
            ->withRoutes(
                success: route('guest.payment-links.success'),
                failure: route('guest.payment-links.failure'),
                webhook: route('guest.payment-links.webhook')
            )
            ->build();

        return Inertia::location($paymentUrl);
    }

    public function paymentSuccess(Request $request)
    {
        return Inertia::render('Guest/PaymentResult', [
            'status' => 'success',
            'message' => __('general.payment_successful_thank_you'),
        ]);
    }

    public function paymentFailure(Request $request)
    {
        return Inertia::render('Guest/PaymentResult', [
            'status' => 'error',
            'message' => __('general.payment_failed_please_try_again'),
        ]);
    }

    public function paymentWebhook(Request $request)
    {
        $payload = json_decode($request->getContent(), true);
        if (! $payload || ! isset($payload['data'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload format'], 400);
        }

        $metaData = json_decode($payload['data']['metaData'] ?? '{}', true);
        if (($metaData['source'] ?? '') !== 'payment-link') {
            return response()->json(['status' => 'ignored', 'message' => 'Not a payment link payment']);
        }

        try {
            $webhook = IncomingWebhook::create([
                'source' => 'kashier',
                'event_type' => $payload['data']['status'] ?? 'unknown',
                'payload' => $payload,
                'headers' => $request->headers->all(),
                'status' => 'pending',
            ]);

            ProcessWebhookJob::dispatch($webhook);

            return response()->json(['status' => 'accepted', 'id' => $webhook->id], 202);
        } catch (\Exception $e) {
            Log::error('Guest Payment Link webhook store failed: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => 'Internal error'], 500);
        }
    }
}