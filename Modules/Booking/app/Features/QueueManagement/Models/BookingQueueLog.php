<?php

namespace Modules\Booking\app\Features\QueueManagement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class BookingQueueLog extends Model
{
    protected $table = 'booking_queue_logs';

    protected $fillable = [
        'tenant_id',
        'queue_entry_id',
        'action',
        'performed_by_user_id',
        'notes',
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
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(BookingQueueEntry::class, 'queue_entry_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by_user_id');
    }
}
