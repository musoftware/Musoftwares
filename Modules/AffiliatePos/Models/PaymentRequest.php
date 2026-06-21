<?php

namespace Modules\AffiliatePos\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PaymentRequest extends Model
{
    use SoftDeletes;

    protected $table = 'affiliate_pos_payment_requests';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment_method()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'relation');
    }
}
