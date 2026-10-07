<?php

namespace App\Traits;

use Carbon\Carbon;

trait HasRecurringSchedule
{
    /**
     * Calculate the next execution date starting from today (or given date).
     */
    public function getNextExecutionDate(?Carbon $from = null): ?Carbon
    {
        $from = $from ? static::toBusinessDate($from) : static::businessToday();

        if (! empty($this->start_date)) {
            $startDate = Carbon::parse($this->start_date)->startOfDay();
            if ($from->lt($startDate)) {
                $from = $startDate->copy();
            }
        }

        $startDay = 0;
        if ($this->isToday($from) && $this->createdBefore($from)) {
            $startDay = 1;
        }

        for ($i = $startDay; $i <= 1826; $i++) {
            $checkDate = $from->copy()->addDays($i);
            if ($this->isToday($checkDate)) {
                if ($i > 0 || ! $this->createdBefore($checkDate)) {
                    return $checkDate;
                }
            }
        }

        return null;
    }

    /**
     * Today's date on the business calendar (config app.business_timezone).
     */
    protected static function businessToday(): Carbon
    {
        return static::toBusinessDate(Carbon::now());
    }

    /**
     * The business calendar date of a moment, returned as midnight in the app
     * timezone. Keeping every schedule date at app-timezone midnight keeps day,
     * week and month differences whole when compared with stored dates.
     */
    protected static function toBusinessDate(Carbon $moment): Carbon
    {
        $businessTimezone = config('app.business_timezone', 'Africa/Cairo');

        return Carbon::parse($moment->copy()->setTimezone($businessTimezone)->toDateString());
    }
}
