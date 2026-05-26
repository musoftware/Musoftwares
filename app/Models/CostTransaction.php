<?php

namespace App\Models;

use App\Helpers\FinanceHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CostTransaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function ($costTransaction) {
            $currency = $costTransaction->currency_id ?? \App\Models\AdminSettings::business_currency();
            $businessCurrencyId = \App\Models\AdminSettings::business_currency();
            
            $date = $costTransaction->created_at ?? now();
            $costTransaction->business_amount = \App\Models\CurrenciesExchange::RateByDate(
                $date,
                $costTransaction->amount,
                $currency,
                $businessCurrencyId
            );
            $costTransaction->business_calculated = true;
        });
    }

    public function getCurrencyAttribute()
    {
        return $this->attributes['currency_id'] ?? null;
    }

    public function setCurrencyAttribute($value)
    {
        $this->attributes['currency_id'] = $value;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function amount_str()
    {
        return FinanceHelper::instance()->format_money($this->amount, $this->currency_id);
    }

    public static function add_cost_balance($user, $amount, $reason, $currency = null, $project = null)
    {
        if ($amount == 0) return null;
        $user_id = null;
        if (is_object($user)) {
            $user_id = $user->id;
        } elseif ($user != null) {
            $user = User::find($user);
            $user_id = $user->id;
        }

        $c = new CostTransaction();
        $c->user_id = $user_id;
        if (is_numeric($project) || is_string($project)) {
            $c->project_id = $project;
        } else {
            $c->project_id = optional($project)->id;
        }
        $c->amount = $amount;
        $c->reason = $reason;
        $c->currency = $currency;

        DB::transaction(function () use ($c, $project, $amount, $user) {
            $c->save();
            optional($user)->increment('total_cost', $amount);
        });
        return $c->id;
    }

}
