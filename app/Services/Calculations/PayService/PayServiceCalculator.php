<?php

namespace App\Services\Calculations\PayService;

use App\Models\CurrenciesExchange;
use App\Models\Invoice;

/**
 * Service to handle Pay Service (دفع خدمة) calculations
 */
class PayServiceCalculator
{
    private const MARGIN_USD = 0.20;

    public function __construct(
        private SourceFeeCalculator $sourceFeeCalculator,
        private DestinationFeeCalculator $destinationFeeCalculator,
        private RevenueMarginCalculator $revenueMarginCalculator
    ) {
    }

    /**
     * @param Invoice $invoice
     * @param float $service_amount 
     * @param int $input_currency_id 
     * @param string $source (wallet, paypal, gumroad, payoneer, cash, bank_transfer)
     * @param string $dest (cib, cib_swype, alex, redot, wallet, cash, bank_transfer)
     * @param int $revenue_tier
     * @return array
     */
    public function calculate(Invoice $invoice, $service_amount, $input_currency_id, $source, $dest, $revenue_tier)
    {
        // 1. Convert initial amount to invoice's currency
        $cost = CurrenciesExchange::RateToday((int)$service_amount, $input_currency_id, $invoice->currency_id);

        // 2. Apply Source Gateway Fees
        $cost = $this->sourceFeeCalculator->calculate($cost, $source);

        // 3. Apply Destination Gateway Fees
        $cost = $this->destinationFeeCalculator->calculate($cost, $dest, $input_currency_id, $service_amount);

        // 4. Calculate Final Total with Revenue Margins
        $total = $this->revenueMarginCalculator->calculate($cost, (int)$revenue_tier);

        // 5. Calculate USD Equivalent
        $usd_currency_id = config('musoftware.usd_currency_id', 2); 
        $total_usd = CurrenciesExchange::RateToday($cost, $invoice->currency_id, $usd_currency_id);
        $total_usd = round($total_usd / (1 - self::MARGIN_USD), 2);

        return [
            'cost' => round($cost, 2),
            'total' => round($total, 2),
            'total_usd' => round($total_usd, 2),
        ];
    }
}
