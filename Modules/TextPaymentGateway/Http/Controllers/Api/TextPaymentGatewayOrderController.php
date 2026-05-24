<?php

namespace Modules\TextPaymentGateway\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PaymentOrder;
use Modules\TextPaymentGateway\Models\TextPaymentGatewayTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TextPaymentGatewayOrderController extends Controller
{
    /**
     * Create a new order with validation
     * POST /api/text-payment-gateway/orders/create
     */
    public function create(Request $request)
    {
        try {
            $user = Auth::user();

            // Validate order data
            $validator = Validator::make($request->all(), [
                'customer_name' => 'required|string|max:255',
                'customer_phone' => 'required|string|max:20',
                'customer_email' => 'nullable|email|max:255',
                'customer_address' => 'nullable|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.name' => 'required|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.sku' => 'nullable|string|max:100',
                'total_amount' => 'required|numeric|min:0.01',
                'currency' => 'nullable|string|max:3',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Calculate and verify total amount
            $calculatedTotal = collect($request->items)->sum(function ($item) {
                return $item['price'] * $item['quantity'];
            });

            if (abs($calculatedTotal - $request->total_amount) > 0.01) {
                return response()->json([
                    'success' => false,
                    'message' => 'Total amount mismatch',
                    'calculated_total' => $calculatedTotal,
                    'provided_total' => $request->total_amount
                ], 422);
            }

            // Create order (mark as test if user has test mode enabled)
            $order = PaymentOrder::create([
                'user_id' => $user->id,
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'customer_address' => $request->customer_address,
                'items' => $request->items,
                'subtotal' => $calculatedTotal,
                'tax' => 0,
                'total_amount' => $request->total_amount,
                'currency' => $request->currency ?? 'EGP',
                'status' => 'pending_payment',
                'payment_method' => 'text-payment-gateway',
                'notes' => $request->notes,
                'is_test' => $user->auto_sms_test_mode ?? false,
            ]);

            Log::info('Order created via API', [
                'order_id' => $order->id,
                'user_id' => $user->id,
                'amount' => $order->total_amount
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'customer_name' => $order->customer_name,
                    'customer_phone' => $order->customer_phone,
                    'total_amount' => $order->total_amount,
                    'currency' => $order->currency,
                    'status' => $order->status,
                    'created_at' => $order->created_at->toISOString(),
                ],
                'payment_instructions' => $this->getPaymentInstructions($order)
            ]);

        } catch (\Exception $e) {
            Log::error('Order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Verify payment for an order
     * POST /api/text-payment-gateway/orders/verify-payment
     */
    public function verifyPayment(Request $request)
    {
        try {
            $user = Auth::user();

            $validator = Validator::make($request->all(), [
                'order_id' => 'required|integer|exists:payment_orders,id',
                'phone_number' => 'required|string|max:20',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get order
            $order = PaymentOrder::where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            // Check if already paid
            if ($order->status === 'paid' || $order->status === 'completed') {
                return response()->json([
                    'success' => true,
                    'payment_verified' => true,
                    'message' => 'Order already paid',
                    'order' => $this->formatOrderResponse($order),
                    'transaction' => $order->transaction ? $this->formatTransactionResponse($order->transaction) : null
                ]);
            }

            // Clean phone number for comparison
            $cleanPhone = preg_replace('/[^0-9+]/', '', $request->phone_number);

            // Look for transaction from this phone number
            $transaction = TextPaymentGatewayTransaction::where('user_id', $user->id)
                ->where('phone_number', 'LIKE', '%' . substr($cleanPhone, -8) . '%')
                ->where('amount', '>=', $order->total_amount * 0.99) // Allow 1% tolerance
                ->where('amount', '<=', $order->total_amount * 1.01)
                ->where('status', 'pending')
                ->whereNull('order_id')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($transaction) {
                // Found matching transaction - update order and transaction
                DB::transaction(function () use ($order, $transaction) {
                    $order->update([
                        'status' => 'paid',
                        'payment_phone' => $transaction->phone_number,
                        'text-payment-gateway_transaction_id' => $transaction->id,
                        'paid_at' => now(),
                    ]);

                    $transaction->update([
                        'order_id' => $order->id,
                        'status' => 'verified',
                    ]);
                });

                Log::info('Payment verified for order', [
                    'order_id' => $order->id,
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount
                ]);

                return response()->json([
                    'success' => true,
                    'payment_verified' => true,
                    'message' => 'Payment verified successfully',
                    'order' => $this->formatOrderResponse($order->fresh()),
                    'transaction' => $this->formatTransactionResponse($transaction)
                ]);
            }

            // No transaction found
            return response()->json([
                'success' => true,
                'payment_verified' => false,
                'message' => 'No payment found yet. Please make the payment and try again.',
                'order' => $this->formatOrderResponse($order)
            ]);

        } catch (\Exception $e) {
            Log::error('Payment verification failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Payment verification failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get order status
     * GET /api/text-payment-gateway/orders/{id}
     */
    public function show($id)
    {
        try {
            $user = Auth::user();

            $order = PaymentOrder::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'order' => $this->formatOrderResponse($order, true)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel an order
     * POST /api/text-payment-gateway/orders/{id}/cancel
     */
    public function cancel($id)
    {
        try {
            $user = Auth::user();

            $order = PaymentOrder::where('id', $id)
                ->where('user_id', $user->id)
                ->whereIn('status', ['pending_payment', 'pending'])
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or cannot be cancelled'
                ], 404);
            }

            $order->update(['status' => 'cancelled']);

            Log::info('Order cancelled', [
                'order_id' => $order->id,
                'user_id' => $user->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order cancelled successfully',
                'order' => $this->formatOrderResponse($order)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List user orders
     * GET /api/text-payment-gateway/orders
     */
    public function index(Request $request)
    {
        try {
            $user = Auth::user();

            $query = PaymentOrder::where('user_id', $user->id);

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'orders' => $orders->map(function ($order) {
                    return $this->formatOrderResponse($order);
                }),
                'pagination' => [
                    'current_page' => $orders->currentPage(),
                    'last_page' => $orders->lastPage(),
                    'per_page' => $orders->perPage(),
                    'total' => $orders->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch orders: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber()
    {
        do {
            $orderNumber = 'ORD-' . strtoupper(substr(uniqid(), -8));
        } while (PaymentOrder::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    /**
     * Get payment instructions
     */
    private function getPaymentInstructions($order)
    {
        return sprintf(
            'Please transfer %s %s to the payment number using your mobile wallet from %s.',
            $order->total_amount,
            $order->currency,
            $order->customer_phone
        );
    }

    /**
     * Format order response
     */
    private function formatOrderResponse($order, $includeDetails = false)
    {
        $data = [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
            'total_amount' => (float)$order->total_amount,
            'currency' => $order->currency,
            'status' => $order->status,
            'payment_method' => $order->payment_method,
            'paid_at' => $order->paid_at?->toISOString(),
            'created_at' => $order->created_at->toISOString(),
        ];

        if ($includeDetails) {
            $data['items'] = $order->items;
            $data['customer_address'] = $order->customer_address;
            $data['notes'] = $order->notes;
            $data['subtotal'] = (float)$order->subtotal;
            $data['tax'] = (float)$order->tax;
        }

        if ($order->transaction) {
            $data['transaction'] = $this->formatTransactionResponse($order->transaction);
        }

        return $data;
    }

    /**
     * Format transaction response
     */
    private function formatTransactionResponse($transaction)
    {
        return [
            'id' => $transaction->id,
            'amount' => (float)$transaction->amount,
            'currency' => $transaction->currency,
            'phone_number' => $transaction->phone_number,
            'sender_name' => $transaction->sender_name,
            'sender' => $transaction->sender,
            'transaction_date' => $transaction->transaction_date,
            'status' => $transaction->status,
        ];
    }
}
