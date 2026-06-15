<?php

namespace App\Services\Calculations\PayService;

class RevenueMarginCalculator
{
    /**
     * Applies the final revenue margin multipliers based on the revenue tier.
     *
     * @param float $cost The final cost after all gateway fees
     * @param int $revenue_tier
     * @return float
     */
    public function calculate(float $cost, int $revenue_tier): float
    {
        return match ($revenue_tier) {
            3  => round($cost / (1 - 0.25), 2),
            2  => round($cost / (1 - 0.175), 2),
            1  => round($cost / (1 - 0.1125), 2),
            0  => round($cost / (1 - 0.0475), 2),
            -1 => round($cost / (1 - 0.01125), 2),
            default => round($cost / (1 - 0.0175), 2), // includes 4 or unknown
        };
    }
}
