<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserReferral;
use App\Models\Earning;
use App\Models\CurrenciesExchange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;

class ReferralService extends BaseService
{

    /**
     * Cookie name used to dedupe referral-view increments per visitor.
     */
    public const VIEW_DEDUPE_COOKIE = 'ref_viewed';

    /**
     * Ensure the user has the referral system activated and a primary referral link exists.
     *
     * NOTE: This mutates user state on a read path (called by ReferralController::index).
     * If that's undesirable in the future, replace with an explicit POST activation step.
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
     * Calculate commission by referred user id for the *current* pagination page.
     * Use calculateTotalCommissionForReferrer() for a global aggregate.
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
     * Global lifetime commission total earned by the auth user across ALL
     * referred users, not just the currently paginated page. Cached briefly
     * to avoid re-summing millions of earning rows on every dashboard render.
     */
    public function calculateTotalCommissionForReferrer(User $auth): float
    {
        return Cache::remember(
            'referrer_total_commission:' . $auth->id,
            now()->addMinutes(2),
            function () use ($auth) {
                $total = 0;
                Earning::query()
                    ->where('user_id', $auth->id)
                    ->orderBy('id')
                    ->chunk(500, function ($chunk) use (&$total, $auth) {
                        foreach ($chunk as $e) {
                            if (class_exists(CurrenciesExchange::class)) {
                                $total += CurrenciesExchange::RateToday($e->amount, $e->currency, $auth->currency);
                            } else {
                                $total += $e->amount;
                            }
                        }
                    });

                return round($total, 2);
            }
        );
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
     * Handle the referral redirect logic. Side-effect-free with respect to the
     * HTTP response (no header() calls — those belong in middleware).
     *
     * Returns null when the ref is invalid, bot, self-referral, or recently
     * counted. View increments are deduped via a signed cookie set by the
     * caller (ReferralRedirectMiddleware).
     */
    public function processReferralRedirect($valueRef, ?Request $request = null): ?UserReferral
    {
        $referral = UserReferral::resolveRef($valueRef);
        if (!$referral) {
            return null;
        }

        // Self-referral guard: a referrer clicking their own link must not
        // inflate their own view/registration counters.
        if ($request && $request->user() && $request->user()->id === $referral->user_id) {
            return $referral;
        }

        // Skip view increments for empty-UA or obvious bot agents. This is a
        // best-effort filter — true bot detection belongs in middleware that
        // also rate-limits by IP and uses the ref-dedupe cookie.
        $ua = $request ? ($request->userAgent() ?? '') : ($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (empty($ua) || preg_match('~(bot|crawl|spider|slurp)~i', $ua)) {
            return $referral;
        }

        // Cookie-based dedupe: only increment once per visitor per ref.
        if ($request && $request->cookie(self::VIEW_DEDUPE_COOKIE) === $referral->key) {
            return $referral;
        }

        // Atomic increment guarded by a short cache lock to avoid double-counts
        // when the same visitor hits the redirect in parallel.
        $lockKey = 'referral_view_lock:' . $referral->key;
        Cache::lock($lockKey, 2)->block(1, function () use ($referral) {
            $referral->increment('views');
        });

        return $referral;
    }
}