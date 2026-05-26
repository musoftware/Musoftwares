<?php

namespace Modules\AffiliatePos\Models;

use Illuminate\Database\Eloquent\Model;

class StockMutation extends Model
{
    protected $table = 'affiliate_pos_stock_mutations';
    protected $guarded = [];

    public function stockable()
    {
        return $this->morphTo();
    }
}
