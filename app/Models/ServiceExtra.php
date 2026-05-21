<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceExtra extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'title',
        'price',
        'duration_days',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function buyer_price($user = null)
    {
        $buyer_price = round($this->price * 1.12);
        if ($user != null && empty($user->ref_user_id)) {
            return $this->price + (($buyer_price - $this->price) * 0.95);
        }
        return $buyer_price;
    }

    public function current_price()
    {
        if (\Illuminate\Support\Facades\Auth::check()) {
            return round(\App\Models\CurrenciesExchange::RateToday($this->buyer_price(\Illuminate\Support\Facades\Auth::user()), $this->service->currency, \Illuminate\Support\Facades\Auth::user()->currency));
        } else {
            return round(\App\Models\CurrenciesExchange::RateToday($this->buyer_price(\Illuminate\Support\Facades\Auth::user()), $this->service->currency, 2));
        }
    }

    public function current_price_str()
    {
        if (\Illuminate\Support\Facades\Auth::check()) {
            return \App\Helpers\FinanceHelper::instance()->format_money($this->current_price(), \Illuminate\Support\Facades\Auth::user()->currency);
        } else {
            return \App\Helpers\FinanceHelper::instance()->format_money($this->current_price(), 2);
        }
    }
}
