<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Membership extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'currency',
        'amount',
        'color_hue_degree',
        'description',
        'is_active',
    ];

    public function programs()
    {
        return $this->hasMany(MembershipProgram::class, 'membership_id');
    }

    public function software()
    {
        $list = [];
        foreach ($this->programs()->get() as $item) {
            $list[] = $item->program;
        }
        return $list;
    }

    public function users()
    {
        return $this->hasMany(MembershipUser::class, 'membership_id');
    }

    public function premiumTools()
    {
        return $this->belongsToMany(PremiumTool::class, 'membership_premium_tools')
                    ->withPivot('is_enabled')
                    ->withTimestamps();
    }


    public function current_price()
    {
        if (Auth::check()) {
            $member = MembershipUser::query()->where('membership_id', $this->id)
                ->where('user_id', Auth::user()->id)->first();
            if ($member != null) {
                return round(CurrenciesExchange::RateToday($member->amount, $member->currency, Auth::user()->currency));
            }
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

    public function current_price_annual()
    {
        if (Auth::check()) {
            $member = MembershipUser::query()->where('membership_id', $this->id)
                ->where('user_id', Auth::user()->id)->first();
            if ($member != null) {
                return round(CurrenciesExchange::RateToday($member->amount * 10, $member->currency, Auth::user()->currency));
            }
            return round(CurrenciesExchange::RateToday($this->amount * 10, $this->currency, Auth::user()->currency));
        } else {
            return $this->amount * 10;
        }
    }

    public function price_str_annual()
    {
        if (Auth::check()) {
            return \App\Helper\FinanceHelper::instance()->format_money($this->current_price_annual(), Auth::user()->currency);
        } else {
            return \App\Helper\FinanceHelper::instance()->format_money($this->current_price_annual(), $this->currency);
        }
    }

    /**
     * Get the membership amount converted to USD
     *
     * @return float
     */
    public function getAmountInUSD()
    {
        // If the currency is already USD (currency ID 1), return the amount as is
        if ($this->currency == 1) {
            return $this->amount;
        }

        // Convert the amount to USD using the exchange rate
        return round(CurrenciesExchange::RateToday($this->amount, $this->currency, 1), 2);
    }

    /**
     * Get the annual membership amount converted to USD
     *
     * @return float
     */
    public function getAnnualAmountInUSD()
    {
        return round($this->getAmountInUSD() * 10, 2);
    }


}

