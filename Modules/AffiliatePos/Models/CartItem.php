<?php

namespace Modules\AffiliatePos\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use SoftDeletes;

    protected $table = 'affiliate_pos_cart_items';
    protected $guarded = [];

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id', 'id');
    }

    public function sku()
    {
        return $this->belongsTo(ProductSku::class, 'sku_id', 'id');
    }
}
