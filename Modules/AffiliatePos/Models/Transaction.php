<?php

namespace Modules\AffiliatePos\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'affiliate_pos_transactions';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function relation()
    {
        return $this->morphTo();
    }
}
