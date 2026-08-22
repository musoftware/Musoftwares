<?php

namespace App\Services\Calculations\PayService;

use App\Models\CurrenciesExchange;
use Illuminate\Support\Facades\DB;

class DestinationFeeCalculator
{
    /**
     * Applies the fee of the payment destination (e.g., CIB, Redot).
     *
     * @param float $cost The cost after source fees are applied
     * @param string $dest
     * @param int $input_currency_id
     * @param float $original_amount The original service amount before any conversions or fees
     * @return float
     */
    public function calculate(float $cost, string $dest, int $input_currency_id, float $original_amount): float
    {
        $usd_currency_id = config('musoftware.usd_currency_id', 2);

        if ($dest === 'cib') {
            if ($input_currency_id == $usd_currency_id) {
                $cost = round($cost * 1.05, 2);
                return round($cost / (1 - 0.02), 2);
            } else {
                $cost = round($cost / (1 - 0.044), 2);
                return round($cost / (1 - 0.05), 2);
            }
        }

        if ($dest === 'cib_swype') {
            $cost = round($cost * 1.05, 2);
            $cost = round($cost / (1 - 0.02), 2);

            $months = 12; // Assuming a 12-month installment plan
            $interestRate = 2.67 / 100; // Convert percentage to decimal

            $monthlyInstallment = ($cost * $interestRate * pow(1 + $interestRate, $months)) /
                (pow(1 + $interestRate, $months) - 1);

            return round($monthlyInstallment * $months, 2);
        }

        if ($dest === 'alex') {
            $cost = round($cost / (1 - 0.044), 2);
            return round($cost / (1 - 0.06), 2);
        }

        if ($dest === 'wallet') {
            return round($cost / (1 - 0.01), 2);
        }

        if ($dest === 'redot') {
            $cost = round($cost / (1 - 0.044), 2);

            return round($cost / (1 - 0.035), 2);
        }

        // 'cash', 'bank_transfer', 'paypal' passed from UI
        return $cost; 
    }
}
