<?php

namespace Modules\AffiliatePos\Models;

use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    protected $table = 'affiliate_pos_product_options';
    protected $guarded = [];

    public function values()
    {
        return $this->hasMany(OptionValue::class, 'option_id', 'id');
    }
}
