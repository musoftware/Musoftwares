<?php

namespace Modules\AffiliatePos\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class OptionValue extends Model
{
    use SoftDeletes;

    protected $table = 'affiliate_pos_product_option_values';
    protected $guarded = [];

    public function option()
    {
        return $this->belongsTo(Option::class, 'option_id', 'id');
    }
}
