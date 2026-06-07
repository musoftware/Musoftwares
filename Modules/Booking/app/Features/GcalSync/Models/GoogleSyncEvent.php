<?php

namespace Modules\Booking\app\Features\GcalSync\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Modules\Booking\Models\Booking;

class GoogleSyncEvent extends Model
{
    protected $table = 'booking_google_sync_events';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'google_event_id',
        'calendar_id',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
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
