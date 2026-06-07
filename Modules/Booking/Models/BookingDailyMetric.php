<?php

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BookingDailyMetric extends Model
{
    protected $table = 'booking_daily_metrics';

    protected $fillable = [
        'tenant_id',
        'date',
        'total_bookings',
        'completed_bookings',
        'cancelled_bookings',
        'no_show_bookings',
        'total_revenue',
        'currency_id',
    ];

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }
}
