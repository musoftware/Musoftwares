<?php

namespace App\Services\Calculations\PayService;

use App\Services\BaseService;

class SourceFeeCalculator extends BaseService
{

    /**
     * Applies the fee of the payment source (e.g., Wallet, PayPal) to the base cost.
     *
     * @param float $cost
     * @param string $source
     * @return float
     */
    public function calculate(float $cost, string $source): float
    {
        return match ($source) {
            'wallet'   => round($cost / (1 - 0.01), 2),
            'paypal'   => round($cost / (1 - 0.05), 2),
            'gumroad'  => round($cost / (1 - 0.14), 2),
            'payoneer' => round($cost / (1 - 0.03), 2),
            'cash', 'bank_transfer', 'cib_swype' => $cost, // 0% fee
            default    => $cost,
        };
    }
}
