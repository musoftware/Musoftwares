<?php

namespace App\Services\Calculations\PayService;

use App\Services\BaseService;

use App\Models\CurrenciesExchange;
use App\Models\GoldWorldPrice;
use App\Models\GoldPrice;
use Illuminate\Support\Facades\DB;

class DestinationFeeCalculator extends BaseService
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
            // Note: The original logic overrides the entire cost and ignores source fees.
            // Retaining original behavior based on the old script.
            $item = GoldWorldPrice::query()
                ->select(DB::raw('DATE(price_date) as price_date, avg(price_24k) as price_24k, avg(price_22k) as price_22k, avg(price_21k) as price_21k, avg(price_18k) as price_18k, avg(price_14k) as price_14k'))
                ->groupBy(DB::raw('DATE(price_date)'))
                ->orderBy(DB::raw('DATE(price_date)'), 'desc')
                ->first();
            
            if ($item) {
                $usdPrice1 = CurrenciesExchange::RateByDate($item->price_date, $item->price_21k, $usd_currency_id, 1);
                $price_21 = GoldPrice::query()
                    ->where(DB::raw('DATE(price_date)'), $item->price_date)
                    ->select(DB::raw('avg(price_21k) as price_21k'))
                    ->groupBy(DB::raw('DATE(price_date)'))
                    ->first();

                if ($usdPrice1 > 0 && $price_21) {
                    $new_cost = (int)$original_amount * ($price_21->price_21k / $usdPrice1);
                    $new_cost = round($new_cost / (1 - 0.044), 2);
                    return round($new_cost / (1 - 0.035), 2);
                }
            }
        }

        // 'cash', 'bank_transfer', 'paypal' passed from UI
        return $cost; 
    }
}
