<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Voucher;
use Illuminate\Support\Str;

class PromotionService extends BaseService
{
    public function createCoupon(array $data): Coupon
    {
        $code = $data['code'] ?? null;
        if (! $code) {
            $code = strtoupper(Str::random(8));
            while (Coupon::where('code', $code)->exists()) {
                $code = strtoupper(Str::random(8));
            }
        }

        return Coupon::create([
            'code' => $code,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'discount_amount' => $data['discount_amount'] ?? null,
            'discount_percentage' => $data['discount_percentage'] ?? null,
            'currency_id' => $data['currency'],
            'min_purchase_amount' => $data['min_purchase_amount'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? null,
            'max_total_uses' => $data['max_total_uses'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);
    }

    public function updateCoupon(Coupon $coupon, array $data): Coupon
    {
        $coupon->update([
            'code' => strtoupper($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'type' => $data['type'],
            'discount_amount' => $data['discount_amount'] ?? null,
            'discount_percentage' => $data['discount_percentage'] ?? null,
            'currency_id' => $data['currency'],
            'min_purchase_amount' => $data['min_purchase_amount'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? null,
            'max_total_uses' => $data['max_total_uses'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);

        return $coupon;
    }

    public function createVoucher(array $data): Voucher
    {
        return Voucher::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'spend_amount' => $data['spend_amount'],
            'spend_currency_id' => $data['spend_currency'],
            'reward_amount' => $data['reward_amount'],
            'reward_currency_id' => $data['reward_currency'],
            'type' => $data['type'],
            'reward_percentage' => $data['reward_percentage'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? null,
            'max_total_uses' => $data['max_total_uses'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);
    }

    public function updateVoucher(Voucher $voucher, array $data): Voucher
    {
        $voucher->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'spend_amount' => $data['spend_amount'],
            'spend_currency_id' => $data['spend_currency'],
            'reward_amount' => $data['reward_amount'],
            'reward_currency_id' => $data['reward_currency'],
            'type' => $data['type'],
            'reward_percentage' => $data['reward_percentage'] ?? null,
            'max_uses_per_user' => $data['max_uses_per_user'] ?? null,
            'max_total_uses' => $data['max_total_uses'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);

        return $voucher;
    }
}
