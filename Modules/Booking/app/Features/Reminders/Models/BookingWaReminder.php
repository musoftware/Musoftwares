<?php

namespace Modules\Booking\app\Features\Reminders\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class BookingWaReminder extends Model
{
    protected $table = 'booking_wa_reminders';

    protected $fillable = [
        'tenant_id',
        'booking_id',
        'template_id',
        'status',
        'scheduled_at',
        'sent_at',
        'phone',
        'message',
        'error_log',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    /**
     * Get the template that generated this reminder.
     */
    public function template()
    {
        return $this->belongsTo(BookingWaTemplate::class, 'template_id');
    }

    /**
     * Get the booking associated with this reminder.
     * We assume \Modules\Booking\Models\Booking exists.
     */
    public function booking()
    {
        return $this->belongsTo(\Modules\Booking\Models\Booking::class, 'booking_id');
    }

    /**
     * The "booted" method of the model.
     */
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
