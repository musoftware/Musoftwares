<?php

namespace Modules\Marketplace\Services;

use App\Models\UserReferral;
use App\Models\UserReferralRequestWithdraw;
use App\Models\User;
use Modules\Marketplace\Models\ServiceOrder;
use Illuminate\Support\Facades\DB;
use Exception;

class ReferralService
{
    /**
     * Calculate and credit referral commission on completed order.
     */
    public function processOrderReferralCommission(ServiceOrder $order): ?float
    {
        $buyer = $order->buyer;
        $referral = UserReferral::first();

        if (!$referral || !$referral->user_id) {
            return null;
        }

        $referrer = User::find($referral->user_id);
        if (!$referrer) {
            return null;
        }


        $service = $order->package->service ?? null;
        $commissionFrom = $service->referral_commission_from ?? 'fee';
        $commissionRate = $service->referral_commission_percentage ?? 10.0; // Default 10%

        $baseAmount = ($commissionFrom === 'seller') ? $order->amount : $order->commission_amount;
        $commissionAmount = $baseAmount * ($commissionRate / 100);

        if ($commissionAmount <= 0) {
            return null;
        }

        DB::transaction(function () use ($referrer, $commissionAmount, $order) {
            $referrer->add_balance(
                $commissionAmount,
                __('marketplace.referral_commission_description', ['id' => $order->id]),
                'earned',
                $order->currency_id
            );

            UserReferral::where('referred_user_id', $order->buyer_id)->increment('total_earnings', $commissionAmount);
        });

        return $commissionAmount;
    }

    /**
     * Submit referral earnings withdrawal request.
     */
    public function requestWithdrawal(User $user, float $amount, string $payoutMethod, array $paymentDetails, ?int $userPaymentMethodId = null): UserReferralRequestWithdraw
    {
        if ($user->available_balance() < $amount) {
            throw new Exception(__('marketplace.insufficient_balance_for_withdrawal'));
        }

        return DB::transaction(function () use ($user, $amount, $payoutMethod, $paymentDetails, $userPaymentMethodId) {
            // Deduct balance temporarily for review
            $user->add_balance(
                -$amount,
                __('marketplace.withdrawal_pending_review_description'),
                'used'
            );

            return UserReferralRequestWithdraw::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'currency_id' => 1,
                'user_payment_method_id' => $userPaymentMethodId ?? 1,
                'payment_method' => $payoutMethod,
                'payment_info' => json_encode($paymentDetails),
                'status' => 'pending',
                'created_at' => now('Africa/Cairo'),
            ]);
        });
    }
}
