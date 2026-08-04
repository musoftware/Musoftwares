<?php

namespace Modules\Marketplace\Services;

use App\Models\User;
use Modules\Marketplace\Models\ServicePackage;
use Modules\Marketplace\Models\ServiceOrder;
use Modules\Marketplace\Services\EscrowService;
use Modules\Marketplace\Services\PromotionsService;
use Illuminate\Support\Facades\DB;
use Exception;

class CheckoutService
{
    public function __construct(
        protected EscrowService $escrowService,
        protected PromotionsService $promotionsService
    ) {}

    /**
     * Process checkout for single package or multi-item cart.
     */
    public function processCheckout(User $buyer, int $packageId, ?string $couponCode = null, array $extraIds = []): ServiceOrder
    {
        return DB::transaction(function () use ($buyer, $packageId, $couponCode, $extraIds) {
            // Lock buyer record for update to prevent concurrent balance deductions
            $lockedBuyer = User::where('id', $buyer->id)->lockForUpdate()->first();

            $package = ServicePackage::with('service')->findOrFail($packageId);
            $service = $package->service;

            if (!$service || $service->status !== 'active') {
                throw new Exception("عذراً، هذه الخدمة غير متاحة للشراء حالياً.");
            }

            if ($lockedBuyer->id === $service->seller_id) {
                throw new Exception("لا يمكنك شراء الخدمة الخاصة بك.");
            }

            $totalAmount = $package->price;

            // Add extras
            if (!empty($extraIds)) {
                $extrasPrice = \Modules\Marketplace\Models\ServiceExtra::whereIn('id', $extraIds)
                    ->where('service_id', $service->id)
                    ->sum('price');
                $totalAmount += $extrasPrice;
            }

            // Apply promotional coupon if provided
            $couponId = null;
            $savedAmount = 0;
            if ($couponCode) {
                $couponRes = $this->promotionsService->applyCoupon($couponCode, $totalAmount, $lockedBuyer);
                $totalAmount = $couponRes['final_amount'];
                $discountAmount = $couponRes['discount_amount'];
                $couponId = $couponRes['coupon_id'];
                $savedAmount = $discountAmount;
            }

            $availableBalance = $lockedBuyer->available_balance();
            if ($availableBalance < $totalAmount) {
                throw new Exception("رصيدك الحسابي غير كافٍ لإتمام الشراء (المطلوب: {$totalAmount}، المتاح: {$availableBalance}).");
            }

            $commissionRate = config('marketplace.commission_rate', 0.10);
            $commissionAmount = $totalAmount * $commissionRate;

            $order = ServiceOrder::create([
                'buyer_id' => $lockedBuyer->id,
                'seller_id' => $service->seller_id,
                'package_id' => $package->id,
                'amount' => $totalAmount,
                'currency_id' => $package->currency_id ?? 1,
                'commission_amount' => $commissionAmount,
                'status' => \Modules\Marketplace\Enums\ServiceOrderStatus::PENDING,
            ]);

            // Hold funds in Escrow
            $this->escrowService->holdFunds($order);

            if ($couponId && $savedAmount > 0) {
                $this->promotionsService->recordCouponRedemption($couponId, $lockedBuyer, $order->id, $savedAmount);
            }

            return $order;
        });
    }
}
