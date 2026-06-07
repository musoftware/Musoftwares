<?php

namespace Modules\Booking\app\Features\GroupSessions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class GroupSession extends Model
{
    protected $table = 'booking_group_sessions';

    protected $fillable = [
        'tenant_id',
        'resource_id',
        'title',
        'starts_at',
        'ends_at',
        'max_capacity',
        'min_capacity',
        'price',
        'status',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'price' => 'decimal:2',
    ];

    public function participants()
    {
        return $this->hasMany(GroupParticipant::class, 'group_session_id');
    }

    public function waitlist()
    {
        return $this->hasMany(GroupWaitlist::class, 'group_session_id');
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
