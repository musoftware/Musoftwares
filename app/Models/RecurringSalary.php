<?php

namespace App\Models;

use App\Helpers\FinanceHelper;
use App\Traits\HasRecurringSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property float $amount
 * @property int $currency_id
 * @property string|null $reason
 * @property string $start_date
 * @property string $current_date
 * @property string $recurring
 * @property int|null $recurring_times
 * @property mixed $recurring_times_day
 * @property mixed $recurring_times_month
 * @property mixed $recurring_times_year
 * @property bool $is_active
 */
class RecurringSalary extends Model
{
    use SoftDeletes, HasRecurringSchedule;

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

    public function generateMissingRuns(?Carbon $until = null): int
    {
        $timezone = config('app.timezone', 'Africa/Cairo');
        $until = $until ? $until->copy()->setTimezone($timezone)->endOfDay() : Carbon::today($timezone)->endOfDay();
        $startDate = Carbon::parse($this->start_date)->setTimezone($timezone)->startOfDay();

        if ($startDate->gt($until)) {
            return 0;
        }

        $user = User::find($this->user_id);
        if (! $user) {
            return 0;
        }

        $reason = trim((string) ($this->reason ?? $this->title));
        if ($reason === '') {
            $reason = 'Recurring salary #'.$this->id;
        }

        $generatedCount = 0;
        $diffDays = $startDate->diffInDays($until);

        for ($i = 0; $i <= $diffDays; $i++) {
            $checkDate = $startDate->copy()->addDays($i);
            if ($this->isToday($checkDate) && ! $this->createdBefore($checkDate)) {
                $targetTime = $checkDate->copy()->setTime(3, 0, 0);

                $tid = $user->add_balance(
                    (float) $this->amount,
                    $reason,
                    'earned',
                    (int) $this->currency_id,
                    null
                );

                if ($tid) {
                    Transaction::where('id', $tid)->update([
                        'created_at' => $targetTime,
                        'updated_at' => $targetTime,
                    ]);

                    DB::table('recurring_salary_transactions')->insert([
                        'recurring_salary_id' => $this->id,
                        'transaction_id' => $tid,
                        'unique_id' => $this->unique_id($checkDate),
                        'created_at' => $targetTime,
                        'updated_at' => $targetTime,
                    ]);

                    $generatedCount++;
                }
            }
        }

        return $generatedCount;
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
