<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringNotice extends Model
{
    use SoftDeletes, HasFactory;

    protected $casts = [
        'is_active' => 'boolean',
    ];

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

    public function isToday($date)
    {
        if ($this->recurring == 'day') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInDays($t);
            if ($diff == 0) return true;
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

    public function isDueToday(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        return $this->isToday(Carbon::now());
    }

    public static function dueToday()
    {
        $now = Carbon::now();

        return static::where('is_active', true)
            ->get()
            ->filter(fn ($notice) => $notice->isToday($now))
            ->values();
    }

    public function scheduleLabel(): string
    {
        $label = 'Every ' . $this->recurring_times . ' ' . $this->recurring . '(s)';
        $details = $this->details();
        if ($this->recurring !== 'day' && $details) {
            $label .= ' on [' . $details . ']';
        }

        return $label;
    }
}
