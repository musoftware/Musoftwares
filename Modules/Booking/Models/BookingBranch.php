<?php

namespace Modules\Booking\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingBranch extends Model
{
    protected $table = 'booking_branches';

    protected $fillable = [
        'tenant_id',
        'name',
        'address',
        'timezone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (app()->bound('currentTenant') && app('currentTenant')) {
                $builder->where('tenant_id', app('currentTenant')->id);
            } elseif (auth()->check() && auth()->user()->tenant_id) {
                $builder->where('tenant_id', auth()->user()->tenant_id);
            }
        });
        
        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = auth()->user()->tenant_id;
            }
        });
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'branch_id');
    }
}
