<?php

namespace Modules\Booking\app\Features\SmsNotifications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SmsSetting extends Model
{
    protected $table = 'booking_sms_settings';

    protected $fillable = [
        'tenant_id',
        'provider_name',
        'provider_credentials',
        'sender_id',
        'is_active',
    ];

    protected $casts = [
        'provider_credentials' => 'encrypted:array', // Secure credentials at rest!
        'is_active' => 'boolean',
    ];

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
