<?php

namespace Modules\GoldSavers\Models;

use Illuminate\Database\Eloquent\Model;

class GoldSaver extends Model
{
    protected $fillable = [
        'gram_price',
        'additional_price',
        'grams',
        'tax',
        'carat',
    ];

    public function buyer_price(): float
    {
        $gramPrice = $this->gram_price ?? 0;
        $additionalPrice = $this->additional_price ?? 0;
        $grams = $this->grams ?? 0;
        $tax = $this->tax ?? 0;

        return ($gramPrice + $additionalPrice) * $grams + $tax;
    }

    public function seller_price(array $latestPrices): float
    {
        $carat = $this->carat;
        $grams = $this->grams ?? 0;
        $key = 'price_' . $carat . 'k';

        if (!isset($latestPrices[$key])) {
            return 0.0;
        }

        return $latestPrices[$key] * $grams;
    }
}
