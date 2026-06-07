<?php

namespace Modules\Booking\app\Features\WaConfirm\Models;

use Illuminate\Database\Eloquent\Model;

class BookingWaTemplate extends Model
{
    protected $table = 'booking_wa_templates';

    protected $fillable = [
        'tenant_id',
        'name',
        'body',
        'variables',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (app()->bound('currentTenant') && app('currentTenant')) {
                $builder->where('tenant_id', app('currentTenant')->id);
            } elseif (auth()->check() && (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id())) {
                $builder->where('tenant_id', (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()));
            }
        });
        
        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
            }
        });
    }
}
