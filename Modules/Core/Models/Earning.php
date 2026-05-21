<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Earning extends Model
{
    use HasFactory;

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
        return $this->belongsTo(Currency::class, 'currency');
    }

    public static function clearing_balance()
    {
        $query = Earning::query()->select(DB::raw('sum(amount) as amount, currency, FROM_UNIXTIME(AVG(UNIX_TIMESTAMP(created_at))) AS avg_date'));
        if (Schema::hasColumn('earnings', 'transaction_id')) {
            $query->whereNull('transaction_id');
        }
        $data = $query->groupBy('currency')->get();
        $amount = 0;
        foreach ($data as $commission) {
            $user_amount = CurrenciesExchange::RateByDate($commission->avg_date, $commission->amount, $commission->currency, \Modules\Core\Models\CurrenciesExchange::BusinessCurrency());
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
        $data = Earning::query()->select(DB::raw('sum(amount) as amount, currency'))->groupBy('currency')->get();

        $amount = 0;
        foreach ($data as $commission) {
            $user_amount = CurrenciesExchange::RateByDate($commission->created_at, $commission->amount, $commission->currency, \Modules\Core\Models\CurrenciesExchange::BusinessCurrency());
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

        $start_time = strtotime($this->created_at); // Assuming `start_date` is the beginning of the period.

        // Calculate total duration in seconds
        $total_duration = abs($end_time - $start_time);
        $elapsed_duration = abs($current_time - $start_time);

        // Calculate percentage of elapsed time
        $progress_percentage = ($elapsed_duration * 1.0 / $total_duration) * 100.0;

        return min(100, round($progress_percentage, 2));
    }


}
