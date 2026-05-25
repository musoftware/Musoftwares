<?php

namespace Modules\Booking\Features\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Booking\Core\Traits\TenantAwareModel;

class BookingService extends Model
{
    use SoftDeletes, TenantAwareModel;

    protected $table = 'booking_services';

    protected $guarded = ['id'];
}
