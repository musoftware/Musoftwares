<?php

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Booking\app\Features\MultiBranch\Models\BookingBranchUser;

class BookingBranch extends Model
{
    use SoftDeletes;

    protected $table = 'booking_branches';

    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        'phone',
        'timezone',
        'is_main_branch',
        'is_active',
    ];

    protected $casts = [
        'is_main_branch' => 'boolean',
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

        // Ensure only one main branch exists per tenant
        static::saving(function ($model) {
            if ($model->is_main_branch) {
                static::withoutEvents(function () use ($model) {
                    BookingBranch::where('id', '!=', $model->id)
                        ->update(['is_main_branch' => false]);
                });
            }
        });
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'branch_id');
    }

    public function users()
    {
        // Assuming the User model is App\Models\User. Adjust if it's different.
        return $this->belongsToMany(\App\Models\User::class, 'booking_branch_users', 'branch_id', 'user_id')
                    ->withPivot('role', 'tenant_id')
                    ->withTimestamps()
                    ->using(BookingBranchUser::class);
    }
}
