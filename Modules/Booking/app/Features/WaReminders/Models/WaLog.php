<?php

namespace Modules\Booking\app\Features\WaReminders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Modules\Booking\Models\Booking;

class WaLog extends Model
{
    protected $table = 'booking_wa_logs';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'phone_number',
        'message_content',
        'provider_message_id',
        'delivery_status',
        'error_reason',
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
