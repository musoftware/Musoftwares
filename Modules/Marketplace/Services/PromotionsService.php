<?php

namespace Modules\Marketplace\Services;

use Modules\Marketplace\Models\ServiceDiscount;
use App\Models\Coupon;
use App\Models\Voucher;
use App\Models\CouponRedemption;
use App\Models\VoucherRedemption;
use Modules\Marketplace\Helpers\MarketplaceHelper;
use App\Models\User;
use Exception;

class PromotionsService
{
    /**
     * Calculate discounted price for a service given current date rules & user context.
     */
    public function calculateServiceDiscount(float $basePrice, int $serviceId, ?User $user = null): array
    {
        $now = now('Africa/Cairo');

        $discount = ServiceDiscount::where('service_id', $serviceId)
            ->where('is_active', true)
            ->where(function ($q) use ($now) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            })
            ->first();

        if (!$discount) {
            return [
                'final_price' => $basePrice,
                'discount_amount' => 0,
                'applied' => false,
            ];
        }

        // Seasonal Hijri check
        if ($discount->season_rule && !MarketplaceHelper::isHijriPromoActive($discount->season_rule)) {
            return [
                'final_price' => $basePrice,
                'discount_amount' => 0,
                'applied' => false,
                'reason' => __('marketplace.promo_seasonal_not_active'),
            ];
        }

        // New users only check
        if ($discount->new_users_only && $user && $user->created_at->diffInDays($now) > 30) {
            return [
                'final_price' => $basePrice,
                'discount_amount' => 0,
                'applied' => false,
                'reason' => __('marketplace.promo_new_users_only'),
            ];
        }

        // Minimum price threshold
        if ($discount->min_price_threshold && $basePrice < $discount->min_price_threshold) {
            return [
                'final_price' => $basePrice,
                'discount_amount' => 0,
                'applied' => false,
                'reason' => __('marketplace.promo_min_price_threshold'),
            ];
        }

        $discountAmount = $basePrice * (($discount->percentage ?? 0) / 100);
        $finalPrice = max(0, $basePrice - $discountAmount);

        return [
            'final_price' => $finalPrice,
            'discount_amount' => $discountAmount,
            'applied' => true,
            'discount_name' => $discount->code ?? __('marketplace.special_discount'),
        ];
    }

    /**
     * Validate and apply a promotional coupon code.
     */
    public function applyCoupon(string $code, float $amount, User $user): array
    {
        $coupon = Coupon::where('code', strtoupper($code))->where('is_active', true)->first();

        if (!$coupon) {
            throw new Exception(__('marketplace.coupon_invalid_or_inactive'));
        }

        if ($coupon->expires_at && now('Africa/Cairo')->isAfter($coupon->expires_at)) {
            throw new Exception(__('marketplace.coupon_expired'));
        }

        // Usage limit check
        $userRedemptions = CouponRedemption::where('coupon_id', $coupon->id)->where('user_id', $user->id)->count();
        if ($coupon->max_uses_per_user && $userRedemptions >= $coupon->max_uses_per_user) {
            throw new Exception(__('marketplace.coupon_max_uses_exceeded'));
        }

        $discountAmount = $coupon->calculateDiscount($amount);
        $finalAmount = max(0, $amount - $discountAmount);

        return [
            'coupon_id' => $coupon->id,
            'code' => $coupon->code,
            'discount_amount' => $discountAmount,
            'final_amount' => $finalAmount,
        ];
    }

    /**
     * Record coupon redemption.
     */
    public function recordCouponRedemption(int $couponId, User $user, int $orderId, float $savedAmount): void
    {
        CouponRedemption::create([
            'coupon_id' => $couponId,
            'user_id' => $user->id,
            'order_id' => $orderId,
            'saved_amount' => $savedAmount,
            'redeemed_at' => now('Africa/Cairo'),
        ]);
    }
}
