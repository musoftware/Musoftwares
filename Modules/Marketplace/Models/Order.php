<?php

namespace Modules\Marketplace\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'marketplace_orders';
    protected $guarded = [];

    public function buyer() { return $this->belongsTo(User::class, 'buyer_id'); }
    public function seller() { return $this->belongsTo(User::class, 'seller_id'); }
}
