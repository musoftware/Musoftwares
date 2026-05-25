<?php

namespace Modules\SmsPaymentGateway\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\SmsPaymentGateway\Models\SmsPaymentGatewayTransaction;

class WidgetController extends Controller
{
    /**
     * Show the payment widget iframe
     */
    public function show(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'reference' => 'nullable|string',
            'phone' => 'nullable|string',
            'redirect_url' => 'nullable|url',
        ]);

        $user = User::where('sms-payment-gateway_verification_secret', $request->key)->first();

        if (!$user) {
            abort(404, 'Invalid payment gateway key.');
        }

        if (!$request->reference && !$request->phone) {
            abort(400, 'Either a reference number or phone number is required.');
        }

        return view('sms-payment-gateway::widget.iframe', [
            'amount' => $request->amount,
            'reference' => $request->reference,
            'phone' => $request->phone,
            'redirectUrl' => $request->redirect_url,
            'pollingUrl' => route('sms-payment-gateway.widget.status', $request->query()),
            'merchantName' => $user->name,
        ]);
    }

    /**
     * Poll for transaction status
     */
    public function status(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'amount' => 'required|numeric|min:1',
            'reference' => 'nullable|string',
            'phone' => 'nullable|string',
        ]);

        $user = User::where('sms-payment-gateway_verification_secret', $request->key)->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Invalid key'], 404);
        }

        $query = SmsPaymentGatewayTransaction::where('user_id', $user->id)
            ->where('amount', $request->amount)
            ->where('created_at', '>=', now()->subHours(24)); // Only look at recent transactions

        if ($request->reference) {
            $query->where('reference_number', $request->reference);
        }

        if ($request->phone) {
            $query->where('phone_number', $request->phone);
        }

        $transaction = $query->first();

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
            'message' => 'Waiting for payment...'
        ]);
    }
}
