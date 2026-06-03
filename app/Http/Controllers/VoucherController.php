<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\VoucherRedemption;
use App\Models\CurrenciesExchange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VoucherController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get all active vouchers that user can use
        $vouchers = Voucher::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->with(['spendCurrency', 'rewardCurrency'])
            ->get()
            ->filter(function ($voucher) use ($user) {
                return $voucher->canBeUsedByUser($user);
            })->values();

        // Format and pre-calculate amounts to user currency
        $formattedVouchers = $vouchers->map(function ($voucher) use ($user) {
            $spendAmountInUserCurrency = $voucher->spend_currency_id == $user->currency_id
                ? $voucher->spend_amount
                : CurrenciesExchange::RateByDate(now(), $voucher->spend_amount, $voucher->spend_currency_id, $user->currency_id);

            $baseRewardAmount = $voucher->type === 'percentage' 
                ? ($voucher->spend_amount * ($voucher->reward_percentage / 100)) 
                : $voucher->reward_amount;

            $rewardAmountInUserCurrency = $voucher->reward_currency_id == $user->currency_id
                ? $baseRewardAmount
                : CurrenciesExchange::RateByDate(now(), $baseRewardAmount, $voucher->reward_currency_id, $user->currency_id);

            return [
                'id' => $voucher->id,
                'name' => $voucher->name,
                'description' => $voucher->description,
                'type' => $voucher->type,
                'reward_percentage' => $voucher->reward_percentage,
                'spend_amount_user_currency' => $spendAmountInUserCurrency,
                'reward_amount_user_currency' => $rewardAmountInUserCurrency,
                'expires_at' => $voucher->expires_at ? $voucher->expires_at->format('Y-m-d') : null,
                'max_uses_per_user' => $voucher->max_uses_per_user,
                'current_uses' => $voucher->current_uses,
                'max_total_uses' => $voucher->max_total_uses,
            ];
        });

        // Get user's redemption history
        $userRedemptions = VoucherRedemption::where('user_id', $user->id)
            ->with(['voucher', 'spentCurrency', 'rewardCurrency'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Vouchers/Index', [
            'vouchers' => $formattedVouchers,
            'redemptions' => $userRedemptions,
        ]);
    }
}
