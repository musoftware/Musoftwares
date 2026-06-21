<?php

namespace Modules\AffiliatePos\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'affiliate_pos_orders';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(OrderComment::class, 'order_id', 'id');
    }

    public function returns()
    {
        return $this->hasMany(OrderReturn::class, 'order_id', 'id');
    }

    public function replaces()
    {
        return $this->hasMany(OrderReplace::class, 'order_id', 'id');
    }

    public function shippingCompany()
    {
        return $this->belongsTo(ShippingCompany::class, 'shipping_company_id', 'id');
    }
}
