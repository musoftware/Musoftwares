<?php

namespace Modules\Booking\app\Features\MultiBranch\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

use Illuminate\Database\Eloquent\SoftDeletes;

class BookingBranchUser extends Pivot
{
    use SoftDeletes;

    protected $table = 'booking_branch_users';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'user_id',
        'role',
    ];

    protected static function booted(): void
    {
        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
            }
        });
    }
}
