<?php

namespace Modules\SmsPaymentGateway\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayWebhook;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class SmsPaymentGatewayWebhookController extends Controller
{
    /**
     * Register a webhook URL for the authenticated user
     * POST /api/sms-payment-gateway/webhooks
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'required|url|max:500',
            'webhook_secret' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::user();

        // Check if user already has an active webhook
        $existingWebhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($existingWebhook) {
            // Update existing webhook
            $existingWebhook->update([
                'webhook_url' => $request->webhook_url,
                'webhook_secret' => $request->webhook_secret ?? $existingWebhook->webhook_secret ?? Str::random(32),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Webhook updated successfully',
                'data' => [
                    'webhook_id' => $existingWebhook->id,
                    'webhook_url' => $existingWebhook->webhook_url,
                    'is_active' => $existingWebhook->is_active,
                ]
            ]);
        }

        // Create new webhook
        $webhook = SmsPaymentGatewayWebhook::create([
            'user_id' => $user->id,
            'webhook_url' => $request->webhook_url,
            'webhook_secret' => $request->webhook_secret ?? Str::random(32),
            'is_active' => true,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook registered successfully',
            'data' => [
                'webhook_id' => $webhook->id,
                'webhook_url' => $webhook->webhook_url,
                'is_active' => $webhook->is_active,
            ]
        ], 201);
    }

    /**
     * Get webhook configuration for the authenticated user
     * GET /api/sms-payment-gateway/webhooks
     */
    public function show(Request $request)
    {
        $user = Auth::user();

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$webhook) {
            return response()->json([
                'status' => 'error',
                'message' => 'No webhook configured'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'webhook_id' => $webhook->id,
                'webhook_url' => $webhook->webhook_url,
                'is_active' => $webhook->is_active,
                'success_count' => $webhook->success_count,
                'failure_count' => $webhook->failure_count,
                'last_triggered_at' => $webhook->last_triggered_at?->toIso8601String(),
                'created_at' => $webhook->created_at->toIso8601String(),
                'updated_at' => $webhook->updated_at->toIso8601String(),
            ]
        ]);
    }

    /**
     * Update webhook configuration
     * PUT /api/sms-payment-gateway/webhooks/{id}
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'webhook_url' => 'sometimes|required|url|max:500',
            'webhook_secret' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 400);
        }

        $user = Auth::user();

        $webhook = SmsPaymentGatewayWebhook::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$webhook) {
            return response()->json([
                'status' => 'error',
                'message' => 'Webhook not found'
            ], 404);
        }

        $updateData = [];
        if ($request->has('webhook_url')) {
            $updateData['webhook_url'] = $request->webhook_url;
        }
        if ($request->has('webhook_secret')) {
            $updateData['webhook_secret'] = $request->webhook_secret;
        }
        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->is_active;
        }

        $webhook->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook updated successfully',
            'data' => [
                'webhook_id' => $webhook->id,
                'webhook_url' => $webhook->webhook_url,
                'is_active' => $webhook->is_active,
            ]
        ]);
    }

    /**
     * Delete webhook
     * DELETE /api/sms-payment-gateway/webhooks/{id}
     */
    public function destroy($id)
    {
        $user = Auth::user();

        $webhook = SmsPaymentGatewayWebhook::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$webhook) {
            return response()->json([
                'status' => 'error',
                'message' => 'Webhook not found'
            ], 404);
        }

        $webhook->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Webhook deleted successfully'
        ]);
    }

    /**
     * Test webhook (send a test payload)
     * POST /api/sms-payment-gateway/webhooks/test
     */
    public function test(Request $request)
    {
        $user = Auth::user();

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$webhook) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active webhook configured'
            ], 404);
        }

        // Send test webhook
        $testPayload = [
            'event' => 'test',
            'message' => 'This is a test webhook from AutoSMS Payment Hub',
            'timestamp' => now()->toIso8601String(),
        ];

        $result = $this->sendWebhook($webhook, $testPayload);

        return response()->json([
            'status' => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
            'data' => $result['data'] ?? null,
        ], $result['success'] ? 200 : 500);
    }

    /**
     * Send webhook to user's configured URL
     * This is called internally when a transaction is detected
     */
    public static function sendTransactionWebhook(SmsPaymentGatewayTransaction $transaction)
    {
        $user = $transaction->user;

        if (!$user) {
            return;
        }

        $webhook = SmsPaymentGatewayWebhook::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if (!$webhook) {
            return;
        }

        $payload = [
            'event' => 'transaction.detected',
            'transaction_id' => (string) $transaction->id,
            'device_id' => (string) $transaction->device_id,
            'amount' => (float) $transaction->amount,
            'balance' => (float) $transaction->balance,
            'currency' => $transaction->currency,
            'reference_number' => $transaction->reference_number,
            'sender' => $transaction->sender,
            'phone_number' => $transaction->phone_number,
            'sender_name' => $transaction->sender_name,
            'transaction_date' => $transaction->transaction_date?->toIso8601String(),
            'status' => $transaction->status,
            'sms_message' => $transaction->sms_message,
            'order_id' => $transaction->metadata['order_id'] ?? null,
            'timestamp' => now()->toIso8601String(),
        ];

        $controller = new self();
        $result = $controller->sendWebhook($webhook, $payload);

        // Update webhook statistics
        if ($result['success']) {
            $webhook->increment('success_count');
        } else {
            $webhook->increment('failure_count');
        }
        $webhook->update(['last_triggered_at' => now()]);

        return $result;
    }

    /**
     * Send webhook HTTP request
     */
    private function sendWebhook(SmsPaymentGatewayWebhook $webhook, array $payload)
    {
        try {
            // Generate webhook signature
            $payloadJson = json_encode($payload);
            $signature = hash_hmac('sha256', $payloadJson, $webhook->webhook_secret);

            // Send webhook
            $response = Http::withHeaders([
                'X-AutoSMS-Signature' => $signature,
                'X-AutoSMS-Event' => $payload['event'] ?? 'unknown',
                'Content-Type' => 'application/json',
                'User-Agent' => 'AutoSMS-Payment-Hub/1.0',
            ])->timeout(10)->post($webhook->webhook_url, $payload);

            $statusCode = $response->status();
            $responseBody = $response->body();

            $success = $statusCode >= 200 && $statusCode < 300;

            Log::info('AutoSMS Webhook sent', [
                'webhook_id' => $webhook->id,
                'user_id' => $webhook->user_id,
                'webhook_url' => $webhook->webhook_url,
                'status_code' => $statusCode,
                'success' => $success,
                'response_body' => $responseBody,
            ]);

            return [
                'success' => $success,
                'message' => $success ? 'Webhook sent successfully' : 'Webhook delivery failed',
                'data' => [
                    'status_code' => $statusCode,
                    'response' => $responseBody,
                ]
            ];

        } catch (\Exception $e) {
            Log::error('AutoSMS Webhook delivery failed', [
                'webhook_id' => $webhook->id,
                'user_id' => $webhook->user_id,
                'webhook_url' => $webhook->webhook_url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Webhook delivery failed: ' . $e->getMessage(),
            ];
        }
    }
}

