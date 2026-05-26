<?php

namespace Modules\AffiliatePos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class OrderComment extends Model
{
    protected $table = 'affiliate_pos_order_comments';
    protected $guarded = [];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
