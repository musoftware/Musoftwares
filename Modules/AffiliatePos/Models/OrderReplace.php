<?php

namespace Modules\AffiliatePos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderReplace extends Model
{
    protected $table = 'affiliate_pos_order_replaces';
    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function originalItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id', 'id');
    }

    public function newItem()
    {
        return $this->belongsTo(OrderItem::class, 'new_order_item_id', 'id');
    }
}
