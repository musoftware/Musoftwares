<?php

namespace Modules\Booking\app\Features\TeamMembers\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class BookingTeamMember extends Model
{
    use SoftDeletes;

    protected $table = 'booking_team_members';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'job_title',
        'bio',
        'is_bookable',
        'is_active',
    ];

    protected $casts = [
        'is_bookable' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Relationship: The core system user.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
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
    }
}
