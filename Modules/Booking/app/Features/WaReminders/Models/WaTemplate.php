<?php

namespace Modules\Booking\app\Features\WaReminders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class WaTemplate extends Model
{
    use SoftDeletes;

    protected $table = 'booking_wa_templates';

    protected $fillable = [
        'tenant_id',
        'type',
        'content',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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
