<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class EmployeeAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'check_in',
        'check_out',
        'total_hours',
        'status', // 'present', 'absent', 'late'
        'completed_todos',
        'auto_checkout',
        'salary'
    ];

    protected $casts = [
        'check_in' => 'datetime',
        'check_out' => 'datetime',
        'auto_checkout' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function markAttendance()
    {
        // If it's a fresh record or the previous session has been checked-out, start a new session
        if (is_null($this->check_in) || !is_null($this->check_out)) {
            $this->check_in  = now();
            $this->check_out = null;   // reset for the new session

            // set or update status (late / present)
            $this->status = Carbon::now()->format('H:i') > '09:00' ? 'late' : 'present';
            $this->save();
        }
    }

    public function markCheckout($auto = false)
    {
        // Only checkout if currently "checked-in" and not yet "checked-out"
        if ($this->check_in && is_null($this->check_out)) {
            $this->check_out     = now();
            $this->auto_checkout = $auto;

            // Calculate worked minutes for this session then add to running total
            $workedMinutes       = $this->check_in->diffInMinutes($this->check_out);
            $workedHours         = round($workedMinutes / 60, 2); // keep 2-decimal precision

            $this->total_hours   = ($this->total_hours ?? 0) + $workedHours;

            // Calculate earnings and create transaction
            $this->calculateAndAddEarnings($workedHours);

            $this->save();
        }
    }

    /**
     * Calculate earnings based on hours worked and user salary
     * Formula: total_hours * (user->salary / 26 / 8)
     */
    public function calculateEarnings($hours = null)
    {
        $hours = $hours ?? $this->total_hours;

        if (!$hours || !$this->user || !$this->user->salary) {
            return 0;
        }

        // Daily rate = monthly salary / 26 working days
        // Hourly rate = daily rate / 8 hours
        $hourlyRate = $this->user->salary / 26 / 8;

        return round($hours * $hourlyRate, 2);
    }

    /**
     * Calculate earnings for the current session and add transaction
     */
    private function calculateAndAddEarnings($sessionHours)
    {
        if (!$this->user || !$this->user->salary || $sessionHours <= 0) {
            return;
        }

        $earnings = $this->calculateEarnings($sessionHours);

        if ($earnings > 0) {
            // Create transaction for earned amount
            $transaction = new \Modules\Core\Models\Transaction();
            $transaction->user_id = $this->user_id;
            $transaction->type = 'earned';
            $transaction->amount = $earnings;
            $transaction->reason = 'Employee salary for ' . $sessionHours . ' hours worked on ' . $this->check_in->format('Y-m-d');
            $transaction->currency = $this->user->currency ?? 1; // Default to currency 1 if not set
            $transaction->save();

            // Update user balance
            \App\Helpers\BalancesHelper::UpdateBalance($this->user);
        }
    }

    public static function getTodayAttendance($user_id)
    {
        return self::where('user_id', $user_id)
            ->whereDate('created_at', today())
            ->first();
    }

    public static function getMonthlyAttendance($user_id)
    {
        return self::where('user_id', $user_id)
            ->whereMonth('created_at', now()->month)
            ->get();
    }

    public static function autoCheckoutOverdue()
    {
        $overdue = self::whereNull('check_out')
            ->whereDate('check_in', today())
            ->where('check_in', '<=', now()->subHours(7))
            ->get();

        foreach ($overdue as $attendance) {
            $attendance->markCheckout(true);
        }
    }
}
