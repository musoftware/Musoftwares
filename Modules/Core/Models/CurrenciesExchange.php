<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class CurrenciesExchange extends Model
{
    use HasFactory;

    protected $guarded = [];

    public static function is_exist($currency1, $currency2, $date): bool
    {
        $count = CurrenciesExchange::where('currency1', $currency1)
            ->where('currency2', $currency2)
            ->where('date_string', $date)
            ->count();
        return ($count > 0);
    }

    public static function Today()
    {
        $date = date('Y-m-d');
        $data = CurrenciesExchange::where('date_string', $date)->get();
        if (count($data) == 0) {
            return CurrenciesExchange::where('date_string', date('Y-m-d', strtotime('-1 day')))->get();
        } else {
            return $data;
        }
    }

    public static function RateByMonth($m, $y, $amount, $cur1, $cur2)
    {
        if ($cur1 == $cur2)
            return $amount;
        $date = $y . '-' . str_pad($m, 2, "0", STR_PAD_LEFT) . '-%';
        $ex = CurrenciesExchange::where('currency1', $cur1)
            ->where('currency2', $cur2)
            ->where('date_string', 'like', $date)
            ->select(DB::raw('avg(rate) as rate'))->first();

        return $ex->rate * $amount;
    }

    protected static $business_cur = null;

    public static function BusinessCurrency()
    {
        if (static::$business_cur == null) {
            static::$business_cur = AdminSettings::GetValue('business_currency', 2);
        }
        return static::$business_cur;
    }

    public static function RateBusiness($amount, $cur1)
    {
        return static::RateToday($amount, $cur1, static::BusinessCurrency());
    }

    public static function getCurrencySymbol($cur)
    {
        return Currency::find($cur)->currency;
    }


    public static function RateToday($amount, $cur1, $cur2)
    {
        $ex = CurrenciesExchange::where('currency1', trim($cur1))
            ->where('currency2', trim($cur2))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ex != null) {
            return round($ex->rate * $amount, 2);
        } else {
            return round($amount * static::Rate(date('Y-m-d'), $cur1, $cur2), 2);
        }
    }

    public static function RateTodayNoRound($amount, $cur1, $cur2)
    {
        $ex = CurrenciesExchange::where('currency1', trim($cur1))
            ->where('currency2', trim($cur2))
            ->orderBy('created_at', 'desc')
            ->first();

        if ($ex != null) {
            return number_format(round($ex->rate * $amount, 9), 9, '.', '');
        } else {
            return number_format(round($amount * static::Rate(date('Y-m-d'), $cur1, $cur2), 9), 9, '.', '');
        }
    }

    public static function RateByDate($date, $amount, $cur1, $cur2)
    {
        if ($cur1 == $cur2) {
            return 1 * $amount;
        }
        $date_str = date('Y-m-d', strtotime($date));
        $ex = CurrenciesExchange::where('currency1', trim($cur1))
            ->where('currency2', trim($cur2))
            ->where('date_string', trim($date_str))
            ->first();

        if ($ex == null) {
            $ex = CurrenciesExchange::where('currency1', trim($cur1))
                ->where('currency2', trim($cur2))
                ->orderByDesc('date_string')->first();
        }

        if ($ex == null) {
            Artisan::call('fetch:currencies');
        }

        return round($ex->rate * $amount, 2);
    }


    public static function RateByDateNoRound($date, $amount, $cur1, $cur2)
    {
        if ($cur1 == $cur2) {
            return 1 * $amount;
        }
        $date_str = date('Y-m-d', strtotime($date));
        $ex = CurrenciesExchange::where('currency1', trim($cur1))
            ->where('currency2', trim($cur2))
            ->where('date_string', trim($date_str))
            ->first();

        if ($ex == null) {
            if ($date_str == '1970-01-01') {
                $ex = CurrenciesExchange::where('currency1', trim($cur1))
                    ->where('currency2', trim($cur2))
                    ->orderBy('id')->first();
            }
        }

        return number_format(round($ex->rate * $amount, 11), 11, '.', '');
    }

    //

    public static function Rate($date, $cur1, $cur2)
    {
        if ($cur1 == $cur2) {
            return 1;
        }

        $ex = CurrenciesExchange::where('currency1', trim($cur1))
            ->where('currency2', trim($cur2))
            ->where('date_string', trim($date))
            ->first();

        if ($ex == null) {
            $ex = CurrenciesExchange::where('currency1', $cur1)
                ->where('currency2', $cur2)
                ->where('date_string', date('Y-m-d', strtotime($date) - (24 * 60 * 60)))
                ->first();
        }
        if ($ex == null) {
            return 1;
        }
        return $ex->rate;
    }

    public static function UsdToCost($ex_cost, $source)
    {
        if ($source == 'paypal') {
            $ex_cost = round($ex_cost / (1 - 0.05), 2);
        } elseif ($source == 'gumroad') {
            $ex_cost = round($ex_cost / (1 - 0.14), 2);
        }

        $real_cost = $ex_cost * 1.041 * 1.10;
        return $real_cost;
    }

    public static function EgpToCost($ex_cost, $source)
    {
        if ($source == 'paypal') {
            $ex_cost = round($ex_cost / (1 - 0.05), 2);
        } elseif ($source == 'gumroad') {
            $ex_cost = round($ex_cost / (1 - 0.14), 2);
        }
        $real_cost = $ex_cost;
        // $amount = round($real_cost * 1.25);
        return $real_cost;
    }


    public static function EgpToEgpCost($ex_cost)
    {
        $real_cost = $ex_cost * 1.01;
        $amount = round($real_cost * 1.10);

        return $amount;
    }

    //
    public static function UsdToEgpCost($ex_cost)
    {
        $real_cost = $ex_cost * 1.01;
        $amount = round($real_cost * 1.351);

        return $amount;
    }

    public static function EgpToUsdCost($ex_cost)
    {
        $real_cost = $ex_cost * 1.01;
        $amount = round($real_cost * 1.351);

        return $amount;
    }
}
