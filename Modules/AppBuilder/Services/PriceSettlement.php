<?php

namespace Modules\AppBuilder\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AppBuilder\Models\BuilderMessage;
use Modules\AppBuilder\Models\BuilderProject;

/**
 * After a paid build is delivered, its price follows what the work really cost: the cost plus our share (more when a
 * referrer also earns from it), plus the designer and the study as agreed, never above the price the customer
 * accepted. A signed-in customer gets what they paid above that back as site balance for their next program; a guest
 * pays less of what is still due. Once per quote version.
 */
class PriceSettlement
{
    public function settle(BuilderProject $project, float $jobCostUsd): void
    {
        if (! $this->applies($project, $jobCostUsd)) {
            return;
        }

        $final = $this->finalPrice($project, $jobCostUsd);
        $refund = round((float) $project->quote_paid - $final, 2);
        $credited = $refund > 0 && $project->user_id !== null;

        DB::transaction(function () use ($project, $final, $refund, $credited): void {
            $project->update([
                // a guest who already paid more keeps the program at what they paid; a deposit's rest only goes down
                'amount' => $credited || $refund <= 0 ? $final : (float) $project->quote_paid,
                'quote_paid' => $credited ? $final : (float) $project->quote_paid,
                'paid_total' => $credited ? (float) $project->paid_total - $refund : (float) $project->paid_total,
                'price_settled_version' => $project->quote_version,
            ]);
            if ($credited) {
                $project->user->add_balance($refund, 'AI builder: the build cost less than its price ('.$project->title.')', 'received', $this->currencyId($project));
                $project->messages()->create(['role' => BuilderMessage::TEAM, 'content' => __('builder.price_settled', ['amount' => number_format($refund, 2).' '.$project->currency], $project->customerLanguage())]);
            }
        });

        Log::info('App builder: price settled', ['project_id' => $project->id, 'cost_usd' => $jobCostUsd, 'final' => $final, 'refund' => $credited ? $refund : 0]);
    }

    private function applies(BuilderProject $project, float $jobCostUsd): bool
    {
        return config('builder.settlement.enabled')
            && $jobCostUsd > 0
            && ! $project->free_fix
            && ! $project->free_build
            && (float) $project->quote_paid > 0
            && $project->price_settled_version !== $project->quote_version
            // the cost is in dollars; another currency would need a rate, so its price stays as agreed
            && $project->currency === 'USD';
    }

    /** The real cost plus our share, the fixed fees as agreed, at least the cost plus the referrer's share, at most the price. */
    private function finalPrice(BuilderProject $project, float $jobCostUsd): float
    {
        $share = $project->referrer_user_id ? (float) config('builder.settlement.referral_markup') : (float) config('builder.settlement.markup');
        $work = max($jobCostUsd * (1 + $share), $jobCostUsd + (float) $project->referral_commission);
        $fees = (float) $project->design_fee + ($project->study_status === Study\FeasibilityStudy::READY ? (float) $project->study_fee : 0);

        return min((float) $project->amount, ceil(($work + $fees) * 100) / 100);
    }

    private function currencyId(BuilderProject $project): ?int
    {
        return \App\Models\Currency::resolve($project->currency)?->id;
    }
}
