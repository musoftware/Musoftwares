<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserReferral;
use App\Models\Earning;
use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class ReferralService extends BaseService
{

    /**
     * Ensure the user has the referral system activated and a primary referral link exists.
     */
    public function ensureReferralSystemActive(User $user): UserReferral
    {
        if ($user->allow_referral_system != '1') {
            $user->allow_referral_system = '1';
            $user->save();
        }

        $referral = UserReferral::where('user_id', $user->id)->first();
        
        if (!$referral) {
            $referral = new UserReferral();
            $referral->user_id = $user->id;
            $referral->title = 'Primary Campaign';
            $referral->key = sha1(md5(uniqid() . $user->id));
            $referral->save();
        }

        return $referral;
    }

    /**
     * Calculate commission by referred user id.
     */
    public function calculateCommissionByUserId(User $auth, $referredUsers): array
    {
        $ids = $referredUsers->pluck('id');
        $earnings = Earning::query()
            ->where('user_id', $auth->id)
            ->whereIn('referred_user_id', $ids)
            ->get();

        $commissionByUserId = [];
        foreach ($earnings->groupBy('referred_user_id') as $referred_user_id => $userEarnings) {
            $total = 0;
            foreach ($userEarnings as $e) {
                if (class_exists(CurrenciesExchange::class)) {
                    $total += CurrenciesExchange::RateToday($e->amount, $e->currency, $auth->currency);
                } else {
                    $total += $e->amount;
                }
            }
            $commissionByUserId[$referred_user_id] = round($total, 2);
        }

        return $commissionByUserId;
    }

    /**
     * Store a new user referred by the authenticated user.
     */
    public function registerReferredUser(User $referrer, array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone_number' => $data['phone_number'] ?? null,
            'currency' => $referrer->currency_name(),
            'affiliate_commission_percentage' => $data['affiliate_commission_percentage'] ?? 1.00,
            'add_commission_to_total' => isset($data['affiliate_commission_percentage']),
        ]);

        $user->ref_user_id = $referrer->id;
        $user->save();

        event(new Registered($user));

        return $user;
    }

    /**
     * Handle the referral redirect logic.
     */
    public function processReferralRedirect($valueRef): ?UserReferral
    {
        $referral = UserReferral::resolveRef($valueRef);
        if (!$referral) {
            return null;
        }

        if (str_contains($_SERVER['HTTP_USER_AGENT'] ?? '', 'FBAV')) {
            header('X-Frame-Options: DENY');
            header('Referrer-Policy: origin');
        }

        if (empty($_SERVER['HTTP_USER_AGENT']) || !preg_match('~(bot|crawl)~i', $_SERVER['HTTP_USER_AGENT'])) {
            UserReferral::IncViewRef($valueRef);
        }

        return $referral;
    }
}
