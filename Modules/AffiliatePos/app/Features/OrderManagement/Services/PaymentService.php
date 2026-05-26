<?php

namespace Modules\AffiliatePos\app\Features\OrderManagement\Services;

use Modules\AffiliatePos\Models\PaymentMethod;
use Modules\AffiliatePos\Models\PaymentRequest;
use Modules\AffiliatePos\Models\Transaction;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function requestPayout($userId, $tenantId, $paymentMethodId, $amount)
    {
        return DB::transaction(function () use ($userId, $tenantId, $paymentMethodId, $amount) {
            $paymentRequest = PaymentRequest::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'payment_method_id' => $paymentMethodId,
                'amount' => $amount,
                'status' => 'pending'
            ]);

            Transaction::create([
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'relation_type' => PaymentRequest::class,
                'relation_id' => $paymentRequest->id,
                'type' => 'withdraw',
                'amount' => -$amount,
                'notes' => 'Payout Request'
            ]);

            return $paymentRequest;
        });
    }

    public function processPayout(PaymentRequest $paymentRequest, $status)
    {
        return DB::transaction(function () use ($paymentRequest, $status) {
            $paymentRequest->update(['status' => $status]);

            if ($status === 'declined') {
                // Reverse the withdrawal
                Transaction::create([
                    'tenant_id' => $paymentRequest->tenant_id,
                    'user_id' => $paymentRequest->user_id,
                    'relation_type' => PaymentRequest::class,
                    'relation_id' => $paymentRequest->id,
                    'type' => 'withdraw_declined',
                    'amount' => abs($paymentRequest->amount), // Return money
                    'notes' => 'Payout Request Declined'
                ]);
            }

            return $paymentRequest;
        });
    }
}
