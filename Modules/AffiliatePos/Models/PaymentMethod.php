<?php

namespace Modules\AffiliatePos\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $table = 'affiliate_pos_payment_methods';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function title()
    {
        if ($this->type === 'bank') {
            return $this->bank . ' ( ' . $this->bank_number . ' ) ';
        }
        return $this->mobile;
    }
}
