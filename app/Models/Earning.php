<?php

namespace App\Models;


use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Earning extends Model
{
    use SoftDeletes, HasFactory;

    protected static function booted(): void
    {
        static::saving(function ($earning) {
            // Force user currency conversion if different
            if ($earning->user_id && $earning->user) {
                $userCurrencyId = $earning->user->currency_id ?? \App\Models\AdminSettings::business_currency();
                $currentCurrencyId = $earning->currency_id ?? $earning->currency ?? \App\Models\AdminSettings::business_currency();

                if ($currentCurrencyId != $userCurrencyId) {
                    $date = $earning->created_at ?? now();
                    $earning->amount = \App\Models\CurrenciesExchange::RateByDateNoRound(
                        $date,
                        $earning->amount,
                        $currentCurrencyId,
                        $userCurrencyId
                    );

                    $earning->currency = $userCurrencyId;
                    $earning->currency_id = $userCurrencyId;
                }
            }

            // Track the first time a referred user generated a commission.
            // This drives the boosted commission window (10% for 1 month, per
            // 2026_03_02_..._add_first_referral_payment_at_to_users_table migration).
            if (!empty($earning->referred_user_id)) {
                $referred = $earning->referred_user ?? User::find($earning->referred_user_id);
                if ($referred && empty($referred->first_referral_payment_at)) {
                    $referred->first_referral_payment_at = $earning->created_at ?? now();
                    $referred->saveQuietly();
                }
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function referred_user()
    {
        return $this->belongsTo(User::class, 'referred_user_id');
    }

    public function currencyModel()
    {
        return $this->belongsTo(Currency::class, 'currency_id');
    }

    public static function clearing_balance()
    {
        $query = Earning::query()->select(DB::raw('sum(amount) as amount, currency_id, FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(created_at))) AS avg_date'));
        if (Schema::hasColumn('earnings', 'transaction_id')) {
            $query->whereNull('transaction_id');
        }
        $data = $query->groupBy('currency_id')->get();
        $amount = 0;
        foreach ($data as $commission) {
            $user_amount = CurrenciesExchange::RateByDateNoRound($commission->avg_date, $commission->amount, $commission->currency_id, \App\Models\CurrenciesExchange::BusinessCurrency());
            $amount += $user_amount;
        }
        return $amount;
    }

    public static function clearing_start_date()
    {
        $query = Earning::query()->select(DB::raw('min(convert_to_balance_on) as ctb_date'));
        if (Schema::hasColumn('earnings', 'transaction_id')) {
            $query->whereNull('transaction_id');
        }
        $data = $query->first();
        if ($data == null) {
            return null;
        } else {
            return $data->ctb_date;
        }
    }


    public static function clearing_last_date()
    {
        $query = Earning::query()->select(DB::raw('max(convert_to_balance_on) as ctb_date'));
        if (Schema::hasColumn('earnings', 'transaction_id')) {
            $query->whereNull('transaction_id');
        }
        $data = $query->first();
        if ($data == null) {
            return null;
        } else {
            return $data->ctb_date;
        }
    }


    public static function total_balance()
    {
        $data = Earning::query()->select(DB::raw('sum(amount) as amount, currency_id, FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(created_at))) AS avg_date'))->groupBy('currency_id')->get();

        $amount = 0;
        foreach ($data as $commission) {
            $user_amount = CurrenciesExchange::RateByDateNoRound($commission->avg_date, $commission->amount, $commission->currency_id, \App\Models\CurrenciesExchange::BusinessCurrency());
            $amount += $user_amount;
        }
        return $amount;
    }





    public function ref_progress_days()
    {
        if (time() > strtotime($this->convert_to_balance_on)) {
            return 'Cleared';
        }
        $diff = abs(strtotime($this->convert_to_balance_on) - time());

        $years = floor($diff / (365 * 60 * 60 * 24));
        $months = floor(($diff - $years * 365 * 60 * 60 * 24) / (30 * 60 * 60 * 24));
        $days = floor(($diff - $years * 365 * 60 * 60 * 24 - $months * 30 * 60 * 60 * 24) / (60 * 60 * 24));

        return trans_choice('common.days_count', $days, ['count' => $days]);
    }

    public function ref_progress_percentage()
    {
        $current_time = time();
        $end_time = strtotime($this->convert_to_balance_on);

        // Check if the current time is beyond the end time
        if ($current_time > $end_time) {
            return '100';
        }

        $start_time = strtotime($this->created_at); // Beginning of the clearing period.

        // Calculate total duration in seconds
        $total_duration = abs($end_time - $start_time);

        // Guard: if start and end are the same moment, treat as fully elapsed
        if ($total_duration === 0) {
            return '100';
        }

        $elapsed_duration = abs($current_time - $start_time);

        // Calculate percentage of elapsed time
        $progress_percentage = ($elapsed_duration * 1.0 / $total_duration) * 100.0;

        return min(100, round($progress_percentage, 2));
    }


}
