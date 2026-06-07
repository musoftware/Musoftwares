<?php

namespace Modules\Booking\app\Features\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Booking\app\Core\Traits\TenantAwareModel;

class BookingService extends Model
{
    use SoftDeletes, TenantAwareModel;

    protected $table = 'booking_services';

    protected $guarded = ['id'];
}
