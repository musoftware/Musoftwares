<?php

namespace App\Models;

use App\Helpers\BalancesHelper;
use App\Helpers\FinanceHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class CostTransaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function ($costTransaction) {
            if (empty($costTransaction->currency_id)) {
                throw new \Exception('CostTransaction is missing an associated currency relation (currency_id cannot be null).');
            }
            $currency = $costTransaction->currency_id;
            $businessCurrencyId = AdminSettings::business_currency();

            $date = $costTransaction->created_at ?? now();
            $costTransaction->business_amount = CurrenciesExchange::RateByDateNoRound(
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeExcludingSalaries(Builder $query): Builder
    {
        return $query->where('reason', '!=', 'salary');
    }

    public function scopeInYearMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('created_at', $year)->whereMonth('created_at', $month);
    }

    public function scopeByCategory(Builder $query, ?string $category): Builder
    {
        if ($category === null || $category === '') {
            return $query;
        }

        return $query->where('category', $category);
    }

    public function scopeForUser(Builder $query, $userId): Builder
    {
        if (empty($userId)) {
            return $query;
        }

        return $query->where('user_id', $userId);
    }

    public function scopeForProject(Builder $query, $projectId): Builder
    {
        if (empty($projectId)) {
            return $query;
        }

        return $query->where('project_id', $projectId);
    }

    public function scopeForCurrency(Builder $query, $currencyId): Builder
    {
        if (empty($currencyId)) {
            return $query;
        }

        return $query->where('currency_id', $currencyId);
    }

    public function scopeAmountBetween(Builder $query, $min, $max): Builder
    {
        if ($min !== null && $min !== '') {
            $query->where('amount', '>=', (float) $min);
        }
        if ($max !== null && $max !== '') {
            $query->where('amount', '<=', (float) $max);
        }

        return $query;
    }

    public function scopeRecurringOnly(Builder $query, bool $value = true): Builder
    {
        if (! $value) {
            return $query;
        }

        return $query->whereHas('recurringSources');
    }

    public function scopeWithTrashedIncluded(Builder $query, bool $value = true): Builder
    {
        if ($value) {
            return $query->withTrashed();
        }

        return $query;
    }

    public function amount_str()
    {
        return FinanceHelper::instance()->format_money($this->amount, $this->currency_id);
    }

    public static function add_cost_balance($user, $amount, $reason, $currency = null, $project = null, $createdAt = null)
    {
        if ($amount == 0) {
            return null;
        }
        $user_id = null;
        if (is_object($user)) {
            $user_id = $user->id;
        } elseif ($user != null) {
            $user = User::find($user);
            $user_id = $user->id;
        }

        $c = new CostTransaction;
        $c->user_id = $user_id;
        if (is_numeric($project) || is_string($project)) {
            $c->project_id = $project;
        } else {
            $c->project_id = optional($project)->id;
        }
        $c->amount = $amount;
        $c->reason = $reason;
        $c->currency = $currency ?? optional($user)->currency_id;
        if ($createdAt) {
            $c->created_at = Carbon::parse($createdAt);
            $c->updated_at = Carbon::parse($createdAt);
        }

        DB::transaction(function () use ($c, $user) {
            $c->save();
            if ($user) {
                BalancesHelper::instance()->CalcCostBalance($user);
            }
        });

        return $c->id;
    }
}
