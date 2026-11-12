<?php

namespace Modules\AppBuilder\Services;

/**
 * Turns Claude's token estimate into the customer's price, the build model and the worker's spending cap.
 * The work factor comes from real builds of the same kind (BuildCostHistory), so prices follow real cost.
 * Price = the larger of the project type's minimum and the estimated cost x markup, plus a design fee.
 * The build may spend up to (price without design) x budget_factor / markup, and the worker stops at 1.5x that,
 * which is the price itself: a build never costs more than the customer paid.
 */
class QuotePricer
{
    public const DEFAULT_TYPE = 'other';

    /** Changes to a delivered or imported program learn their cost from other changes, not from new builds. */
    public const CHANGE = 'change';

    public function __construct(private readonly BuildCostHistory $history) {}

    /** @return array{tokens: int, cost: float, amount: float, design_fee: float, budget: float, model: string, project_type: string} */
    public function price(int $estimatedTokens, bool $complex = false, float $designFee = 0, string $projectType = self::DEFAULT_TYPE): array
    {
        $pricing = config('builder.pricing');
        $tokens = (int) min(max($estimatedTokens, $pricing['min_tokens']), $pricing['max_tokens']);
        $model = $this->buildModel($complex);
        $type = $projectType === self::CHANGE ? self::CHANGE : $this->type($projectType);
        // what builds of this kind really cost per estimated token, learned from delivered work
        $cost = $tokens / 1_000_000 * $model['output_usd_per_mtok'] * $this->history->workFactor($type);
        $programming = max((float) $pricing['minimum'], $this->minimumFor($type), ceil($cost * $pricing['markup']));
        $design = $this->designFee($designFee);

        return [
            'tokens' => $tokens,
            'cost' => round($cost, 4),
            'amount' => $programming + $design,
            'design_fee' => $design,
            'budget' => round($programming * $pricing['budget_factor'] / $pricing['markup'], 4),
            'model' => $model['model'],
            'project_type' => $type,
        ];
    }

    /**
     * A change to a program that is already delivered: its own work times the markup, at least the change minimum.
     * The minimum of the project's type is for building a whole program from nothing, so it never applies here.
     *
     * @return array{tokens: int, cost: float, amount: float, design_fee: float, budget: float, model: string, project_type: string}
     */
    public function change(int $estimatedTokens, bool $complex = false): array
    {
        $pricing = config('builder.pricing');
        $price = $this->price($estimatedTokens, $complex, 0, self::CHANGE);
        $amount = max((float) $pricing['change_minimum'], ceil($price['cost'] * $pricing['markup']));

        return ['amount' => $amount, 'budget' => $this->budgetFor($amount)] + $price;
    }

    /** What a build may spend for a change sold at this price (the owner may set the price by hand). */
    public function budgetFor(float $amount): float
    {
        $pricing = config('builder.pricing');

        return round($amount * $pricing['budget_factor'] / $pricing['markup'], 4);
    }

    /** A free fix gets the same model and spending cap as paid work; only the customer's price is zero. */
    public function freeFix(int $estimatedTokens, bool $complex = false): array
    {
        $paid = $this->price($estimatedTokens, $complex, 0, self::CHANGE);

        // a fix is small work, so its cap follows its own estimate, not the minimum of a whole project
        return ['amount' => 0.0, 'budget' => round($paid['cost'] * config('builder.pricing.budget_factor'), 4)] + $paid;
    }

    private function type(string $type): string
    {
        return array_key_exists($type, (array) config('builder.project_types')) ? $type : self::DEFAULT_TYPE;
    }

    private function minimumFor(string $type): float
    {
        return (float) config("builder.project_types.{$type}", 0);
    }

    /** A design is either not asked for (0) or at least the minimum fee. */
    private function designFee(float $fee): float
    {
        return $fee <= 0 ? 0.0 : max((float) config('builder.design.minimum_fee'), ceil($fee));
    }

    /** @return array{model: string, output_usd_per_mtok: float} */
    private function buildModel(bool $complex): array
    {
        return config('builder.build_models.'.($complex ? 'strong' : 'standard'));
    }
}
