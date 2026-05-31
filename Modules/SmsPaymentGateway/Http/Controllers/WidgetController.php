<?php

namespace Modules\SmsPaymentGateway\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewaySetting;
use Modules\SmsPaymentGateway\Services\RealtimePaymentMatchingEngine;

class WidgetController extends Controller
{
    protected RealtimePaymentMatchingEngine $matchingEngine;

    public function __construct(RealtimePaymentMatchingEngine $matchingEngine)
    {
        $this->matchingEngine = $matchingEngine;
    }
    /**
     * Show the payment widget iframe
     */
    public function show($order_id)
    {
        $order = \App\Models\PaymentOrder::find($order_id);

        if (!$order) {
            abort(404, 'Invalid checkout session.');
        }

        $user = User::find($order->user_id);

        if (!$user) {
            abort(404, 'Merchant not found.');
        }

        $settings = SmsPaymentGatewaySetting::where('user_id', $user->id)->first();
        
        $phone = $settings ? $settings->wallet_phone_number : ($order->customer_phone ?? '');
        $instapayPhone = $settings ? ($settings->instapay_phone_number ?: $phone) : $phone;
        $vodafonePhone = $settings ? ($settings->vodafone_cash_phone_number ?: $phone) : $phone;
        $isInstapay = $settings ? $settings->is_instapay_enabled : true;
        $isVodafone = $settings ? $settings->is_vodafone_cash_enabled : true;

        return view('sms-payment-gateway::widget.iframe', [
            'amount' => $order->amount,
            'reference' => $order->metadata['order_number'] ?? ('ORD-' . $order->id),
            'phone' => $phone,
            'instapayPhone' => $instapayPhone,
            'vodafonePhone' => $vodafonePhone,
            'isInstapay' => $isInstapay,
            'isVodafone' => $isVodafone,
            'redirectUrl' => route('sms-payment-gateway.widget.status', ['order_id' => $order->id]),
            'verifyUrl' => route('sms-payment-gateway.widget.verify', ['order_id' => $order->id]),
            'merchantName' => $user->name,
            'order_number' => $order->metadata['order_number'] ?? ('ORD-' . $order->id),
            'currency' => $order->currency ?? 'EGP',
        ]);
    }

    /**
     * Poll for transaction status
     */
    public function status($order_id)
    {
        $order = \App\Models\PaymentOrder::find($order_id);

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
    public function verify(Request $request, $order_id)
    {
        $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $order = \App\Models\PaymentOrder::find($order_id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Invalid order'], 404);
        }

        // Clean up transaction ID (remove spaces, etc)
        $transactionId = trim($request->transaction_id);

        $transaction = $this->matchingEngine->manualMatch($order, $transactionId);

        if ($transaction) {
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
