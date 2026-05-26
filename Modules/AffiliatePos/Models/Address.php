<?php

namespace Modules\AffiliatePos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'affiliate_pos_addresses';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
