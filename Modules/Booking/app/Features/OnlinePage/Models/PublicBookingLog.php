<?php

namespace Modules\Booking\app\Features\OnlinePage\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Booking\Models\Booking;

class PublicBookingLog extends Model
{
    use SoftDeletes;

    protected $table = 'booking_public_booking_logs';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'source_ip',
        'status',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
