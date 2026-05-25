<?php

namespace Modules\AffiliatePos\Models;

use Illuminate\Database\Eloquent\Model;

class SkuValue extends Model
{
    protected $table = 'affiliate_pos_product_sku_values';
    protected $guarded = [];

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id', 'id');
    }

    public function option()
    {
        return $this->belongsTo(Option::class, 'option_id', 'id');
    }

    public function optionValue()
    {
        return $this->belongsTo(OptionValue::class, 'option_value_id', 'id');
    }
}
