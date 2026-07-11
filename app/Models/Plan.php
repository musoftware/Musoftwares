<?php

namespace App\Models;

use App\Helpers\FinanceHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Plan extends Model
{
    use HasFactory, SoftDeletes;

    protected $softDelete = true;

    protected $guarded = [];

    protected function current_currency()
    {
        if (Auth::check()) {
            return Auth::user()->currency;
        } else {
            return 1;
        }
    }

    /**
     * Apply plan discount to an amount. Uses single discount_percentage.
     */
    public function calcDiscount(float $amount): float
    {
        $pct = (float) ($this->discount_percentage ?? 0);
        if ($pct <= 0) {
            return $amount;
        }

        return $amount - ($amount * $pct / 100);
    }

    public function current_plan_price()
    {
        $currencyId = $this->current_currency();
        $raw = CurrenciesExchange::RateToday($this->plan_price, $this->plan_currency, $currencyId);

        return FinanceHelper::instance()->price_fixer($raw, $currencyId);
    }

    public function plan_duration_short()
    {
        if ($this->plan_duration == 30) {
            return 'mo'; // 30 days as a month
        } elseif ($this->plan_duration == 7) {
            return 'wk'; // 7 days as a week
        } elseif ($this->plan_duration == 365) {
            return 'yr'; // 365 days as a year
        } elseif ($this->plan_duration % 30 == 0) {
            return ($this->plan_duration / 30).'mo'; // Any multiple of 30 days as months
        } elseif ($this->plan_duration % 7 == 0) {
            return ($this->plan_duration / 7).'wk'; // Any multiple of 7 days as weeks
        } elseif ($this->plan_duration % 365 == 0) {
            return ($this->plan_duration / 365).'yr'; // Any multiple of 365 days as years
        } else {
            return $this->plan_duration.'d'; // Default: remaining days
        }
    }

    public function current_plan_price_str()
    {
        return FinanceHelper::instance()->format_money($this->current_plan_price(), $this->current_currency());
    }

    public function getPlanDescriptionAttribute()
    {
        return $this->attributes['plan_description'] ?? 'Standard Subscription Plan';
    }
}
