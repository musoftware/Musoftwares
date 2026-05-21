<?php

namespace App\Models\Operations;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRecurringTodo extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'priority',
        'recurring',
        'recurring_times',
        'recurring_times_week',
        'recurring_times_month',
        'recurring_times_year',
        'current_date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(EmployeeRecurringTodoTransaction::class);
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
        $now = \Carbon\Carbon::now();
        if ($this->isToday($now)) {
            if (!$this->createdBefore($now)) {
                
                // Create the actual Todo for the employee
                $todo = Todo::create([
                    'user_id' => $this->user_id,
                    'title' => $this->title,
                    'description' => $this->description,
                    'completed' => false,
                    'inDate' => $now->format('Y-m-d'),
                    'priority' => $this->priority,
                    'priorityColor' => match($this->priority) {
                        'high' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'secondary'
                    },
                    'tags' => json_encode([])
                ]);

                // Record the transaction
                EmployeeRecurringTodoTransaction::create([
                    'employee_recurring_todo_id' => $this->id,
                    'todo_id' => $todo->id,
                    'unique_id' => $this->unique_id($now)
                ]);
            }
        }
    }

    private function unique_id($date)
    {
        if ($date instanceof \Carbon\Carbon) {
            $d = $date->format('Y-m-d');
        } else {
            $d = date('Y-m-d', strtotime($date));
        }
        return $this->id . '-' . $d;
    }

    public function createdBefore($date)
    {
        $unique_id = $this->unique_id($date);
        return EmployeeRecurringTodoTransaction::where('unique_id', $unique_id)->exists();
    }

    public function isToday($date)
    {
        if ($this->recurring == 'day') {
            $t = \Carbon\Carbon::parse($this->current_date);
            $diff = $date->diffInDays($t);
            if ($diff == 0) return true;
            return $diff % $this->recurring_times == 0;
        }

        if ($this->recurring == 'week') {
            $t = \Carbon\Carbon::parse($this->current_date);
            $diff = $date->diffInWeeks($t);

            $days = explode(',', $this->recurring_times_week);

            if ($diff % $this->recurring_times == 0) {
                return in_array(date('l', strtotime($date)), $days);
            }
        }

        if ($this->recurring == 'month') {
            $t = \Carbon\Carbon::parse($this->current_date);
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
            $t = \Carbon\Carbon::parse($this->current_date);
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
}
