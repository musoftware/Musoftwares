<?php

namespace Modules\Booking\app\Features\Recurring\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Modules\Booking\Models\Booking;

class RecurringSeries extends Model
{
    use SoftDeletes;

    protected $table = 'booking_recurring_series';

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'resource_id',
        'service_id',
        'rrule',
        'starts_at',
        'ends_at',
        'duration_minutes',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function occurrences()
    {
        return $this->hasMany(Booking::class, 'recurring_series_id');
    }

    public function exceptions()
    {
        return $this->hasMany(RecurringException::class, 'series_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app()->bound('currentTenant') ? app('currentTenant')->id : null;
            if (!$tenantId && auth()->check()) {
                $tenantId = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
            }
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
            }
        });
    }
}
