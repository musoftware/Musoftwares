<?php

namespace Modules\Booking\app\Features\QueueManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookingQueue extends Model
{
    use SoftDeletes;

    protected $table = 'booking_queues';

    protected $fillable = [
        'tenant_id',
        'branch_id',
        'resource_id',
        'name',
        'prefix',
        'is_active',
        'current_sequence_date',
        'current_sequence_number',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'current_sequence_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($builder) {
            if (app()->bound('currentTenant') && app('currentTenant')) {
                $builder->where('tenant_id', app('currentTenant')->id);
            } elseif (auth()->check() && (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id())) {
                $builder->where('tenant_id', (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id()));
            }
        });
        
        static::creating(function ($model) {
            if (!$model->tenant_id && auth()->check()) {
                $model->tenant_id = (app()->bound('currentTenant') ? app('currentTenant')->id : auth()->id());
            }
        });
    }

    public function entries(): HasMany
    {
        return $this->hasMany(BookingQueueEntry::class, 'queue_id');
    }

    public function displays(): HasMany
    {
        return $this->hasMany(BookingQueueDisplay::class, 'queue_id');
    }
}
