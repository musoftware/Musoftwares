<?php

namespace Modules\Booking\app\Features\Widget\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BookingWidget extends Model
{
    protected $table = 'booking_widgets';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'name',
        'type',
        'primary_color',
        'button_text',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function domains()
    {
        return $this->hasMany(BookingWidgetDomain::class, 'widget_id');
    }

    public function logs()
    {
        return $this->hasMany(BookingWidgetLog::class, 'widget_id');
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
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
