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
        $timezone = config('app.timezone', 'Africa/Cairo');
        $today = Carbon::today($timezone);
        $from = $from ? $from->copy()->setTimezone($timezone)->startOfDay() : $today;

        if (! empty($this->start_date)) {
            $startDate = Carbon::parse($this->start_date)->setTimezone($timezone)->startOfDay();
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
}
