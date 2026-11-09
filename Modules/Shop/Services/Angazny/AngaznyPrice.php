<?php

namespace Modules\Shop\Services\Angazny;

/**
 * The owner's pricing rule. Real cost is Angazny's price minus its commission.
 * The result is the highest price ending in the set digit that keeps profit inside the cap,
 * and it is never under Angazny's price or under the least profit per piece.
 */
final class AngaznyPrice
{
    private const STEP = 10;

    /** Null means Angazny pays no commission, so the owner sets this price by hand. */
    public static function forStore(float $angaznyPrice, float $commission): ?float
    {
        if ($commission <= 0) {
            return null;
        }

        $cost = $angaznyPrice - $commission;
        $lowest = max($angaznyPrice, $cost + (float) config('shop.angazny.min_profit'));
        $highest = $cost / (1 - (float) config('shop.angazny.max_profit_ratio'));

        return max(self::roundUp($lowest), self::roundDown($highest));
    }

    private static function roundUp(float $value): float
    {
        $ending = (int) config('shop.angazny.price_ending');

        return ceil(($value - $ending) / self::STEP) * self::STEP + $ending;
    }

    private static function roundDown(float $value): float
    {
        $ending = (int) config('shop.angazny.price_ending');

        return floor(($value - $ending) / self::STEP) * self::STEP + $ending;
    }
}
