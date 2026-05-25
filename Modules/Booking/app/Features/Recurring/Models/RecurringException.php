<?php

namespace Modules\Booking\app\Features\Recurring\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class RecurringException extends Model
{
    protected $table = 'booking_recurring_exceptions';

    protected $fillable = [
        'tenant_id',
        'series_id',
        'exception_date',
        'reason',
        'status',
    ];

    protected $casts = [
        'exception_date' => 'date',
    ];

    public function series()
    {
        return $this->belongsTo(RecurringSeries::class, 'series_id');
    }

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app()->bound('currentTenant') ? app('currentTenant')->id : null;
            if (!$tenantId && auth()->check()) {
                $tenantId = auth()->user()->tenant_id;
            }
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });

        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }
}
