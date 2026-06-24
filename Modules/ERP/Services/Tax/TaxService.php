<?php

namespace Modules\ERP\Services\Tax;

use Modules\ERP\Models\Tax\TaxRate;

class TaxService
{
    /**
     * Calculate tax for a given subtotal and tax rate
     * @param float $amount The subtotal amount
     * @param string $taxRateId The UUID of the TaxRate
     * @return array Contains tax amount and total
     */
    public function calculateTax(float $amount, string $taxRateId): array
    {
        $rate = TaxRate::findOrFail($taxRateId);
        
        $taxAmount = 0;
        
        if ($rate->type === 'percentage') {
            $taxAmount = $amount * ($rate->rate / 100);
        } elseif ($rate->type === 'fixed') {
            $taxAmount = (float) $rate->rate;
        }

        return [
            'subtotal' => $amount,
            'tax_amount' => $taxAmount,
            'total' => $amount + $taxAmount,
            'rate_used' => $rate->rate,
            'rate_type' => $rate->type
        ];
    }
}
