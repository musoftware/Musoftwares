<?php

namespace App\Models;

use App\Helpers\BalancesHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserReferralRequestWithdraw extends Model
{
    use HasFactory;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user_payment_method()
    {
        return $this->belongsTo(UserPaymentMethod::class, 'user_payment_method_id');
    }

    public function payoutMethod()
    {
        return $this->belongsTo(UserPaymentMethod::class, 'user_payment_method_id');
    }

    public function cost_transaction()
    {
        return $this->belongsTo(CostTransaction::class);
    }
    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function getCurrencyAttribute()
    {
        return $this->attributes['currency_id'] ?? null;
    }

    public function setCurrencyAttribute($value)
    {
        $this->attributes['currency_id'] = $value;
    }

    public function icon()
    {
        return $this->user_payment_method ? $this->user_payment_method->icon() : null;
    }

    public function type_name()
    {
        return $this->user_payment_method ? $this->user_payment_method->type_name() : null;
    }


    public function method_details()
    {
        return $this->user_payment_method ? $this->user_payment_method->method_details() : null;
    }

    public function equivalent_amount()
    {
        return CurrenciesExchange::RateToday($this->amount, $this->user->currency, $this->user_payment_method->currency);
    }


    public static function withdrawed_balance()
    {
        $data = static::query()->where('status', 'approved')->groupBy('currency')->select(DB::raw('sum(amount) as amount, currency'))->get();
        $amount = 0;
        foreach ($data as $commission) {
            $user_amount = CurrenciesExchange::RateByDate($commission->created_at, $commission->amount, $commission->currency, \App\Models\CurrenciesExchange::BusinessCurrency());
            $amount += $user_amount;
        }
        return $amount;
    }


    public static function CreateWithdraw($payoutMethod, $amount)
    {
        $new_withdraw = new UserReferralRequestWithdraw();
        $new_withdraw->user_id = $payoutMethod->user_id;
        $new_withdraw->currency = $payoutMethod->user->currency;
        $new_withdraw->payment_method = $payoutMethod->type_name();
        $new_withdraw->payment_info = $payoutMethod->method_data();
        $new_withdraw->amount = $amount;
        $new_withdraw->user_payment_method_id = $payoutMethod->id;

        DB::transaction(function () use ($new_withdraw) {
            $new_withdraw->save();
            $user = User::find($new_withdraw->user_id);
            $user->increment('withdrawing_commission', $new_withdraw->amount);
        });
        
        return $new_withdraw;
    }

    public function changeStatus($new_status)
    {
        DB::transaction(function () use ($new_status) {
            $user = User::find($this->user_id);

            if ($this->transaction_id == null && $new_status == 'approved') {
                $this->transaction_id = $this->user->add_balance(
                    -1 * abs($this->amount),
                    'Withdraw',
                    'sent',
                    $this->currency
                );
                $this->save();
            }

            static::two_field_increment(
                $user,
                'withdrawn_commission',
                'withdrawing_commission',
                $this->status,
                $new_status,
                $this->amount
            );

            $this->status = $new_status;
            $this->save();

            if ($new_status != 'approved') {
                optional($this->cost_transaction)->delete();
                optional($this->transaction)->delete();
            }
            if (class_exists(BalancesHelper::class)) {
                BalancesHelper::UpdateBalance($this->user);
            }
        });
    }

    public static function two_field_increment($user, $field1, $field2, $old_status, $new_status, $amount)
    {
        if ($old_status == $new_status) return;

        if (($old_status == 'pending' || $old_status == 'reviewing') && $new_status == 'approved') {
            $user->increment($field1, $amount);
            $user->decrement($field2, $amount);
        }
        if ($old_status == 'approved' && ($new_status == 'pending' || $new_status == 'reviewing')) {
            $user->decrement($field1, $amount);
            $user->increment($field2, $amount);
        }
        if ($old_status == 'declined' && ($new_status == 'pending' || $new_status == 'reviewing')) {
            $user->increment($field2, $amount);
        }
        if ($old_status == 'pending' && ($new_status == 'declined')) {
            $user->decrement($field2, $amount);
        }
        if ($old_status == 'approved' && ($new_status == 'declined')) {
            $user->decrement($field1, $amount);
            $user->increment($field2, $amount);
        }
    }

    /**
     * Scope to get pending withdrawal requests
     */
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'reviewing'])
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope to get recent withdrawal requests
     */
    public function scopeRecent($query, $limit = 10)
    {
        return $query->orderBy('created_at', 'desc')
            ->limit($limit);
    }
}
