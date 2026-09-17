<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyPointTransaction;
use App\Models\User;
use App\Models\UserReferral;
use App\Services\LoyaltyService;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyController extends Controller
{
    public function __construct(
        protected LoyaltyService $loyaltyService,
        protected ReferralService $referralService
    ) {}

    /**
     * Render the dedicated Client Loyalty & Points Hub.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // 1. User loyalty profile summary (tier, balance, points to next tier, progress)
        $summary = $this->loyaltyService->getUserSummary($user);

        // 2. Full transparent ledger (paginated)
        $ledger = $this->loyaltyService->getPaginatedLedger($user, 15);

        // 3. Complete tiers progression ladder
        $tiers = $this->loyaltyService->getAllTiers();

        // 4. Rewards catalog for instant redemption
        $rewards = $this->loyaltyService->getActiveRewards();

        // 5. Referral link and stats
        $referral = UserReferral::where('user_id', $user->id)->first();
        if (! $referral) {
            $referral = $this->referralService->ensureReferralSystemActive($user);
        }

        $referralCount = User::where('ref_user_id', $user->id)->count();
        $referralPointsEarned = (int) LoyaltyPointTransaction::where('user_id', $user->id)
            ->whereIn('event_type', ['referral_registered', 'referral_first_payment'])
            ->sum('points');

        $referralUrl = $referral ? url('/r/' . ($referral->slug ?: $referral->key)) : url('/r/' . $user->id);

        return Inertia::render('Client/Loyalty/Index', [
            'summary'          => $summary,
            'ledger'           => $ledger,
            'tiers'            => $tiers,
            'rewards'          => $rewards,
            'referral'         => [
                'code'         => $referral?->key,
                'slug'         => $referral?->slug,
                'url'          => $referralUrl,
                'total_users'  => $referralCount,
                'points_earned'=> $referralPointsEarned,
            ],
            'pointsConversionRate' => LoyaltyService::POINTS_TO_CURRENCY_RATE,
        ]);
    }
}
