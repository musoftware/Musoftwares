<?php

namespace Modules\AffiliatePos\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Option extends Model
{
    use SoftDeletes;

    protected $table = 'affiliate_pos_product_options';
    protected $guarded = [];

    public function values()
    {
        return $this->hasMany(OptionValue::class, 'option_id', 'id');
    }
}
