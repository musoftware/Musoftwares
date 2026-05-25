<?php

namespace Modules\Booking\app\Features\GroupSessions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class GroupParticipant extends Model
{
    protected $table = 'booking_group_session_participants';

    protected $fillable = [
        'tenant_id',
        'group_session_id',
        'customer_id',
        'status',
    ];

    public function session()
    {
        return $this->belongsTo(GroupSession::class, 'group_session_id');
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
