<?php

namespace Modules\Booking\app\Features\CustomDomains\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingCustomDomain extends Model
{
    use SoftDeletes;

    protected $table = 'booking_custom_domains';

    protected $fillable = [
        'tenant_id',
        'domain',
        'status',
        'ssl_status',
        'verification_token',
        'is_primary',
        'connected_at',
        'verified_at',
        'last_checked_at',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'connected_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_checked_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        // Scope queries to current tenant when active
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
            
            // Auto generate verification token
            if (empty($model->verification_token)) {
                $model->verification_token = \Illuminate\Support\Str::random(32);
            }
        });
    }
}
