<?php

namespace App\Models;

use App\Helpers\FinanceHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;

class CostTransaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function ($costTransaction) {
            if (empty($costTransaction->currency_id)) {
                throw new \Exception("CostTransaction is missing an associated currency relation (currency_id cannot be null).");
            }
            $currency = $costTransaction->currency_id;
            $businessCurrencyId = \App\Models\AdminSettings::business_currency();

            $date = $costTransaction->created_at ?? now();
            $costTransaction->business_amount = \App\Models\CurrenciesExchange::RateByDateNoRound(
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function recurringSources(): BelongsToMany
    {
        return $this->belongsToMany(RecurringCost::class, 'recurring_cost_transactions', 'cost_transaction_id', 'recurring_cost_id')
            ->withPivot([]);
    }

    public function scopeExcludingSalaries(Builder $query): Builder
    {
        return $query->where('reason', '!=', 'salary');
    }

    public function scopeInYearMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
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
        $c->currency = $currency ?? optional($user)->currency_id;

        DB::transaction(function () use ($c, $user) {
            $c->save();
            if ($user) {
                \App\Helpers\BalancesHelper::instance()->CalcCostBalance($user);
            }
        });
        return $c->id;
    }

}