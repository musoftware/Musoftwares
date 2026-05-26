<?php

namespace Modules\PaymentGateway\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\PaymentGateway\Models\GatewayClient;
use Modules\PaymentGateway\Models\GatewayPayment;
use Modules\PaymentGateway\Services\PaymentGatewayService;
use Modules\PaymentGateway\Http\Requests\Api\InitiatePaymentRequest;
use Modules\PaymentGateway\Http\Resources\GatewayPaymentResource;
use App\Helpers\KashierHelper;

class PaymentGatewayApiController extends Controller
{
    public function __construct(
        protected PaymentGatewayService $service
    ) {}

    // ─── Middleware Helper ────────────────────────────────────────────────────

    /**
     * Authenticate a client from Authorization header.
     * Expected: Authorization: Basic base64(client_id:client_secret)
     */
    private function resolveClient(Request $request): ?GatewayClient
    {
        $clientId     = $request->header('X-Client-Id') ?? $request->input('client_id');
        $clientSecret = $request->header('X-Client-Secret') ?? $request->input('client_secret');

        if (!$clientId || !$clientSecret) {
            // Try Basic Auth as fallback
            $clientId     = $request->getUser();
            $clientSecret = $request->getPassword();
        }

        if (!$clientId || !$clientSecret) {
            return null;
        }

        return $this->service->authenticateClient($clientId, $clientSecret);
    }

    // ─── Endpoints ───────────────────────────────────────────────────────────

    /**
     * POST /api/payment-gateway/initiate
     *
     * Merchant sends payment details, we return a Kashier redirect URL.
     *
     * Headers:
     *   X-Client-Id:     pgw_xxxx
     *   X-Client-Secret: sk_xxxx
     *
     * Body:
     * {
     *   "order_id":    "ORDER_123",
     *   "amount":      150.00,
     *   "currency":    "EGP",
     *   "description": "Shirt × 2",
     *   "success_url": "https://mysite.com/success",
     *   "failure_url": "https://mysite.com/failure",
     *   "webhook_url": "https://mysite.com/webhooks/payment",
     *   "customer": {
     *     "name": "Ahmed Ali",
     *     "email": "ahmed@example.com",
     *     "phone": "01012345678"
     *   }
     * }
     *
     * Response:
     * {
     *   "payment_url": "https://payments.kashier.io/?...",
     *   "order_id":    "pgw_xxxxx",
     *   "expires_in":  3600
     * }
     */
    public function initiate(InitiatePaymentRequest $request)
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return response()->json([
                'success' => false,
                'error'   => 'Invalid credentials. Check your X-Client-Id and X-Client-Secret headers.',
            ], 401);
        }

        try {
            $payment = $this->service->initiatePayment($client, $request->validated());

            return response()->json([
                'success'     => true,
                'payment_url' => $payment->kashier_payment_url,
                'order_id'    => $payment->internal_order_id,
                'amount'      => (float) $payment->amount,
                'currency'    => $payment->currency,
                'expires_in'  => 3600,
            ]);
        } catch (\Exception $e) {
            Log::error('[PaymentGateway API] initiate error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error'   => 'Payment initiation failed. Please try again.',
            ], 500);
        }
    }

    /**
     * GET /api/payment-gateway/status/{order_id}
     *
     * Check payment status by internal or external order ID.
     */
    public function status(Request $request, string $orderId)
    {
        $client = $this->resolveClient($request);

        if (!$client) {
            return response()->json(['success' => false, 'error' => 'Invalid credentials.'], 401);
        }

        $payment = GatewayPayment::where('client_id', $client->id)
            ->where(function ($q) use ($orderId) {
                $q->where('internal_order_id', $orderId)
                  ->orWhere('external_order_id', $orderId);
            })
            ->first();

        if (!$payment) {
            return response()->json(['success' => false, 'error' => 'Payment not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'payment' => (new GatewayPaymentResource($payment))->resolve(),
        ]);
    }

    // ─── Kashier Webhook (called by Kashier servers) ──────────────────────────

    /**
     * POST /api/payment-gateway/webhook/kashier
     *
     * Kashier calls this after every payment attempt.
     * Validates Kashier signature → updates payment → fires merchant webhook.
     */
    public function kashierWebhook(Request $request)
    {
        Log::info('[PaymentGateway] Kashier webhook received', $request->all());

        if (!KashierHelper::validatePayload()) {
            Log::warning('[PaymentGateway] Invalid Kashier webhook signature');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $data = $request->input('data', []);

        $processed = $this->service->processKashierWebhook($data);

        return response()->json(['status' => $processed ? 'processed' : 'ignored']);
    }

    /**
     * GET /api/payment-gateway/webhook/success/{internalOrderId}
     *
     * Kashier redirects the customer here after a successful payment.
     * We update status then redirect to merchant's success_url.
     */
    public function kashierSuccess(Request $request, string $internalOrderId)
    {
        $payment = GatewayPayment::where('internal_order_id', $internalOrderId)->first();

        if (!$payment) {
            abort(404);
        }

        // Status will be confirmed by server webhook — just redirect to merchant
        $redirectUrl = $payment->success_url
            . '?order_id=' . $payment->external_order_id
            . '&status=success';

        return redirect()->away($redirectUrl);
    }

    /**
     * GET /api/payment-gateway/webhook/failure/{internalOrderId}
     *
     * Kashier redirects the customer here after a failed payment.
     */
    public function kashierFailure(Request $request, string $internalOrderId)
    {
        $payment = GatewayPayment::where('internal_order_id', $internalOrderId)->first();

        if ($payment && $payment->isPending()) {
            $payment->update(['status' => 'failed']);
        }

        $redirectUrl = ($payment?->failure_url ?? $payment?->success_url ?? '/')
            . '?order_id=' . ($payment?->external_order_id ?? '')
            . '&status=failed';

        return redirect()->away($redirectUrl);
    }
}
