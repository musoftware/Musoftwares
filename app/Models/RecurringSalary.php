<?php

namespace App\Models;

use App\Helpers\FinanceHelper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class RecurringSalary extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): BelongsToMany
    {
        return $this->belongsToMany(Transaction::class, 'recurring_salary_transactions')
            ->withPivot('unique_id')
            ->withTimestamps();
    }

    public function current_amount(): float
    {
        return (float) $this->amount;
    }

    public function current_amount_str(): string
    {
        return FinanceHelper::instance()->format_money($this->current_amount(), $this->currency_id);
    }

    /**
     * Apply salary for today if due: exchange amount to user currency on date, add earned transaction.
     */
    public function apply(): void
    {
        $now = Carbon::now();
        if (! $this->isToday($now)) {
            return;
        }
        if ($this->createdBefore($now)) {
            return;
        }

        $user = User::find($this->user_id);
        if (! $user) {
            return;
        }

        $reason = trim((string) ($this->reason ?? $this->title));
        if ($reason === '') {
            $reason = 'Recurring salary #'.$this->id;
        }

        $tid = $user->add_balance(
            (float) $this->amount,
            $reason,
            'earned',
            (int) $this->currency_id,
            null
        );
        if ($tid) {
            DB::table('recurring_salary_transactions')->insert([
                'recurring_salary_id' => $this->id,
                'transaction_id' => $tid,
                'unique_id' => $this->unique_id($now),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    private function unique_id($date): string
    {
        $d = $date instanceof Carbon ? $date->format('Y-m-d') : date('Y-m-d', strtotime($date));

        return $this->id.'-'.$d;
    }

    public function createdBefore($date): bool
    {
        $row = DB::selectOne(
            'SELECT COUNT(id) AS cnt FROM recurring_salary_transactions WHERE unique_id = ?',
            [$this->unique_id($date)]
        );

        return (int) $row->cnt > 0;
    }

    public function isToday($date): bool
    {
        if ($this->recurring === 'day') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInDays($t);
            if ($diff === 0) {
                return true;
            }

            return $diff % $this->recurring_times === 0;
        }

        if ($this->recurring === 'week') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInWeeks($t);
            $days = $this->recurring_times_week ? explode(',', $this->recurring_times_week) : [];

            if ($diff % $this->recurring_times === 0) {
                return in_array(date('l', $date->timestamp), $days, true);
            }
        }

        if ($this->recurring === 'month') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInMonths($t);
            $days = $this->recurring_times_month ? explode(',', $this->recurring_times_month) : [];

            if ($diff % $this->recurring_times === 0) {
                if ((int) date('n', $date->timestamp) === 2) {
                    foreach ($days as $day) {
                        if ((int) $day > (int) date('t', $date->timestamp)) {
                            return (int) date('t', $date->timestamp) === (int) date('d', $date->timestamp);
                        }
                    }
                }
                $dayNum = (int) date('d', $date->timestamp);
                $daysInt = array_map('intval', $days);

                return in_array($dayNum, $daysInt, true);
            }
        }

        if ($this->recurring === 'year') {
            $t = Carbon::parse($this->current_date);
            $diff = $date->diffInYears($t);
            $dayMonths = $this->recurring_times_year ? explode(',', $this->recurring_times_year) : [];

            if ($diff % $this->recurring_times === 0) {
                return in_array(date('j-n', $date->timestamp), $dayMonths, true);
            }
        }

        return false;
    }

    public function details(): string
    {
        if ($this->recurring === 'day') {
            return '';
        }
        if ($this->recurring === 'month') {
            return (string) $this->recurring_times_month;
        }
        if ($this->recurring === 'week') {
            return (string) $this->recurring_times_week;
        }
        if ($this->recurring === 'year') {
            return (string) $this->recurring_times_year;
        }

        return '';
    }
}
