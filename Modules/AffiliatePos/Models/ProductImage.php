<?php

namespace Modules\AffiliatePos\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use SoftDeletes;

    protected $table = 'affiliate_pos_product_images';
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }
}
