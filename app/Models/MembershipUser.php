<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class MembershipUser extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'membership_id',
        'user_id',
        'currency',
        'amount',
        'serial',
        'expires_at',
    ];



    protected $casts = [
        'expires_at' => 'date',
    ];

    public function membership()
    {
        return $this->belongsTo(Membership::class, 'membership_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function current_price()
    {
        if (Auth::check()) {
            return round(CurrenciesExchange::RateToday($this->amount, $this->currency, Auth::user()->currency));
        } else {
            return $this->amount;
        }
    }

    public function price_str()
    {
        if (Auth::check()) {
            return \App\Helper\FinanceHelper::instance()->format_money($this->current_price(), Auth::user()->currency);
        } else {
            return \App\Helper\FinanceHelper::instance()->format_money($this->current_price(), $this->currency);
        }
    }


}
