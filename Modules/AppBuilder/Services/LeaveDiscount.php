<?php

namespace Modules\AppBuilder\Services;

use Modules\AppBuilder\Models\BuilderProject;
use Modules\AppBuilder\Services\Study\FeasibilityStudy;

/**
 * A customer who starts to leave after seeing the price is offered a discount, once per project.
 * It comes off the programming part only (the designer's fee and a written study are real costs), and the
 * build's spending cap goes down by the same share, so a build still never costs more than the customer paid.
 * Once taken it stays: a later quote for the same project gets it too.
 */
class LeaveDiscount
{
    /** The percent to offer now, or null when there is nothing to offer. */
    public function offerFor(BuilderProject $project): ?int
    {
        $percent = (int) config('builder.leave_discount.percent');
        if ($percent <= 0 || $project->leave_discount_at !== null) {
            return null;
        }

        return $this->isOpenQuote($project) ? $percent : null;
    }

    /** @return bool false when there was no offer (already taken, paid, or no open price) */
    public function take(BuilderProject $project): bool
    {
        $percent = $this->offerFor($project);
        if ($percent === null) {
            return false;
        }

        // two clicks at once take it only once
        $claimed = BuilderProject::whereKey($project->id)->whereNull('leave_discount_at')
            ->update(['leave_discount_at' => now(), 'leave_discount_percent' => $percent]);
        if ($claimed === 0) {
            return false;
        }

        $this->applyTo($project->refresh());

        return true;
    }

    /** Takes the customer's discount off the current price; called after every new quote as well. */
    public function applyTo(BuilderProject $project): void
    {
        if ($project->leave_discount_at === null || ! $this->isOpenQuote($project)) {
            return;
        }

        $keep = (100 - $project->leave_discount_percent) / 100;
        $off = round($this->programming($project) * $project->leave_discount_percent / 100, 2);
        $project->update([
            'amount' => round((float) $project->amount - $off, 2),
            'leave_discount_usd' => $off,
            'budget_usd' => round((float) $project->budget_usd * $keep, 4),
            'referral_commission' => round((float) $project->referral_commission * $keep, 2),
        ]);
    }

    private function isOpenQuote(BuilderProject $project): bool
    {
        return $project->status === BuilderProject::QUOTED
            && ! $project->free_fix
            && (float) $project->amount > 0
            && (float) $project->quote_paid <= 0
            && (float) $project->payment_due <= 0;
    }

    /** The part of the price that is our own work. */
    private function programming(BuilderProject $project): float
    {
        $study = $project->study_status === FeasibilityStudy::READY ? (float) $project->study_fee : 0.0;

        return max(0.0, (float) $project->amount - (float) $project->design_fee - $study);
    }
}
