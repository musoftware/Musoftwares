<?php

namespace Modules\SmsPaymentGateway\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\SmsPaymentGateway\Models\SmsGatewayCheckoutSession;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewaySetting;
use Modules\SmsPaymentGateway\Services\WebhookDispatchService;

class CheckoutSessionController extends Controller
{
    /**
     * Create a new Checkout Session.
     * POST /api/v1/sms-gateway/checkout/sessions
     * Requires: secret key (sk_*)
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1|max:1000000',
            'currency' => 'nullable|string|max:5',
            'success_url' => 'required|url|max:1000',
            'cancel_url' => 'nullable|url|max:1000',
            'webhook_url' => 'nullable|url|max:1000',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'metadata' => 'nullable|array',
            'payment_method_types' => 'nullable|array',
            'payment_method_types.*' => 'string|in:vodafone_cash,instapay,etisalat_cash,orange_cash,we_pay,bank_transfer',
            'expires_in' => 'nullable|integer|min:5|max:1440', // minutes, default 30
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => [
                    'type' => 'invalid_request_error',
                    'message' => __('sms_gateway.validation_failed'),
                    'errors' => $validator->errors(),
                ],
            ], 422);
        }

        try {
            $user = $request->smsGatewayUser;
            $apiKey = $request->smsGatewayApiKey;
            $isTest = $request->smsGatewayIsTest;

            // Resolve currency
            $currencyCode = $request->currency ?? 'EGP';
            $currency = \App\Models\Currency::where('currency', strtoupper($currencyCode))->first();
            if (!$currency) {
                return response()->json([
                    'error' => [
                        'type' => 'invalid_request_error',
                        'message' => __('sms_gateway.invalid_currency'),
                    ],
                ], 422);
            }

            $expiresIn = $request->expires_in ?? 30;

            $session = SmsGatewayCheckoutSession::create([
                'user_id' => $user->id,
                'api_key_id' => $apiKey->id,
                'amount' => $request->amount,
                'currency_id' => $currency->id,
                'status' => 'open',
                'success_url' => $request->success_url,
                'cancel_url' => $request->cancel_url,
                'webhook_url' => $request->webhook_url,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'metadata' => $request->metadata,
                'payment_method_types' => $request->payment_method_types ?? ['vodafone_cash', 'instapay'],
                'is_test' => $isTest,
                'expires_at' => now()->addMinutes($expiresIn),
            ]);

            $session->load('currency');

            Log::info('Checkout session created', [
                'session_id' => $session->session_id,
                'user_id' => $user->id,
                'amount' => $session->amount,
                'is_test' => $isTest,
            ]);

            return response()->json($session->toApiResponse(), 201);

        } catch (\Exception $e) {
            Log::error('Failed to create checkout session', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => [
                    'type' => 'api_error',
                    'message' => __('sms_gateway.session_create_failed'),
                ],
            ], 500);
        }
    }

    /**
     * Retrieve a Checkout Session by ID.
     * GET /api/v1/sms-gateway/checkout/sessions/{sessionId}
     * Accepts: publishable or secret key
     */
    public function show(Request $request, string $sessionId)
    {
        $user = $request->smsGatewayUser;

        $session = SmsGatewayCheckoutSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json([
                'error' => [
                    'type' => 'invalid_request_error',
                    'message' => __('sms_gateway.session_not_found'),
                ],
            ], 404);
        }

        // Auto-expire if past expiry
        if ($session->status === 'open' && $session->isExpired()) {
            $session->markExpired();
        }

        $session->load('currency');

        return response()->json($session->toApiResponse());
    }

    /**
     * Force-expire an open Checkout Session.
     * POST /api/v1/sms-gateway/checkout/sessions/{sessionId}/expire
     * Requires: secret key (sk_*)
     */
    public function expire(Request $request, string $sessionId)
    {
        $user = $request->smsGatewayUser;

        $session = SmsGatewayCheckoutSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (!$session) {
            return response()->json([
                'error' => [
                    'type' => 'invalid_request_error',
                    'message' => __('sms_gateway.session_not_found_or_not_open'),
                ],
            ], 404);
        }

        $session->markExpired();
        $session->load('currency');

        Log::info('Checkout session force-expired', [
            'session_id' => $session->session_id,
            'user_id' => $user->id,
        ]);

        return response()->json($session->toApiResponse());
    }

    /**
     * Lightweight poll for session status (for JS widget).
     * GET /api/v1/sms-gateway/checkout/sessions/{sessionId}/poll
     * Accepts: publishable key (pk_*)
     */
    public function poll(Request $request, string $sessionId)
    {
        $user = $request->smsGatewayUser;

        $session = SmsGatewayCheckoutSession::where('session_id', $sessionId)
            ->where('user_id', $user->id)
            ->first();

        if (!$session) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // Auto-expire
        if ($session->status === 'open' && $session->isExpired()) {
            $session->markExpired();
        }

        return response()->json([
            'status' => $session->status,
            'completed_at' => $session->completed_at?->toIso8601String(),
        ]);
    }
}
