<?php

namespace Modules\Booking\app\Features\GcalSync\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class GoogleCalendar extends Model
{
    protected $table = 'booking_google_calendars';

    protected $fillable = [
        'tenant_id',
        'account_id',
        'calendar_id',
        'name',
        'sync_direction',
        'is_active',
        'sync_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function account()
    {
        return $this->belongsTo(GoogleAccount::class, 'account_id');
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
