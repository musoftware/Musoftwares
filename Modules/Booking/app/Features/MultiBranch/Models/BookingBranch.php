<?php

namespace Modules\Booking\app\Features\MultiBranch\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingBranch extends Model
{
    use SoftDeletes;

    protected $table = 'booking_branches';

    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        'phone',
        'is_main_branch',
        'is_active',
    ];

    protected $casts = [
        'is_main_branch' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: The users assigned to this branch.
     */
    public function users()
    {
        // Assuming the User model is App\Models\User. Adjust if it's different.
        return $this->belongsToMany(\App\Models\User::class, 'booking_branch_users', 'branch_id', 'user_id')
                    ->withPivot('role', 'tenant_id')
                    ->withTimestamps()
                    ->using(BookingBranchUser::class);
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Scope queries to current tenant
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
}
