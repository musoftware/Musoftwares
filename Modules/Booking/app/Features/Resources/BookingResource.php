<?php

namespace Modules\Booking\app\Features\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Booking\app\Core\Traits\TenantAwareModel;
use Modules\Booking\app\Features\Availability\BookingResourceSchedule;
use Modules\Booking\app\Features\Availability\BookingScheduleException;
use Modules\Booking\app\Features\Availability\BookingScheduleRule;
use Modules\Booking\app\Features\Availability\BookingResourceTimeOff;

class BookingResource extends Model
{
    use SoftDeletes, TenantAwareModel;

    protected $table = 'booking_resources';

    protected $guarded = ['id'];

    public function schedules()
    {
        return $this->hasMany(BookingResourceSchedule::class, 'resource_id');
    }

    public function exceptions()
    {
        return $this->hasMany(BookingScheduleException::class, 'resource_id');
    }

    public function rules()
    {
        return $this->hasMany(BookingScheduleRule::class, 'resource_id');
    }

    public function timeOffs()
    {
        return $this->hasMany(BookingResourceTimeOff::class, 'resource_id');
    }
}
