<?php

namespace Modules\GoldSavers\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoldSaver extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'carat',
        'gram_price',
        'additional_price',
        'grams',
        'tax',
        'bought_date',
        'zakat'
    ];

    public function buyer_price()
    {
        return round((($this['gram_price'] + $this['additional_price']) * $this['grams']) + $this['tax']);
    }

    public function buy2sell_rate()
    {
        $buy_price = round((($this['gram_price'] + $this['additional_price']) * $this['grams']) + $this['tax']);
        $sell_price = round($this['grams'] * $this['gram_price']);
        
        // Prevent division by zero
        if ($sell_price == 0) return 0;
        
        return round($buy_price / $sell_price, 2);
    }

    public function predict_buy($gold_price){
        return round($this->seller_price($gold_price) * $this->buy2sell_rate());
    }

    public function seller_price($gold_price)
    {
        $carat24_price = round($gold_price['price_24k'] ?? 0, 2);
        $carat21_price = round($gold_price['price_21k'] ?? 0, 2);
        $carat22_price = round($gold_price['price_22k'] ?? 0, 2);
        $carat18_price = round($gold_price['price_18k'] ?? 0, 2);
        $carat14_price = round($gold_price['price_14k'] ?? 0, 2);
        $carat10_price = round($gold_price['price_10k'] ?? 0, 2);

        if ($this['carat'] == 24) {
            $carat_price = $carat24_price;
        }
        elseif ($this['carat'] == 21) {
            $carat_price = $carat21_price;
        }
        elseif ($this['carat'] == 22)
            $carat_price = $carat22_price;
        elseif ($this['carat'] == 18)
            $carat_price = $carat18_price;
        elseif ($this['carat'] == 14)
            $carat_price = $carat14_price;
        elseif ($this['carat'] == 10)
            $carat_price = $carat10_price;
        else
            $carat_price = 0;

        return round($this['grams'] * $carat_price);
    }
}
