<?php

namespace Modules\AffiliatePos\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use SoftDeletes;

    protected $table = 'affiliate_pos_order_items';
    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
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
