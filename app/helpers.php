<?php

if (! function_exists('psychological_price')) {
    /**
     * Apply psychological pricing rounding.
     * Example: 22 -> 19.99, 24 -> 29.99
     */
    function psychological_price(float $price): float
    {
        if ($price <= 0) {
            return 0;
        }

        $tens = floor($price / 10) * 10;
        $remainder = $price - $tens;

        // Handle small prices < 10
        if ($tens == 0 && $remainder < 4) {
            return max(0, round($price) - 0.01);
        }

        if ($remainder < 4) {
            return $tens - 0.01;
        } else {
            return $tens + 10 - 0.01;
        }
    }
}
