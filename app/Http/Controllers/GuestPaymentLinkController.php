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
        $paymentLink = PaymentLink::with(['currency', 'client'])->where('uuid', $uuid)->firstOrFail();

        if ($paymentLink->status === 'paid') {
            return Inertia::render('Guest/PaymentResult', [
                'status' => 'success',
                'message' => __('general.payment_already_paid', ['default' => 'This payment link has already been paid.']),
                'continue_url' => $paymentLink->metadata['return_url'] ?? null,
            ]);
        }

        return Inertia::render('Guest/PaymentLinkShow', [
            'paymentLink' => $paymentLink->makeHidden(['client', 'metadata']),
            'pay_url' => route('guest.payment-links.pay', ['uuid' => $paymentLink->uuid]),
            'prefill' => [
                'name' => $paymentLink->client?->name,
                'email' => $paymentLink->client?->email,
            ],
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

        // Plan links activate an account by this email after payment (see PlanPaymentLinkService).
        $paymentLink->forceFill(['metadata' => array_merge($paymentLink->metadata ?? [], [
            'payer' => ['name' => $request->input('guest_name'), 'email' => $request->input('guest_email')],
        ])])->save();

        $paymentUrl = KashierCheckoutBuilder::make()
            ->forAmount($amount, $currency)
            ->forGuest($request->input('guest_name'), $request->input('guest_email'), 'guest_plnk_'.$paymentLink->uuid)
            ->withSource('payment-link', 'plnk_')
            ->withMetadata([
                'payment_link_id' => $paymentLink->id,
                'user_id' => $paymentLink->user_id,
            ])
            ->withRoutes(
                success: route('guest.payment-links.success', ['link' => $paymentLink->uuid]),
                failure: route('guest.payment-links.failure'),
                webhook: route('guest.payment-links.webhook')
            )
            ->build();

        return Inertia::location($paymentUrl);
    }

    public function paymentSuccess(Request $request)
    {
        $link = $request->filled('link')
            ? PaymentLink::where('uuid', (string) $request->query('link'))->first()
            : null;

        return Inertia::render('Guest/PaymentResult', [
            'status' => 'success',
            'message' => __('general.payment_successful_thank_you'),
            'continue_url' => $link?->metadata['return_url'] ?? null,
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
        $payload = json_decode($request->getContent(), true) ?: $request->all();
        if (! $payload || ! isset($payload['data'])) {
            return response()->json(['status' => 'error', 'message' => 'Invalid payload format'], 400);
        }

        $data = $payload['data'];
        $metaData = $data['metaData'] ?? [];
        if (is_string($metaData)) {
            $metaData = json_decode($metaData, true) ?: [];
        }

        $source = $metaData['source'] ?? null;
        $merchantOrderId = $data['merchantOrderId'] ?? '';
        if (! $source && $merchantOrderId) {
            if (str_starts_with($merchantOrderId, 'plnk_')) {
                $source = 'payment-link';
            }
        }

        if ($source && $source !== 'payment-link') {
            return response()->json(['status' => 'ignored', 'message' => 'Not a payment link payment']);
        }

        try {
            $webhook = IncomingWebhook::create([
                'source' => 'kashier',
                'event_type' => $data['status'] ?? 'unknown',
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
