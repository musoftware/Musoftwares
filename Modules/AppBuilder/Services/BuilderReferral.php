<?php

namespace Modules\AppBuilder\Services;

use App\Models\Currency;
use App\Models\Earning;
use App\Models\UserReferral;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * Referrals on the AI builder. A customer who came from someone's referral link pays the normal price plus a
 * share; after payment that share is recorded as the referrer's earning, which the existing earnings:clear
 * command moves into their balance once the clearing days pass.
 */
class BuilderReferral
{
    /** The referrer's user id from the referral link the visitor opened (`/r/{code}?to=ai` or `/ai?ref=code`). */
    public function referrerFor(Request $request): ?int
    {
        $code = (string) ($request->query('ref') ?: $request->session()->get('referral', ''));
        $referral = $code !== '' ? UserReferral::resolveRef($code) : null;

        // a referrer opening their own link earns nothing from themselves
        if (! $referral || $referral->user_id === $request->user()?->id) {
            return null;
        }

        return (int) $referral->user_id;
    }

    /**
     * @param  array{amount: float}  $price
     * @return array{amount: float, commission: float}
     */
    public function addShare(array $price): array
    {
        $amount = (float) $price['amount'];
        $withShare = ceil($amount * (1 + (float) config('builder.referral.share')));

        return ['amount' => $withShare, 'commission' => round($withShare - $amount, 2)];
    }

    /**
     * The referrer's share of one payment: a deposit earns its part now, the rest when the balance is paid.
     * A problem here must never undo the payment, so it is logged instead.
     */
    public function recordEarning(BuilderProject $project, ?float $paid = null): void
    {
        if (! $project->referrer_user_id || (float) $project->referral_commission <= 0 || (float) $project->amount <= 0) {
            return;
        }
        $amount = round((float) $project->referral_commission * min(1, ($paid ?? (float) $project->amount) / (float) $project->amount), 2);

        try {
            Earning::create([
                'user_id' => $project->referrer_user_id,
                'currency_id' => Currency::where('currency', $project->currency)->value('id'),
                'amount' => $amount,
                'convert_to_balance_on' => now()->addDays((int) config('builder.referral.clearing_days')),
            ]);
        } catch (\Throwable $e) {
            Log::error('App builder: referral earning not recorded', ['project_id' => $project->id, 'error' => $e->getMessage()]);
        }
    }
}
