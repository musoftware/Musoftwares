<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AdminSettings extends Model
{
    use HasFactory;

    /** Cache TTL for settings (seconds). */
    const SETTINGS_CACHE_TTL = 300;

    public static function GetValue($key, $default = null)
    {
        $cacheKey = 'admin_settings_' . $key;
        return Cache::remember($cacheKey, self::SETTINGS_CACHE_TTL, function () use ($key, $default) {
            $find = AdminSettings::where('setting_key', $key)->first();
            return $find === null ? $default : $find->setting_value;
        });
    }

    public static function SetValue($key, $value)
    {
        $find = AdminSettings::where('setting_key', $key)->first();
        if ($find == null) {
            $new_setting = new AdminSettings();
            $new_setting->setting_key = $key;
            $new_setting->setting_value = $value;
            $new_setting->save();
        } else {
            $find->setting_key = $key;
            $find->setting_value = $value;
            $find->save();
        }
        Cache::forget('admin_settings_' . $key);
    }

    public static function business_currency()
    {
        return static::GetValue('business_currency', '2');
    }

    public static function business_currency_name()
    {
        return Currency::find(static::business_currency())->currency;
    }

    public static function currencies_exist()
    {
        return Transaction::query()->select('currency')->groupBy('currency')->get();
    }


    public static function business_phone()
    {
        return static::GetValue('business_phone', '01001253077');
    }

    public static function business_address()
    {
        return static::GetValue('business_address', 'Egypt');
    }

    public static function business_email()
    {
        return static::GetValue('business_email', 'musoftwarex810@gmail.com');
    }

    /**
     * Whether Friday is allowed as a working day (1 = yes, 0 = no).
     * Used for timers, scheduling, and any logic that treats Friday as off.
     */
    public static function friday_work_allowed()
    {
        return static::GetValue('friday_work_allowed', '1') === '1';
    }

    /**
     * Calculates the recommended hourly rate based on average expenses from the last 6 months,
     * adjusted by the default overhead percentage, divided by 8 hours.
     * Matches the logic in ProjectPriceCalculator's Overhead Cost section.
     * Cached per currency for 10 minutes to avoid heavy queries on every invoice show.
     */
    public static function GetRecommendedHourlyRate($currencyId = null)
    {
        if ($currencyId === null) {
            $currencyId = static::business_currency();
        }

        $cacheKey = 'admin_recommended_hourly_rate_' . $currencyId;
        return (float) Cache::remember($cacheKey, 600, function () use ($currencyId) {
            // Calculate average monthly cost from last 6 months (As in ProjectPriceCalculator)
            $startDate = now()->subMonths(6)->startOfMonth();
            $endDate = now()->endOfMonth();

            $costs = \Modules\Core\Models\CostTransaction::whereBetween('created_at', [$startDate, $endDate])->get();

            $totalCost = 0;
            foreach ($costs as $cost) {
                $totalCost += \Modules\Core\Models\CurrenciesExchange::RateToday($cost->amount, $cost->currency, 2);
            }

            $avgMonthlyCost = $totalCost > 0 ? round($totalCost / 6, 2) : 0;
            $dailyCostRate = round($avgMonthlyCost / 30, 2);
            $adjustment = (int) static::GetValue('overhead_cost_default', 150);
            $recommendedDailyRate = $dailyCostRate * ($adjustment / 100);
            $recommendedHourlyRate = $recommendedDailyRate / 8;

            return round(\Modules\Core\Models\CurrenciesExchange::RateToday($recommendedHourlyRate, 2, $currencyId), 2);
        });
    }


}
