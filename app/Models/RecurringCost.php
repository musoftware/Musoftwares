<?php

namespace App\Models;

use App\Helpers\FinanceHelper;
use App\Traits\HasRecurringSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $title
 * @property float $amount
 * @property int $currency_id
 * @property string $reason
 * @property string $start_date
 * @property string $current_date
 * @property string $recurring
 * @property int|null $recurring_times
 * @property mixed $recurring_times_day
 * @property mixed $recurring_times_month
 * @property mixed $recurring_times_year
 * @property bool $is_active
 */
class RecurringCost extends Model
{
    use HasFactory, SoftDeletes, HasRecurringSchedule;

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function current_amount()
    {
        return $this->amount;
    }

    public function current_amount_str()
    {
        return FinanceHelper::instance()->format_money($this->current_amount(), $this->currency_id);
    }

    public function transactions()
    {
        return $this->belongsToMany(CostTransaction::class, 'recurring_cost_transactions');
    }

    public function details()
    {
        if ($this->recurring == 'day') {
            return '';
        }
        if ($this->recurring == 'month') {
            return $this->recurring_times_month;
        }
        if ($this->recurring == 'week') {
            return $this->recurring_times_week;
        }
        if ($this->recurring == 'year') {
            return $this->recurring_times_year;
        }
    }

    public function apply()
    {
        $now = Carbon::now();
        if ($this->isToday($now)) {
            if (! $this->createdBefore($now)) {
                $c_id = CostTransaction::add_cost_balance(null, $this->amount, $this->reason, $this->currency_id, null);
                $this->transactions()->attach($c_id, [
                    'unique_id' => $this->unique_id($now),
                ]);
            }
        }
    }

    public function generateMissingRuns(?Carbon $until = null): int
    {
        $timezone = config('app.timezone', 'Africa/Cairo');
        $until = $until ? $until->copy()->setTimezone($timezone)->endOfDay() : Carbon::today($timezone)->endOfDay();
        $startDate = Carbon::parse($this->start_date)->setTimezone($timezone)->startOfDay();

        if ($startDate->gt($until)) {
            return 0;
        }

        $generatedCount = 0;
        $diffDays = $startDate->diffInDays($until);

        for ($i = 0; $i <= $diffDays; $i++) {
            $checkDate = $startDate->copy()->addDays($i);
            if ($this->isToday($checkDate) && ! $this->createdBefore($checkDate)) {
                $targetTime = $checkDate->copy()->setTime(3, 0, 0);

                $costTx = new CostTransaction;
                $costTx->amount = $this->amount;
                $costTx->reason = $this->reason;
                $costTx->currency_id = $this->currency_id;
                $costTx->created_at = $targetTime;
                $costTx->updated_at = $targetTime;
                $costTx->save();

                $this->transactions()->attach($costTx->id, [
                    'unique_id' => $this->unique_id($checkDate),
                ]);

                $generatedCount++;
            }
        }

        return $generatedCount;
    }

    private function unique_id($date)
    {
        if ($date instanceof Carbon) {
            $d = $date->format('Y-m-d');
        } else {
            $d = date('Y-m-d', strtotime($date));
        }

        return $this->id.'-'.$d;
    }

    public function createdBefore($date)
    {
        $is_exist = DB::selectOne('select count(id) as is_exist_count from recurring_cost_transactions where unique_id=?', [$this->unique_id($date)]);

        return $is_exist->is_exist_count == 1;
    }

    public function isToday($date)
    {
        if ($this->recurring == 'day') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInDays($t);
            if ($diff == 0) {
                return true;
            }

            return $diff % $this->recurring_times == 0;
        }

        if ($this->recurring == 'week') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInWeeks($t);

            $days = explode(',', $this->recurring_times_week);

            if ($diff % $this->recurring_times == 0) {
                return in_array(date('l', strtotime($date)), $days);
            }
        }

        if ($this->recurring == 'month') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInMonths($t);

            $days = explode(',', $this->recurring_times_month);

            if ($diff % $this->recurring_times == 0) {
                if (date('n', strtotime($date)) == '2') {
                    foreach ($days as $day) {
                        if ($day > date('t', strtotime($date))) {
                            return date('t', strtotime($date)) == date('d', strtotime($date));
                        }
                    }
                }

                return in_array(date('d', strtotime($date)), $days);
            }
        }

        if ($this->recurring == 'year') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInYears($t);
            $day_months = explode(',', $this->recurring_times_year);
            if ($diff % $this->recurring_times == 0) {
                return in_array(date('j-n', strtotime($date)), $day_months);
            }
        }

        return false;
    }

    public function delete_with_transactions()
    {
        $this->transactions()->delete();
        $this->delete();
    }

    public static function monthly()
    {
        return static::annual() / 12;
    }

    public static function monthly_str()
    {
        $b_currency = AdminSettings::GetValue('business_currency', '1');

        return FinanceHelper::instance()->format_money(static::monthly(), $b_currency);
    }

    public static function annual_str()
    {
        $b_currency = AdminSettings::GetValue('business_currency', '1');

        return FinanceHelper::instance()->format_money(static::annual(), $b_currency);
    }

    public static function annual()
    {
        $b_currency = AdminSettings::GetValue('business_currency', '1');
        $total_amount = 0;
        foreach (RecurringCost::where('is_active', true)->get() as $rCost) {
            $detail = explode(',', $rCost->details());

            $times_type = 0;
            if ($rCost->recurring == 'day') {
                $times_type = 365;
            }
            if ($rCost->recurring == 'month') {
                $times_type = 12;
            }
            if ($rCost->recurring == 'week') {
                $times_type = 51;
            }
            if ($rCost->recurring == 'year') {
                $times_type = 1;
            }

            $business_amount = CurrenciesExchange::RateToday($rCost->amount, $rCost->currency_id, $b_currency);
            $c_amount = $business_amount / $rCost->recurring_times * count($detail) * $times_type;
            $total_amount += $c_amount;
        }

        return $total_amount;
    }

    public static function chartData()
    {
        $b_currency = AdminSettings::GetValue('business_currency', '1');
        $data = [];
        foreach (RecurringCost::where('is_active', true)->get() as $rCost) {
            $details_str = $rCost->details();
            $detail = $details_str ? explode(',', (string) $details_str) : [];

            $times_type = 0;
            if ($rCost->recurring == 'day') {
                $times_type = 365;
            }
            if ($rCost->recurring == 'month') {
                $times_type = 12;
            }
            if ($rCost->recurring == 'week') {
                $times_type = 51;
            }
            if ($rCost->recurring == 'year') {
                $times_type = 1;
            }

            $business_amount = CurrenciesExchange::RateToday($rCost->amount, $rCost->currency_id, $b_currency);

            $multiplier = ($rCost->recurring == 'day') ? 1 : count($detail);

            $c_amount = $business_amount / max(1, $rCost->recurring_times) * $multiplier * $times_type;

            $reason = $rCost->reason ?: 'Other';
            if (! isset($data[$reason])) {
                $data[$reason] = 0;
            }
            $data[$reason] += $c_amount;
        }

        $chart = [];
        foreach ($data as $reason => $annual_amount) {
            $chart[] = [
                'name' => $reason,
                'annual' => round((float) $annual_amount, 2),
                'monthly' => round((float) $annual_amount / 12, 2),
            ];
        }

        usort($chart, function ($a, $b) {
            return $b['annual'] <=> $a['annual'];
        });

        return $chart;
    }
}
