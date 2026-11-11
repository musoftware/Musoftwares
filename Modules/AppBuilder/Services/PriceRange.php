<?php

namespace Modules\AppBuilder\Services;

/** The estimate shown around a price, rounded so it reads like a person wrote it ($135 shows as $120 to $200). */
class PriceRange
{
    private const ROUND_TO_TENS_FROM = 100;

    /** @return array{from: float, to: float} */
    public static function around(float $price): array
    {
        $shares = (array) config('builder.price_range');
        $step = $price >= self::ROUND_TO_TENS_FROM ? 10 : 1;

        return [
            'from' => (float) min($price, max($step, self::round($price * (float) $shares['from'], $step))),
            'to' => max($price, self::round($price * (float) $shares['to'], $step)),
        ];
    }

    private static function round(float $value, int $step): float
    {
        return (float) (round($value / $step) * $step);
    }
}
