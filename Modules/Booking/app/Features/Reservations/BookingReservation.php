<?php

namespace Modules\Booking\Features\Reservations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Booking\Core\Traits\TenantAwareModel;
use Modules\Booking\Features\Resources\BookingResource;
use Modules\Booking\Features\Services\BookingService;

class BookingReservation extends Model
{
    use SoftDeletes, TenantAwareModel;

    protected $table = 'booking_reservations';

    protected $guarded = ['id'];

    public function resource()
    {
        return $this->belongsTo(BookingResource::class, 'resource_id');
    }

    public function service()
    {
        return $this->belongsTo(BookingService::class, 'service_id');
    }

    // A relation to Customer would go here if BookingCustomer is used.
}
