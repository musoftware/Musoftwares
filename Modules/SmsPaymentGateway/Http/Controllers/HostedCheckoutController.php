<?php

namespace Modules\SmsPaymentGateway\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Modules\SmsPaymentGateway\Models\SmsGatewayCheckoutSession;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewaySetting;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayWebhook;

class HostedCheckoutController extends Controller
{
    /**
     * Show the hosted checkout page for a session.
     * GET /pay/{sessionId}
     * Public — no auth required, session ID acts as the token.
     */
    public function show(string $sessionId)
    {
        $session = SmsGatewayCheckoutSession::where('session_id', $sessionId)
            ->with('currency', 'user')
            ->first();

        if (!$session) {
            abort(404);
        }

        // Auto-expire if past expiry
        if ($session->status === 'open' && $session->isExpired()) {
            $session->markExpired();
        }

        // If already completed, show success
        if ($session->isComplete()) {
            return view('sms-payment-gateway::checkout.hosted', [
                'session' => $session,
                'state' => 'success',
                'merchantName' => $session->user->name ?? '',
                'amount' => $session->amount,
                'currency' => $session->currency->code ?? 'EGP',
                'walletNumbers' => [],
                'paymentMethods' => [],
            ]);
        }

        // If expired, show expired state
        if ($session->isExpired()) {
            return view('sms-payment-gateway::checkout.hosted', [
                'session' => $session,
                'state' => 'expired',
                'merchantName' => $session->user->name ?? '',
                'amount' => $session->amount,
                'currency' => $session->currency->code ?? 'EGP',
                'walletNumbers' => [],
                'paymentMethods' => [],
            ]);
        }

        // Load merchant settings for wallet numbers
        $settings = SmsPaymentGatewaySetting::where('user_id', $session->user_id)->first();
        $paymentMethodTypes = $session->payment_method_types ?? ['vodafone_cash', 'instapay'];

        $walletNumbers = [];
        $paymentMethods = [];

        $defaultPhone = $settings->wallet_phone_number ?? '';

        if (in_array('instapay', $paymentMethodTypes) && ($settings?->is_instapay_enabled ?? true)) {
            $walletNumbers['instapay'] = $settings->instapay_phone_number ?? $defaultPhone;
            $paymentMethods[] = [
                'id' => 'instapay',
                'name' => 'InstaPay',
                'icon' => asset('assets/images/gateways/instapay.png'),
                'phone' => $walletNumbers['instapay'],
            ];
        }

        if (in_array('vodafone_cash', $paymentMethodTypes) && ($settings?->is_vodafone_cash_enabled ?? true)) {
            $walletNumbers['vodafone_cash'] = $settings->vodafone_cash_phone_number ?? $defaultPhone;
            $paymentMethods[] = [
                'id' => 'vodafone_cash',
                'name' => __('sms_gateway.vodafone_cash'),
                'icon' => asset('assets/images/gateways/vodafone-cash.svg'),
                'phone' => $walletNumbers['vodafone_cash'],
            ];
        }

        return view('sms-payment-gateway::checkout.hosted', [
            'session' => $session,
            'state' => 'open',
            'merchantName' => $session->user->name ?? '',
            'amount' => $session->amount,
            'currency' => $session->currency->code ?? 'EGP',
            'walletNumbers' => $walletNumbers,
            'paymentMethods' => $paymentMethods,
            'sessionId' => $session->session_id,
            'cancelUrl' => $session->cancel_url,
            'verifyUrl' => url('/pay/' . $session->session_id . '/verify'),
            'statusUrl' => url('/pay/' . $session->session_id . '/status'),
            'expiresAt' => $session->expires_at?->toIso8601String(),
        ]);
    }

    /**
     * Customer submits their transaction reference to verify payment.
     * POST /pay/{sessionId}/verify
     */
    public function verify(Request $request, string $sessionId)
    {
        $request->validate([
            'transaction_reference' => 'required|string|max:255',
            'payment_method' => 'nullable|string|max:50',
        ]);

        $session = SmsGatewayCheckoutSession::where('session_id', $sessionId)
            ->where('status', 'open')
            ->with('user', 'currency')
            ->first();

        if (!$session) {
            return response()->json([
                'success' => false,
                'message' => __('sms_gateway.session_not_found_or_expired'),
            ], 404);
        }

        if ($session->isExpired()) {
            $session->markExpired();
            return response()->json([
                'success' => false,
                'message' => __('sms_gateway.session_expired'),
            ], 410);
        }

        $reference = trim($request->transaction_reference);

        // Try to match by reference number or phone number
        $transaction = SmsPaymentGatewayTransaction::where('user_id', $session->user_id)
            ->where('amount', '>=', $session->amount * 0.99)
            ->where('amount', '<=', $session->amount * 1.01)
            ->whereIn('status', ['pending', 'unmatched'])
            ->where('created_at', '>=', now()->subHours(24))
            ->where(function ($q) use ($reference) {
                $q->where('reference_number', $reference)
                  ->orWhere('phone_number', 'LIKE', '%' . substr(preg_replace('/[^0-9]/', '', $reference), -8) . '%');
            })
            ->whereNull('order_id')
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => true,
                'paid' => false,
                'message' => __('sms_gateway.payment_not_found_yet'),
            ]);
        }

        // Match found — complete the session
        DB::transaction(function () use ($session, $transaction, $reference) {
            $session->markComplete($transaction->id, $reference);

            $transaction->update([
                'status' => 'matched',
                'order_id' => null, // legacy field — we use checkout sessions now
            ]);
        });

        Log::info('Checkout session payment verified', [
            'session_id' => $session->session_id,
            'transaction_id' => $transaction->id,
            'amount' => $transaction->amount,
        ]);

        // Dispatch webhook
        $this->dispatchCheckoutWebhook($session->fresh(['currency']));

        return response()->json([
            'success' => true,
            'paid' => true,
            'redirect_url' => $session->getSuccessRedirectUrl(),
            'transaction_id' => (string) $transaction->id,
        ]);
    }

    /**
     * Lightweight status poll for the checkout page.
     * GET /pay/{sessionId}/status
     */
    public function status(string $sessionId)
    {
        $session = SmsGatewayCheckoutSession::where('session_id', $sessionId)->first();

        if (!$session) {
            return response()->json(['status' => 'not_found'], 404);
        }

        if ($session->status === 'open' && $session->isExpired()) {
            $session->markExpired();
        }

        $response = [
            'status' => $session->status,
        ];

        if ($session->isComplete()) {
            $response['redirect_url'] = $session->getSuccessRedirectUrl();
            $response['completed_at'] = $session->completed_at?->toIso8601String();
        }

        return response()->json($response);
    }

    /**
     * Dispatch webhook for completed checkout session.
     */
    protected function dispatchCheckoutWebhook(SmsGatewayCheckoutSession $session): void
    {
        // Per-session webhook URL takes priority
        $webhookUrl = $session->webhook_url;

        // Fallback to user's global webhook
        if (!$webhookUrl) {
            $webhook = SmsPaymentGatewayWebhook::where('user_id', $session->user_id)
                ->where('is_active', true)
                ->first();
            $webhookUrl = $webhook?->webhook_url;
        }

        if (!$webhookUrl) {
            return;
        }

        $payload = [
            'id' => 'evt_' . \Illuminate\Support\Str::random(24),
            'type' => 'checkout.session.completed',
            'data' => [
                'session_id' => $session->session_id,
                'amount' => (float) $session->amount,
                'currency' => $session->currency->code ?? null,
                'status' => 'complete',
                'transaction_reference' => $session->transaction_reference,
                'customer_name' => $session->customer_name,
                'customer_email' => $session->customer_email,
                'customer_phone' => $session->customer_phone,
                'metadata' => $session->metadata ?? new \stdClass(),
                'completed_at' => $session->completed_at?->toIso8601String(),
            ],
            'created_at' => now()->toIso8601String(),
        ];

        $timestamp = (string) time();
        $payloadJson = json_encode($payload);

        // Get webhook secret
        $webhook = SmsPaymentGatewayWebhook::where('user_id', $session->user_id)
            ->where('is_active', true)
            ->first();
        $secret = $webhook->webhook_secret ?? '';
        $signature = hash_hmac('sha256', $timestamp . '.' . $payloadJson, $secret);

        try {
            Http::timeout(10)
                ->withHeaders([
                    'X-SmsPay-Signature' => 'sha256=' . $signature,
                    'X-SmsPay-Timestamp' => $timestamp,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'SmsPay-Webhook/1.0',
                ])
                ->post($webhookUrl, $payload);

            if ($webhook) {
                $webhook->increment('success_count');
                $webhook->update(['last_triggered_at' => now()]);
            }
        } catch (\Exception $e) {
            Log::error('Checkout webhook dispatch failed', [
                'session_id' => $session->session_id,
                'webhook_url' => $webhookUrl,
                'error' => $e->getMessage(),
            ]);

            if ($webhook) {
                $webhook->increment('failure_count');
            }
        }
    }
}
