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
    public function show($uuid)
    {
        $order = \App\Models\PaymentOrder::where('uuid', $uuid)->first();

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

        $isEtisalatVodafone = false;
        if ($settings && $settings->vodafone_cash_allowed_sender) {
            $isEtisalatVodafone = stripos($settings->vodafone_cash_allowed_sender, 'EtisalatCash') !== false || stripos($settings->vodafone_cash_allowed_sender, 'e& money') !== false;
        }
        if (!$isEtisalatVodafone && $this->isPhoneEtisalatCash($user->id, $vodafonePhone)) {
            $isEtisalatVodafone = true;
        }

        $isEtisalatInstapay = false;
        if ($settings && $settings->instapay_allowed_sender) {
            $isEtisalatInstapay = stripos($settings->instapay_allowed_sender, 'EtisalatCash') !== false || stripos($settings->instapay_allowed_sender, 'e& money') !== false;
        }
        if (!$isEtisalatInstapay && $this->isPhoneEtisalatCash($user->id, $instapayPhone)) {
            $isEtisalatInstapay = true;
        }

        return view('sms-payment-gateway::widget.iframe', [
            'amount' => $order->amount,
            'reference' => $order->metadata['order_number'] ?? ('ORD-' . $order->id),
            'phone' => $phone,
            'instapayPhone' => $instapayPhone,
            'vodafonePhone' => $vodafonePhone,
            'isInstapay' => $isInstapay,
            'isVodafone' => $isVodafone,
            'isEtisalatInstapay' => $isEtisalatInstapay,
            'isEtisalatVodafone' => $isEtisalatVodafone,
            'redirectUrl' => route('sms-payment-gateway.widget.status', ['uuid' => $order->uuid]),
            'verifyUrl' => route('sms-payment-gateway.widget.verify', ['uuid' => $order->uuid]),
            'merchantName' => $user->name,
            'order_number' => $order->metadata['order_number'] ?? ('ORD-' . $order->id),
            'currency' => $order->currency->code ?? 'EGP',
            'status' => $order->status,
        ]);
    }

    /**
     * Poll for transaction status
     */
    public function status($uuid)
    {
        $order = \App\Models\PaymentOrder::where('uuid', $uuid)->first();

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
    public function verify(Request $request, $uuid)
    {
        $request->validate([
            'transaction_id' => 'required|string',
        ]);

        $order = \App\Models\PaymentOrder::where('uuid', $uuid)->first();

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

    private function isPhoneEtisalatCash($userId, $phone)
    {
        if (empty($phone)) return false;
        
        $numericPhone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($numericPhone) < 8) return false;
        $searchPhone = substr($numericPhone, -8);

        $device = \Modules\SmsPaymentGateway\Models\SmsPaymentGatewayDevice::where('user_id', $userId)
            ->where(function($q) use ($searchPhone) {
                $q->where('sim1_number', 'LIKE', '%' . $searchPhone . '%')
                  ->orWhere('sim2_number', 'LIKE', '%' . $searchPhone . '%');
            })->first();

        if ($device) {
            $sim1Match = strpos(preg_replace('/[^0-9]/', '', $device->sim1_number ?? ''), $searchPhone) !== false;
            $sim2Match = strpos(preg_replace('/[^0-9]/', '', $device->sim2_number ?? ''), $searchPhone) !== false;
            
            $metadata = $device->metadata ?? [];
            if ($sim1Match && !empty($metadata['sim1_configs']) && is_array($metadata['sim1_configs'])) {
                foreach ($metadata['sim1_configs'] as $config) {
                    if (isset($config['allowed_sender'])) {
                        $sender = $config['allowed_sender'];
                        if (stripos($sender, 'Etisalat') !== false || stripos($sender, 'e&') !== false) return true;
                    }
                }
            }
            if ($sim2Match && !empty($metadata['sim2_configs']) && is_array($metadata['sim2_configs'])) {
                foreach ($metadata['sim2_configs'] as $config) {
                    if (isset($config['allowed_sender'])) {
                        $sender = $config['allowed_sender'];
                        if (stripos($sender, 'Etisalat') !== false || stripos($sender, 'e&') !== false) return true;
                    }
                }
            }
        }
        return false;
    }
}
