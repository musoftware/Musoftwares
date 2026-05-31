<?php

namespace Modules\SmsPaymentGateway\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewaySetting;

class WidgetController extends Controller
{
    /**
     * Show the payment widget iframe
     */
    public function show($order_number)
    {
        $order = \App\Models\PaymentOrder::where('order_number', $order_number)->first();

        if (!$order) {
            abort(404, 'Invalid checkout session.');
        }

        $user = User::find($order->user_id);

        if (!$user) {
            abort(404, 'Merchant not found.');
        }

        $settings = SmsPaymentGatewaySetting::where('user_id', $user->id)->first();
        
        $phone = $settings ? $settings->wallet_phone_number : ($order->customer_phone ?? '');
        $isInstapay = $settings ? $settings->is_instapay_enabled : true;
        $isVodafone = $settings ? $settings->is_vodafone_cash_enabled : true;

        return view('sms-payment-gateway::widget.iframe', [
            'amount' => $order->total_amount,
            'reference' => $order->order_number,
            'phone' => $phone,
            'isInstapay' => $isInstapay,
            'isVodafone' => $isVodafone,
            'redirectUrl' => route('sms-payment-gateway.widget.status', ['order_number' => $order->order_number]),
            'verifyUrl' => route('sms-payment-gateway.widget.verify', ['order_number' => $order->order_number]),
            'merchantName' => $user->name,
            'order_number' => $order->order_number,
            'currency' => $order->currency ?? 'EGP',
        ]);
    }

    /**
     * Poll for transaction status
     */
    public function status($order_number)
    {
        $order = \App\Models\PaymentOrder::where('order_number', $order_number)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Invalid order'], 404);
        }

        if ($order->status === 'paid' || $order->status === 'completed') {
            return response()->json([
                'success' => true, 
                'paid' => true,
                'transaction_id' => $order->transaction_id ?? null,
                'date' => $order->paid_at ? $order->paid_at->toIso8601String() : now()->toIso8601String()
            ]);
        }

        return response()->json([
            'success' => true, 
            'paid' => false,
            'message' => 'Waiting for payment...'
        ]);
    }

    /**
     * Verify payment based on guest input
     */
    public function verify(Request $request, $order_number)
    {
        $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $order = \App\Models\PaymentOrder::where('order_number', $order_number)->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Invalid order'], 404);
        }

        // Clean up transaction ID (remove spaces, etc)
        $transactionId = trim($request->transaction_id);

        $query = SmsPaymentGatewayTransaction::where('user_id', $order->user_id)
            ->where('amount', '>=', $order->total_amount * 0.99) // Allow 1% tolerance
            ->where('amount', '<=', $order->total_amount * 1.01)
            ->where('status', 'pending')
            ->where('created_at', '>=', now()->subHours(24))
            ->where(function ($q) use ($transactionId) {
                // Check both reference_number and phone_number columns in case the user entered their phone number instead
                $q->where('reference_number', $transactionId)
                  ->orWhere('phone_number', $transactionId);
            });

        $transaction = $query->first();

        if ($transaction) {
            \Illuminate\Support\Facades\DB::transaction(function () use ($order, $transaction) {
                $order->update([
                    'status' => 'paid',
                    'payment_phone' => $transaction->phone_number,
                    'sms-payment-gateway_transaction_id' => $transaction->id,
                    'paid_at' => now(),
                ]);

                $transaction->update([
                    'order_id' => $order->id,
                    'status' => 'verified',
                ]);
            });

            return response()->json([
                'success' => true,
                'paid' => true,
                'transaction_id' => $transaction->id,
                'date' => $transaction->created_at->toIso8601String()
            ]);
        }

        return response()->json([
            'success' => true,
            'paid' => false,
            'message' => 'لم يتم العثور على التحويل بعد. يرجى التأكد من الرقم المرجعي أو الانتظار قليلاً والمحاولة مرة أخرى.'
        ]);
    }
}
